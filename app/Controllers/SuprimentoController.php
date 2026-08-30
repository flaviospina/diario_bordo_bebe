<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Autenticacao;
use App\Core\Requisicao;
use App\Core\Resposta;
use App\Core\Sessao;
use App\Core\Visao;
use App\Repositories\RepositorioSuprimentos;

final class SuprimentoController
{
    public function index(Requisicao $requisicao): void
    {
        Visao::exibir('suprimentos/index', [
            'titulo' => 'Suprimentos',
            'suprimentos' => (new RepositorioSuprimentos())->listar(),
            'podeResolver' => Autenticacao::temPapel('responsavel', 'admin_familia'),
        ]);
    }

    public function acao(Requisicao $requisicao): void
    {
        $repositorio = new RepositorioSuprimentos();
        $acao = (string)$requisicao->post('acao', '');

        if ($acao === 'pedir') {
            $item = trim((string)$requisicao->post('item', ''));
            if ($item === '') {
                Sessao::flash('erro', 'Informe o item.');
                Resposta::redirecionarRota('suprimentos.index');
            }
            $nivel = in_array($requisicao->post('nivel'), ['baixo', 'acabou'], true)
                ? (string)$requisicao->post('nivel') : 'baixo';
            $repositorio->criar(mb_substr($item, 0, 120), $nivel, Autenticacao::id());
            Sessao::flash('sucesso', 'Pedido registrado.');
        } elseif ($acao === 'resolver' && Autenticacao::temPapel('responsavel', 'admin_familia')) {
            $repositorio->resolver((int)$requisicao->post('id', '0'));
            Sessao::flash('sucesso', 'Item marcado como resolvido.');
        } else {
            Visao::erro403();
        }
        Resposta::redirecionarRota('suprimentos.index');
    }
}
