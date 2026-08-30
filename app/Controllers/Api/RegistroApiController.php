<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Requisicao;
use App\Core\Resposta;
use App\Repositories\RepositorioCategorias;
use App\Repositories\RepositorioCriancas;
use App\Services\ServicoConfiguracoes;
use App\Services\ServicoGrade;
use App\Services\ServicoRegistros;

/**
 * API de registros (uso do próprio front: sincronização offline e polling).
 * Autenticação por sessão + header X-CSRF (validado no Aplicacao).
 */
final class RegistroApiController
{
    /** POST /api/registros — cria um registro isolado. */
    public function criar(Requisicao $requisicao): void
    {
        $item = $requisicao->json();
        $resultado = self::processarCriacao($item);
        Resposta::json($resultado, $resultado['resultado'] === 'erro' ? 422 : 200);
    }

    /** GET /api/dia/{data} — estado da grade (polling dos pais, 60 s). */
    public function dia(Requisicao $requisicao): void
    {
        $data = $requisicao->parametro('data');
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $data) !== 1) {
            Resposta::erroJson('Data inválida.', 422);
        }
        $grade = new ServicoGrade();
        $crianca = $grade->criancaAtual($requisicao->get('crianca'));
        if ($crianca === null) {
            Resposta::erroJson('Nenhuma criança cadastrada.', 404);
        }
        $dia = $grade->montarDia($crianca, $data);
        Resposta::json([
            'data' => $dia['data'],
            'crianca' => $crianca['slug'],
            'versao' => $dia['versao'],
            'ultima_atividade' => $dia['ultima_atividade'],
            'estatisticas' => $dia['estatisticas'],
        ]);
    }

    /**
     * Processa a criação de um item (usado também pela sincronização em lote).
     * @param array<string,mixed> $item
     * @return array<string,mixed>
     */
    public static function processarCriacao(array $item, string $origem = 'online'): array
    {
        $uuid = (string)($item['uuid_cliente'] ?? '');
        $resposta = static fn(string $resultado, string $mensagem = ''): array => [
            'uuid_cliente' => $uuid, 'resultado' => $resultado, 'mensagem' => $mensagem,
        ];

        $crianca = (new RepositorioCriancas())->buscarPorSlug((string)($item['crianca'] ?? ''));
        if ($crianca === null) {
            return $resposta('erro', 'Criança inexistente.');
        }
        $categoria = (new RepositorioCategorias())->buscarPorSlug((string)($item['categoria'] ?? ''));
        $inativas = (array)(new ServicoConfiguracoes())->obter('categorias_inativas');
        if ($categoria === null || in_array($categoria['slug'], $inativas, true)) {
            return $resposta('erro', 'Categoria inexistente ou desativada.');
        }
        $inicio = (string)($item['inicio'] ?? '');
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}(:\d{2})?$/', $inicio) !== 1) {
            return $resposta('erro', 'Início inválido.');
        }
        $status = in_array($item['status'] ?? 'feito', ['feito', 'nao_feito', 'parcial'], true)
            ? (string)($item['status'] ?? 'feito') : 'feito';
        $justificativa = trim((string)($item['justificativa'] ?? ''));
        if ($status === 'nao_feito' && $justificativa === '') {
            return $resposta('erro', 'Justificativa obrigatória para "não feito".');
        }

        $servico = new ServicoRegistros();
        $validacao = $servico->validarCampos(
            $categoria,
            is_array($item['dados'] ?? null) ? $item['dados'] : [],
            $status !== 'nao_feito'
        );
        if ($validacao['erros'] !== []) {
            return $resposta('erro', implode(' ', $validacao['erros']));
        }

        $fim = (string)($item['fim'] ?? '');
        $resultado = $servico->criar($categoria, [
            'uuid_cliente' => $uuid !== '' ? $uuid : null,
            'crianca_id' => (int)$crianca['id'],
            'roteiro_bloco_id' => ((int)($item['bloco'] ?? 0)) ?: null,
            'inicio' => strlen($inicio) === 16 ? $inicio . ':00' : $inicio,
            'fim' => preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}(:\d{2})?$/', $fim) === 1 ? $fim : null,
            'dados' => $validacao['dados'] === [] ? null : $validacao['dados'],
            'observacao' => trim((string)($item['observacao'] ?? '')) ?: null,
            'status' => $status,
            'justificativa' => $justificativa !== '' ? $justificativa : null,
            'origem' => $origem,
        ], '');

        if ($resultado['intercorrencia'] !== null && class_exists(\App\Services\ServicoNotificacoes::class)) {
            (new \App\Services\ServicoNotificacoes())->notificarIntercorrencia(
                $resultado['intercorrencia']['codigo'],
                $resultado['intercorrencia']['gravidade']
            );
        }

        $saida = $resposta($resultado['duplicado'] ? 'duplicado' : 'criado');
        $saida['codigo'] = (string)$resultado['registro']['codigo_publico'];
        return $saida;
    }
}
