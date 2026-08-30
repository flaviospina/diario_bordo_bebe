<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Requisicao;
use App\Core\Resposta;
use App\Core\Sessao;
use App\Core\Visao;
use App\Services\ServicoConvites;

/**
 * Entrada no sistema por convite (não há cadastro público na v1).
 */
final class ConviteController
{
    public function mostrar(Requisicao $requisicao): void
    {
        $token = $requisicao->parametro('token');
        $convite = (new ServicoConvites())->buscarPorToken($token);
        if ($convite === null) {
            Sessao::flash('erro', 'Convite inválido, expirado ou já utilizado.');
            Resposta::redirecionarRota('login');
        }
        Visao::exibir('autenticacao/convite', [
            'titulo'  => 'Criar sua conta',
            'convite' => $convite,
            'token'   => $token,
        ], 'autenticacao');
    }

    public function aceitar(Requisicao $requisicao): void
    {
        $token = $requisicao->parametro('token');
        $erro = (new ServicoConvites())->aceitar($token, [
            'nome'     => (string)$requisicao->post('nome', ''),
            'senha'    => (string)$requisicao->post('senha', ''),
            'telefone' => $requisicao->post('telefone'),
        ], $requisicao->ip());

        if ($erro !== null) {
            Sessao::flash('erro', $erro);
            Resposta::redirecionarRota('convite.aceitar', ['token' => $token]);
        }
        Sessao::flash('sucesso', 'Conta criada! Entre com seu e-mail e a senha escolhida.');
        Resposta::redirecionarRota('login');
    }
}
