<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Requisicao;
use App\Core\Visao;

/**
 * Destino temporário das rotas de fases futuras: mantém o mapa de rotas (e o
 * helper url()) completo desde a Fase 1, sem quebrar links do layout.
 * Some conforme cada fase entrega a tela real.
 */
final class PlaceholderController
{
    public function emDesenvolvimento(Requisicao $requisicao): void
    {
        Visao::exibir('inicio/em_desenvolvimento', [
            'titulo' => 'Em desenvolvimento',
        ]);
    }
}
