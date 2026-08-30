<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Autenticacao;
use App\Core\Requisicao;
use App\Core\Resposta;
use App\Core\Sessao;
use App\Core\Visao;
use App\Repositories\RepositorioLogAcessos;
use App\Repositories\RepositorioSolicitacoes;
use App\Services\ServicoRegistros;

/**
 * Solicitações de edição/exclusão: os pais aprovam ou recusam dentro do sistema.
 */
final class SolicitacaoController
{
    public function lista(Requisicao $requisicao): void
    {
        $repositorio = new RepositorioSolicitacoes();
        Visao::exibir('solicitacoes/lista', [
            'titulo' => 'Solicitações',
            'pendentes' => $repositorio->listar('pendente'),
            'resolvidas' => array_values(array_filter(
                $repositorio->listar(),
                static fn(array $s): bool => $s['status'] !== 'pendente'
            )),
        ]);
    }

    public function decidirForm(Requisicao $requisicao): void
    {
        $solicitacao = $this->solicitacaoDaRota($requisicao);
        Visao::exibir('solicitacoes/decidir', [
            'titulo' => 'Solicitação ' . $solicitacao['codigo_publico'],
            'solicitacao' => $solicitacao,
            'payload' => json_decode((string)$solicitacao['payload_proposto'], true) ?: [],
        ]);
    }

    public function decidir(Requisicao $requisicao): void
    {
        $solicitacao = $this->solicitacaoDaRota($requisicao);
        if ($solicitacao['status'] !== 'pendente') {
            Sessao::flash('erro', 'Esta solicitação já foi decidida.');
            Resposta::redirecionarRota('solicitacoes.lista');
        }
        $decisao = (string)$requisicao->post('decisao', '');
        if (!in_array($decisao, ['aprovar', 'recusar'], true)) {
            Visao::erro404();
        }
        $resposta = trim((string)$requisicao->post('resposta', '')) ?: null;
        $repositorio = new RepositorioSolicitacoes();

        if ($decisao === 'aprovar') {
            (new ServicoRegistros())->aplicarSolicitacaoAprovada($solicitacao, $requisicao->ip());
            $repositorio->decidir((int)$solicitacao['id'], 'aprovada', Autenticacao::id(), $resposta);
            Sessao::flash('sucesso', 'Solicitação aprovada e aplicada ao registro.');
        } else {
            $repositorio->decidir((int)$solicitacao['id'], 'recusada', Autenticacao::id(), $resposta);
            Sessao::flash('sucesso', 'Solicitação recusada.');
        }
        (new RepositorioLogAcessos())->registrar(
            Autenticacao::familiaId(),
            Autenticacao::id(),
            'solicitacao_' . ($decisao === 'aprovar' ? 'aprovada' : 'recusada'),
            'solicitacoes_edicao',
            (int)$solicitacao['id'],
            $requisicao->ip()
        );
        Resposta::redirecionarRota('solicitacoes.lista');
    }

    private function solicitacaoDaRota(Requisicao $requisicao): array
    {
        $solicitacao = (new RepositorioSolicitacoes())->buscarPorCodigo($requisicao->parametro('codigo'));
        if ($solicitacao === null) {
            Visao::erro404();
        }
        return $solicitacao;
    }
}
