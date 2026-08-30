<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Requisicao;
use App\Core\Resposta;
use App\Core\Sessao;
use App\Core\Visao;
use App\Repositories\RepositorioFamilias;
use App\Repositories\RepositorioLogAcessos;
use App\Services\ServicoPlataforma;

/**
 * Painel da plataforma (super_admin): gestão de famílias e planos.
 * O super_admin nunca vê conteúdo de registros — apenas metadados de tenant.
 */
final class PainelAdminController
{
    public function index(Requisicao $requisicao): void
    {
        Visao::exibir('painel/index', [
            'titulo' => 'Painel da plataforma',
            'familias' => (new RepositorioFamilias())->listarTodas(),
            'linkConvite' => Sessao::obter('_link_convite_plataforma'),
        ]);
    }

    public function acao(Requisicao $requisicao): void
    {
        Sessao::remover('_link_convite_plataforma');
        $familias = new RepositorioFamilias();
        $servico = new ServicoPlataforma();
        $log = new RepositorioLogAcessos();
        $acao = (string)$requisicao->post('acao', '');

        if ($acao === 'criar_familia') {
            $resultado = $servico->criarFamiliaComConvite(
                (string)$requisicao->post('nome', ''),
                (string)$requisicao->post('plano', 'familiar'),
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
                $plano = (string)$requisicao->post('novo_plano', 'familiar');
                $familias->alterarPlano((int)$familia['id'], in_array($plano, ['familiar', 'premium'], true) ? $plano : 'familiar');
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
        $log->registrar(null, \App\Core\Autenticacao::id(), 'plataforma_' . $acao, 'familias', (int)$familia['id'], $requisicao->ip());
        Resposta::redirecionarRota('admin.painel');
    }
}
