<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Autenticacao;
use App\Core\Requisicao;
use App\Core\Resposta;
use App\Core\Sessao;
use App\Core\Visao;
use App\Repositories\RepositorioConvitesFamilia;
use App\Repositories\RepositorioFamilias;
use App\Repositories\RepositorioListaEspera;
use App\Repositories\RepositorioLogAcessos;
use App\Repositories\RepositorioPlanos;
use App\Services\ServicoPlataforma;

/**
 * Painel da plataforma (super_admin): famílias, planos, convites de família
 * fundadora e lista de espera. O super_admin nunca vê conteúdo de registros.
 */
final class PainelAdminController
{
    public function index(Requisicao $requisicao): void
    {
        Visao::exibir('painel/index', [
            'titulo' => 'Painel da plataforma',
            'familias' => (new RepositorioFamilias())->listarTodas(),
            'planos' => (new RepositorioPlanos())->ativos(),
            'convitesFamilia' => (new RepositorioConvitesFamilia())->listarRecentes(),
            'listaEspera' => (new RepositorioListaEspera())->listar(),
            'linkConvite' => Sessao::obter('_link_convite_plataforma'),
        ]);
    }

    public function acao(Requisicao $requisicao): void
    {
        Sessao::remover('_link_convite_plataforma');
        $familias = new RepositorioFamilias();
        $servico = new ServicoPlataforma();
        $log = new RepositorioLogAcessos();
        $planos = new RepositorioPlanos();
        $acao = (string)$requisicao->post('acao', '');

        $planoValido = static function (string $chave) use ($planos): string {
            return $planos->porChave($chave) !== null ? $chave : 'fundador';
        };

        if ($acao === 'criar_familia') {
            $resultado = $servico->criarFamiliaComConvite(
                (string)$requisicao->post('nome', ''),
                $planoValido((string)$requisicao->post('plano', 'fundador')),
                (string)$requisicao->post('email_admin', '')
            );
            if ($resultado['erro'] !== null) {
                Sessao::flash('erro', $resultado['erro']);
            } else {
                Sessao::definir('_link_convite_plataforma', $resultado['link']);
                Sessao::flash('sucesso', 'Família criada; convite do administrador enviado por e-mail.');
            }
            Resposta::redirecionarRota('admin.painel');
        }

        // ── Convites de família fundadora (auto-cadastro) ─────
        if ($acao === 'convite_familia') {
            $codigo = (new RepositorioConvitesFamilia())->criar(
                $planoValido((string)$requisicao->post('plano', 'fundador')),
                Autenticacao::id(),
                max(1, min(30, (int)$requisicao->post('validade_dias', '7'))),
                trim((string)$requisicao->post('observacao', '')) ?: null
            );
            Sessao::definir('_link_convite_plataforma', url_absoluta('comecar', ['codigo' => $codigo]));
            Sessao::flash('sucesso', 'Convite de família gerado! Copie o link e envie pelo WhatsApp.');
            $log->registrar(null, Autenticacao::id(), 'plataforma_convite_familia', 'convites_familia', null, $requisicao->ip());
            Resposta::redirecionarRota('admin.painel');
        }
        if ($acao === 'revogar_convite_familia') {
            (new RepositorioConvitesFamilia())->revogar((int)$requisicao->post('convite_id', '0'));
            Sessao::flash('sucesso', 'Convite revogado.');
            Resposta::redirecionarRota('admin.painel');
        }

        // ── Lista de espera ───────────────────────────────────
        if ($acao === 'espera_convidar') {
            $espera = new RepositorioListaEspera();
            $id = (int)$requisicao->post('espera_id', '0');
            $codigo = (new RepositorioConvitesFamilia())->criar(
                'fundador', Autenticacao::id(), 7, 'Lista de espera #' . $id
            );
            $espera->mudarStatus($id, 'convidado');
            Sessao::definir('_link_convite_plataforma', url_absoluta('comecar', ['codigo' => $codigo]));
            Sessao::flash('sucesso', 'Convite gerado para o interessado — copie o link e envie.');
            Resposta::redirecionarRota('admin.painel');
        }
        if ($acao === 'espera_descartar') {
            (new RepositorioListaEspera())->mudarStatus((int)$requisicao->post('espera_id', '0'), 'descartado');
            Sessao::flash('sucesso', 'Interessado marcado como descartado.');
            Resposta::redirecionarRota('admin.painel');
        }

        $familia = $familias->buscarPorCodigo((string)$requisicao->post('familia', ''));
        if ($familia === null || $familia['plano'] === 'plataforma') {
            Sessao::flash('erro', 'Família não encontrada.');
            Resposta::redirecionarRota('admin.painel');
        }

        switch ($acao) {
            case 'suspender':
                $familias->alterarStatus((int)$familia['id'], 'suspensa');
                Sessao::flash('sucesso', 'Família suspensa (o login dos usuários dela fica bloqueado).');
                break;
            case 'reativar':
                $familias->alterarStatus((int)$familia['id'], 'ativa');
                Sessao::flash('sucesso', 'Família reativada.');
                break;
            case 'plano':
                $familias->alterarPlano((int)$familia['id'], $planoValido((string)$requisicao->post('novo_plano', 'fundador')));
                Sessao::flash('sucesso', 'Plano atualizado.');
                break;
            case 'excluir':
                // Exclusão definitiva exige digitar o nome exato da família
                if ((string)$requisicao->post('confirmacao', '') !== (string)$familia['nome']) {
                    Sessao::flash('erro', 'Digite o nome exato da família para confirmar a exclusão definitiva.');
                    break;
                }
                $servico->excluirFamilia((int)$familia['id']);
                Sessao::flash('sucesso', 'Família e todos os seus dados foram excluídos definitivamente.');
                break;
            default:
                Sessao::flash('erro', 'Ação desconhecida.');
        }
        $log->registrar(null, Autenticacao::id(), 'plataforma_' . $acao, 'familias', (int)$familia['id'], $requisicao->ip());
        Resposta::redirecionarRota('admin.painel');
    }
}
