<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Encapsula a requisição HTTP atual.
 */
final class Requisicao
{
    public readonly string $metodo;
    public readonly string $caminho;      // caminho já sem o BASE_PATH, ex.: /entrar
    public readonly string $caminhoBruto; // caminho original da URL, com BASE_PATH

    /** @var array<string,string> parâmetros capturados da rota ({codigo}, {data}...) */
    public array $parametros = [];

    public function __construct()
    {
        $this->metodo = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        $uri = (string)($_SERVER['REQUEST_URI'] ?? '/');
        $caminho = parse_url($uri, PHP_URL_PATH) ?: '/';
        $caminho = rawurldecode($caminho);
        $this->caminhoBruto = $caminho;

        $base = BASE_PATH;
        if ($base !== '' && str_starts_with($caminho, $base)) {
            $caminho = substr($caminho, strlen($base));
        }
        if ($caminho === '' || $caminho === false) {
            $caminho = '/';
        }
        $this->caminho = $caminho;
    }

    public function post(string $chave, ?string $padrao = null): ?string
    {
        $valor = $_POST[$chave] ?? $padrao;
        return is_string($valor) ? trim($valor) : $padrao;
    }

    public function get(string $chave, ?string $padrao = null): ?string
    {
        $valor = $_GET[$chave] ?? $padrao;
        return is_string($valor) ? trim($valor) : $padrao;
    }

    public function parametro(string $nome, string $padrao = ''): string
    {
        return $this->parametros[$nome] ?? $padrao;
    }

    /** Corpo JSON (endpoints de API). */
    public function json(): array
    {
        $corpo = file_get_contents('php://input') ?: '';
        $dados = json_decode($corpo, true);
        return is_array($dados) ? $dados : [];
    }

    public function ip(): string
    {
        return (string)($_SERVER['REMOTE_ADDR'] ?? '');
    }

    public function userAgent(): string
    {
        return mb_substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
    }

    public function ehApi(): bool
    {
        return str_starts_with($this->caminho, '/api/');
    }

    public function cabecalho(string $nome): string
    {
        $chave = 'HTTP_' . strtoupper(str_replace('-', '_', $nome));
        return (string)($_SERVER[$chave] ?? '');
    }
}
