<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Proteção CSRF: token por sessão, exigido em todo POST de navegação.
 * Endpoints /api/* autenticam por sessão + header X-CSRF (ver Aplicacao)
 * ou por token de serviço (webhooks/tarefas), nunca ficam sem proteção.
 */
final class Csrf
{
    private const CHAVE = '_csrf_token';

    public static function token(): string
    {
        $token = Sessao::obter(self::CHAVE);
        if (!is_string($token) || $token === '') {
            $token = bin2hex(random_bytes(32));
            Sessao::definir(self::CHAVE, $token);
        }
        return $token;
    }

    public static function validar(?string $recebido): bool
    {
        $esperado = Sessao::obter(self::CHAVE);
        return is_string($esperado) && is_string($recebido) && $recebido !== ''
            && hash_equals($esperado, $recebido);
    }

    /** Campo oculto pronto para formulários. */
    public static function campo(): string
    {
        return '<input type="hidden" name="_csrf" value="' . e(self::token()) . '">';
    }
}
