<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Autenticacao;
use App\Core\Requisicao;
use App\Core\Resposta;

/**
 * Rota raiz: redireciona cada papel para a sua tela principal.
 */
final class HomeController
{
    public function inicio(Requisicao $requisicao): void
    {
        if (!Autenticacao::estaLogado()) {
            Resposta::redirecionarRota('login');
        }

        match (Autenticacao::papel()) {
            'cuidador'    => Resposta::redirecionarRota('cuidador.dia'),
            'super_admin' => Resposta::redirecionarRota('admin.painel'),
            default       => Resposta::redirecionarRota('pais.acompanhar'), // responsavel, admin_familia, leitor
        };
    }
}
