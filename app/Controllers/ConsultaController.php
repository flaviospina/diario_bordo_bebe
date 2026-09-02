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
        $abertura = $this->aberturaOuErro($requisicao);
        $convite = $abertura['convite'];
        $crianca = $abertura['crianca'];
        $familiaId = (int)$convite['familia_id'];

        $historico = (new RepositorioMedicoes($familiaId))->listar((int)$crianca['id'], 60);
        Visao::exibir('consulta/ficha', [
            'titulo' => 'Ficha de ' . $crianca['nome'],
            'codigo' => $requisicao->parametro('codigo'),
            'convite' => $convite,
            'crianca' => $crianca,
            'idade' => $crianca['data_nascimento'] !== null
                ? ServicoCrescimento::idadeFormatada((string)$crianca['data_nascimento']) : null,
            'ultimas' => (new RepositorioMedicoes($familiaId))->ultimasMedidas((int)$crianca['id']),
            'historico' => $historico,
            'curvas' => (new ServicoCrescimento())->curvas($crianca, $historico),
            'vacinas' => (new RepositorioVacinas($familiaId))->listar((int)$crianca['id']),
            'resumo' => (new ServicoConsulta())->resumoRotina($familiaId, (int)$crianca['id']),
        ], 'publico');
    }

    /** Página separada com o formulário de registro — a ficha fica só leitura. */
    public function registrar(Requisicao $requisicao): void
    {
        $abertura = $this->aberturaOuErro($requisicao);
        $crianca = $abertura['crianca'];

        Visao::exibir('consulta/registrar', [
            'titulo' => 'Registrar consulta — ' . $crianca['nome'],
            'codigo' => $requisicao->parametro('codigo'),
            'crianca' => $crianca,
            'idade' => $crianca['data_nascimento'] !== null
                ? ServicoCrescimento::idadeFormatada((string)$crianca['data_nascimento']) : null,
        ], 'publico');
    }

    /** Resolve o código público ou encerra com a página de erro adequada. */
    private function aberturaOuErro(Requisicao $requisicao): array
    {
        // Robôs de prévia de link (WhatsApp, Telegram...) visitam a URL para
        // montar o cartão. Eles recebem uma página neutra e NÃO marcam a
        // abertura — só um navegador de verdade conta como "link aberto".
        if (self::ehRoboDePrevia($requisicao->userAgent())) {
            Visao::exibir('consulta/previa', ['titulo' => 'Ficha para consulta'], 'publico');
        }
        $abertura = (new ServicoConsulta())->abrirPorCodigo($requisicao->parametro('codigo'), true);
        if ($abertura['erro'] !== null) {
            $status = $abertura['erro'] === 'inexistente' ? 404 : 410;
            Visao::exibir('consulta/erro', [
                'titulo' => 'Ficha indisponível',
                'tipo' => $abertura['erro'],
            ], 'publico', $status);
        }
        return $abertura;
    }

    private static function ehRoboDePrevia(string $userAgent): bool
    {
        foreach (['whatsapp', 'facebookexternalhit', 'facebot', 'telegrambot', 'twitterbot',
                  'linkedinbot', 'slackbot', 'discordbot', 'skypeuripreview', 'googlebot', 'bingbot'] as $robo) {
            if (stripos($userAgent, $robo) !== false) {
                return true;
            }
        }
        return false;
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
