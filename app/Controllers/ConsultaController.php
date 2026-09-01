<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Requisicao;
use App\Core\Resposta;
use App\Core\Sessao;
use App\Core\Visao;
use App\Repositories\RepositorioConsultasMedicas;
use App\Repositories\RepositorioCriancas;
use App\Repositories\RepositorioMedicoes;
use App\Repositories\RepositorioVacinas;
use App\Services\ServicoConsulta;
use App\Services\ServicoCrescimento;

/**
 * Ficha para a consulta (Alteração 01): o responsável gera um link/QR de uso
 * único; o pediatra abre a ficha SEM login, confere os dados e devolve
 * medidas/vacinas/conduta. A página pública nunca mostra a rotina dia a dia,
 * fotos nem os usuários da família.
 */
final class ConsultaController
{
    // ── Responsável autenticado: gerar e revogar links ────────

    public function gerar(Requisicao $requisicao): void
    {
        $crianca = $this->criancaDaRota($requisicao);
        Visao::exibir('consulta/gerar', [
            'titulo' => 'Ficha para consulta — ' . ($crianca['apelido'] ?: $crianca['nome']),
            'crianca' => $crianca,
            'convites' => (new RepositorioConsultasMedicas())->convitesAtivos((int)$crianca['id']),
        ]);
    }

    public function gerarEnviar(Requisicao $requisicao): void
    {
        $crianca = $this->criancaDaRota($requisicao);
        $acao = (string)$requisicao->post('acao', 'criar');

        if ($acao === 'revogar') {
            (new RepositorioConsultasMedicas())->revogarConvite((int)$requisicao->post('convite_id', '0'));
            Sessao::flash('sucesso', 'Link revogado. Ele não abre mais.');
        } else {
            $resultado = (new ServicoConsulta())->gerarLink($crianca);
            Sessao::flash(
                $resultado['erro'] === null ? 'sucesso' : 'erro',
                $resultado['erro'] ?? 'Link gerado! Mostre o QR code na consulta — vale por 48 horas e só abre uma vez.'
            );
        }
        Resposta::redirecionarRota('consulta.gerar', ['slug' => $crianca['slug']]);
    }

    // ── Página pública do pediatra (sem sessão) ───────────────

    public function ficha(Requisicao $requisicao): void
    {
        $codigo = $requisicao->parametro('codigo');
        $servico = new ServicoConsulta();
        $abertura = $servico->abrirPorCodigo($codigo, true);

        if ($abertura['erro'] !== null) {
            $status = $abertura['erro'] === 'inexistente' ? 404 : 410;
            Visao::exibir('consulta/erro', [
                'titulo' => 'Ficha indisponível',
                'tipo' => $abertura['erro'],
            ], 'publico', $status);
        }

        $convite = $abertura['convite'];
        $crianca = $abertura['crianca'];
        $familiaId = (int)$convite['familia_id'];

        Visao::exibir('consulta/ficha', [
            'titulo' => 'Ficha de ' . $crianca['nome'],
            'codigo' => $codigo,
            'convite' => $convite,
            'crianca' => $crianca,
            'idade' => $crianca['data_nascimento'] !== null
                ? ServicoCrescimento::idadeFormatada((string)$crianca['data_nascimento']) : null,
            'ultimas' => (new RepositorioMedicoes($familiaId))->ultimasMedidas((int)$crianca['id']),
            'historico' => (new RepositorioMedicoes($familiaId))->listar((int)$crianca['id'], 12),
            'vacinas' => (new RepositorioVacinas($familiaId))->listar((int)$crianca['id']),
            'resumo' => $servico->resumoRotina($familiaId, (int)$crianca['id']),
        ], 'publico');
    }

    private function criancaDaRota(Requisicao $requisicao): array
    {
        $crianca = (new RepositorioCriancas())->buscarPorSlug($requisicao->parametro('slug'));
        if ($crianca === null) {
            Visao::erro404();
        }
        return $crianca;
    }
}
