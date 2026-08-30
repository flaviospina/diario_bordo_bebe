<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Autoloader PSR-4 mínimo, sem Composer (restrição de hospedagem compartilhada).
 * Mapeia o namespace App\ para a pasta /app.
 */
final class Autoloader
{
    public static function registrar(): void
    {
        spl_autoload_register(static function (string $classe): void {
            $prefixo = 'App\\';
            if (!str_starts_with($classe, $prefixo)) {
                return;
            }
            $relativo = substr($classe, strlen($prefixo));
            $arquivo  = RAIZ_PROJETO . '/app/' . str_replace('\\', '/', $relativo) . '.php';
            if (is_file($arquivo)) {
                require $arquivo;
            }
        });
    }
}
