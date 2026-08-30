<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Requisicao;
use App\Core\Resposta;
use App\Repositories\RepositorioRegistros;
use App\Services\ServicoRegistros;

/**
 * Sincronização da fila offline (Fase 4).
 *
 * Regras críticas:
 *  8.3 — idempotência: criações repetidas (mesmo uuid_cliente) nunca duplicam;
 *  8.4 — conflito: uma EDIÇÃO enfileirada offline só é aplicada se o registro
 *        não mudou no servidor desde que foi carregado (base_atualizado_em);
 *        se mudou, NÃO sobrescreve — vira solicitação de revisão para os pais.
 */
final class SincronizacaoApiController
{
    public function sincronizar(Requisicao $requisicao): void
    {
        $corpo = $requisicao->json();
        $itens = is_array($corpo['itens'] ?? null) ? $corpo['itens'] : [];
        if ($itens === [] || count($itens) > 100) {
            Resposta::erroJson('Envie de 1 a 100 itens.', 422);
        }

        $resultados = [];
        foreach ($itens as $item) {
            if (!is_array($item)) {
                continue;
            }
            $tipo = (string)($item['tipo'] ?? 'criacao');
            $resultados[] = $tipo === 'edicao'
                ? $this->processarEdicao($item, $requisicao->ip())
                : RegistroApiController::processarCriacao($item, 'offline');
        }
        Resposta::json(['resultados' => $resultados]);
    }

    /** @param array<string,mixed> $item */
    private function processarEdicao(array $item, string $ip): array
    {
        $codigo = (string)($item['codigo'] ?? '');
        $resposta = static fn(string $resultado, string $mensagem = ''): array => [
            'codigo' => $codigo, 'resultado' => $resultado, 'mensagem' => $mensagem,
        ];

        $registro = (new RepositorioRegistros())->buscarPorCodigo($codigo);
        if ($registro === null || $registro['excluido_em'] !== null) {
            return $resposta('erro', 'Registro inexistente ou excluído.');
        }

        $servico = new ServicoRegistros();
        $categoria = ['schema_campos' => $registro['schema_campos']];
        $status = in_array($item['status'] ?? '', ['feito', 'nao_feito', 'parcial'], true)
            ? (string)$item['status'] : (string)$registro['status'];
        $validacao = $servico->validarCampos(
            $categoria,
            is_array($item['dados'] ?? null) ? $item['dados'] : [],
            $status !== 'nao_feito'
        );
        if ($validacao['erros'] !== []) {
            return $resposta('erro', implode(' ', $validacao['erros']));
        }

        $campos = [
            'inicio' => (string)($item['inicio'] ?? $registro['inicio']),
            'fim' => $item['fim'] ?? $registro['fim'],
            'dados' => $validacao['dados'] === [] ? null : $validacao['dados'],
            'observacao' => trim((string)($item['observacao'] ?? '')) ?: null,
            'status' => $status,
            'justificativa' => trim((string)($item['justificativa'] ?? '')) ?: null,
        ];

        // Regra 8.4: registro mudou no servidor enquanto a edição estava na fila?
        $base = (string)($item['base_atualizado_em'] ?? '');
        if ($base !== '' && $base !== (string)$registro['atualizado_em']) {
            $servico->solicitarAlteracao(
                $registro,
                'Conflito de sincronização: o registro foi alterado no servidor enquanto esta edição estava na fila offline.',
                $campos,
                'conflito_sync'
            );
            return $resposta('conflito', 'O registro mudou no servidor; a edição virou solicitação de revisão para os pais.');
        }

        // Fora da janela de edição, mesmo online a edição viraria solicitação
        $permissao = $servico->permissaoEdicao($registro);
        if ($permissao === 'nada') {
            return $resposta('erro', 'Sem permissão para editar este registro.');
        }
        if ($permissao === 'solicitar') {
            $servico->solicitarAlteracao($registro, 'Edição enviada da fila offline, fora da janela de edição.', $campos);
            return $resposta('solicitacao', 'Edição enviada aos responsáveis para aprovação.');
        }

        $servico->editar($registro, $campos, 'Edição sincronizada da fila offline', $ip);
        return $resposta('editado');
    }
}
