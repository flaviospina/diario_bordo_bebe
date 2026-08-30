<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Carrega o .env e expõe as variáveis de ambiente da aplicação.
 *
 * Ordem de busca do arquivo (ver docs/planejamento.md §4.1 — em hospedagem
 * compartilhada o ideal é o .env ficar FORA do webroot):
 *   1. Caminho apontado pela variável de servidor DIARIOBEBE_ENV (SetEnv no vhost)
 *   2. ../../diariobebe_privado/.env  (pasta irmã de public_html)
 *   3. .env na raiz do projeto (bloqueado por .htaccess — fallback)
 */
final class Ambiente
{
    /** @var array<string,string> */
    private static array $variaveis = [];
    private static bool $carregado = false;

    public static function carregar(): void
    {
        if (self::$carregado) {
            return;
        }
        self::$carregado = true;

        foreach (self::caminhosCandidatos() as $caminho) {
            if ($caminho !== '' && is_file($caminho) && is_readable($caminho)) {
                self::$variaveis = self::analisarArquivo($caminho);
                return;
            }
        }
    }

    /** @return string[] */
    private static function caminhosCandidatos(): array
    {
        return [
            (string)($_SERVER['DIARIOBEBE_ENV'] ?? getenv('DIARIOBEBE_ENV') ?: ''),
            dirname(RAIZ_PROJETO, 2) . '/diariobebe_privado/.env',
            RAIZ_PROJETO . '/.env',
        ];
    }

    /** @return array<string,string> */
    private static function analisarArquivo(string $caminho): array
    {
        $variaveis = [];
        $linhas = file($caminho, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        foreach ($linhas as $linha) {
            $linha = trim($linha);
            if ($linha === '' || str_starts_with($linha, '#') || !str_contains($linha, '=')) {
                continue;
            }
            [$chave, $valor] = explode('=', $linha, 2);
            $chave = trim($chave);
            $valor = trim($valor);
            // Remove comentário no fim da linha (apenas fora de aspas)
            if ($valor !== '' && $valor[0] !== '"' && $valor[0] !== "'") {
                $posicao = strpos($valor, ' #');
                if ($posicao !== false) {
                    $valor = rtrim(substr($valor, 0, $posicao));
                }
            }
            // Remove aspas envolventes
            if (strlen($valor) >= 2 && ($valor[0] === '"' || $valor[0] === "'") && str_ends_with($valor, $valor[0])) {
                $valor = substr($valor, 1, -1);
            }
            $variaveis[$chave] = $valor;
        }
        return $variaveis;
    }

    public static function obter(string $chave, string $padrao = ''): string
    {
        self::carregar();
        return self::$variaveis[$chave] ?? $padrao;
    }

    public static function ehDesenvolvimento(): bool
    {
        return self::obter('APP_AMBIENTE', 'producao') === 'desenvolvimento';
    }
}
