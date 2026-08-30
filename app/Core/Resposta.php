<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Utilitários de resposta HTTP: redirecionamentos, JSON, erros e headers de segurança.
 */
final class Resposta
{
    public static function emitirHeadersSeguranca(): void
    {
        if (headers_sent()) {
            return;
        }
        header("Content-Security-Policy: default-src 'self'; img-src 'self' data: blob:; "
            . "style-src 'self'; script-src 'self'; connect-src 'self'; "
            . "frame-ancestors 'none'; base-uri 'self'; form-action 'self'");
        $ehHttps = (($_SERVER['HTTPS'] ?? '') !== '' && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
        if ($ehHttps) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
    }

    /** Redireciona para uma rota nomeada. Nunca receba URL crua de fora. */
    public static function redirecionarRota(string $nomeRota, array $parametros = []): never
    {
        self::redirecionarCaminho(url($nomeRota, $parametros));
    }

    public static function redirecionarCaminho(string $caminho, int $codigo = 302): never
    {
        header('Location: ' . $caminho, true, $codigo);
        exit;
    }

    /** 301 para a forma canônica da URL (minúsculas, sem barra final). */
    public static function redirecionarCanonico(string $caminhoCanonico): never
    {
        $consulta = (string)($_SERVER['QUERY_STRING'] ?? '');
        $destino = BASE_PATH . $caminhoCanonico . ($consulta !== '' ? '?' . $consulta : '');
        self::redirecionarCaminho($destino === '' ? '/' : $destino, 301);
    }

    public static function json(array $dados, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function erroJson(string $mensagem, int $status): never
    {
        self::json(['erro' => $mensagem], $status);
    }
}
