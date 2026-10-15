<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Autenticacao;
use App\Core\Requisicao;
use App\Core\Visao;
use App\Repositories\RepositorioAvisos;
use App\Repositories\RepositorioPushInscricoes;
use App\Services\ServicoPush;

/**
 * Central de avisos (sino no topo): lista os avisos do usuário e os marca
 * como lidos ao abrir. Também é onde se ativam as notificações no aparelho.
 */
final class AvisosController
{
    public function index(Requisicao $requisicao): void
    {
        $avisos = new RepositorioAvisos();
        $lista = $avisos->listar(Autenticacao::id());
        $avisos->marcarTodosLidos(Autenticacao::id());

        Visao::exibir('avisos/index', [
            'titulo' => 'Avisos',
            'avisos' => $lista,
            'vapidPublica' => ServicoPush::chavePublica(),
            'temInscricao' => (new RepositorioPushInscricoes())->temInscricao(Autenticacao::id()),
        ]);
    }
}
