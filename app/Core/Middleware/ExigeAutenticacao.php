<?php

declare(strict_types=1);

namespace App\Core\Middleware;

use App\Core\Autenticacao;
use App\Core\Requisicao;
use App\Core\Resposta;
use App\Core\Sessao;

/**
 * Bloqueia rotas que exigem usuário autenticado.
 */
final class ExigeAutenticacao
{
    public function tratar(Requisicao $requisicao): void
    {
        if (Autenticacao::estaLogado()) {
            return;
        }
        if ($requisicao->ehApi()) {
            Resposta::erroJson('Não autenticado.', 401);
        }
        // Guarda o destino para voltar após o login
        Sessao::definir('_destino_pos_login', $requisicao->caminho);
        Resposta::redirecionarRota('login');
    }
}
