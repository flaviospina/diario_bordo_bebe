<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Renderização de views PHP simples dentro de um layout.
 * Escape de saída é responsabilidade das views via helper e() — sempre.
 */
final class Visao
{
    /**
     * @param string $view   caminho relativo em app/Views, ex.: 'autenticacao/entrar'
     * @param array  $dados  variáveis expostas à view
     * @param string $layout layout em app/Views/layouts (sem .php); '' = sem layout
     */
    public static function renderizar(string $view, array $dados = [], string $layout = 'base'): string
    {
        $conteudo = self::capturar($view, $dados);
        if ($layout === '') {
            return $conteudo;
        }
        $dados['conteudo'] = $conteudo;
        return self::capturar('layouts/' . $layout, $dados);
    }

    public static function exibir(string $view, array $dados = [], string $layout = 'base', int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: text/html; charset=utf-8');
        echo self::renderizar($view, $dados, $layout);
        exit;
    }

    public static function erro404(): never
    {
        self::exibir('erros/404', ['titulo' => 'Página não encontrada'], 'base', 404);
    }

    public static function erro403(): never
    {
        self::exibir('erros/403', ['titulo' => 'Acesso negado'], 'base', 403);
    }

    private static function capturar(string $view, array $dados): string
    {
        $arquivo = RAIZ_PROJETO . '/app/Views/' . $view . '.php';
        if (!is_file($arquivo)) {
            throw new \RuntimeException("View inexistente: {$view}");
        }
        extract($dados, EXTR_SKIP);
        ob_start();
        require $arquivo;
        return (string)ob_get_clean();
    }
}
