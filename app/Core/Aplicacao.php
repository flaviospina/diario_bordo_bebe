<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\Middleware\ExigeAutenticacao;
use App\Core\Middleware\ExigeConsentimentoLgpd;
use App\Core\Middleware\ExigePapel;

/**
 * Bootstrap e ciclo de vida da requisição:
 * env → constantes → sessão → rotas → canônico → middlewares → CSRF → controller.
 */
final class Aplicacao
{
    private Roteador $roteador;

    public function __construct()
    {
        require RAIZ_PROJETO . '/config/app.php';

        error_reporting(E_ALL);
        ini_set('display_errors', Ambiente::ehDesenvolvimento() ? '1' : '0');
        ini_set('log_errors', '1');
        if (is_dir(STORAGE_PATH . '/logs')) {
            ini_set('error_log', STORAGE_PATH . '/logs/php_erros.log');
        }

        $this->roteador = new Roteador();
        $registrar = require RAIZ_PROJETO . '/config/rotas.php';
        $registrar($this->roteador);
        $this->roteador->indexar();

        // Torna o roteador acessível ao helper url()
        $GLOBALS['__roteador'] = $this->roteador;
    }

    public function executar(): void
    {
        $requisicao = new Requisicao();

        try {
            Resposta::emitirHeadersSeguranca();
            Sessao::iniciar();

            $resolucao = $this->roteador->resolver($requisicao);
            if ($resolucao === null) {
                if ($requisicao->ehApi()) {
                    Resposta::erroJson('Recurso não encontrado.', http_response_code() === 405 ? 405 : 404);
                }
                Visao::erro404();
            }

            /** @var Rota $rota */
            [$rota, $parametros] = $resolucao;
            $requisicao->parametros = $parametros;

            foreach ($rota->middlewares as $nome) {
                $this->executarMiddleware($nome, $requisicao);
            }

            // CSRF em todo POST de navegação; APIs de sessão exigem header X-CSRF
            if ($requisicao->metodo === 'POST' && !$this->rotaIsentaDeCsrf($rota)) {
                $token = $requisicao->ehApi()
                    ? $requisicao->cabecalho('X-CSRF')
                    : $requisicao->post('_csrf');
                if (!Csrf::validar($token)) {
                    if ($requisicao->ehApi()) {
                        Resposta::erroJson('Token CSRF inválido.', 419);
                    }
                    // Envio maior que post_max_size: o PHP descarta o POST inteiro (inclusive o _csrf).
                    // Explica o motivo real em vez de "sessão expirou" e volta à página de origem.
                    if ($_POST === [] && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > self::limiteBytesIni('post_max_size')) {
                        Sessao::flash('erro', 'O arquivo enviado é maior que o limite do servidor ('
                            . ini_get('post_max_size') . '). Reduza o PDF e tente de novo.');
                        $origem = (string)($_SERVER['HTTP_REFERER'] ?? '');
                        $mesmaOrigem = $origem !== '' && parse_url($origem, PHP_URL_HOST) === (string)($_SERVER['HTTP_HOST'] ?? '');
                        Resposta::redirecionarCaminho($mesmaOrigem ? $origem : url('home'));
                    }
                    Sessao::flash('erro', 'Sua sessão expirou. Tente novamente.');
                    Resposta::redirecionarRota('login');
                }
            }

            $this->invocarAcao($rota->acao, $requisicao);
        } catch (\PDOException $excecao) {
            $this->tratarErroInterno($requisicao, $excecao, 'Falha de banco de dados.');
        } catch (\Throwable $excecao) {
            $this->tratarErroInterno($requisicao, $excecao, 'Erro interno.');
        }
    }

    private function executarMiddleware(string $nome, Requisicao $requisicao): void
    {
        if ($nome === 'autenticado') {
            (new ExigeAutenticacao())->tratar($requisicao);
            (new ExigeConsentimentoLgpd())->tratar($requisicao);
            return;
        }
        // Autenticado sem exigir termo LGPD (rotas do próprio aceite e logout)
        if ($nome === 'autenticado_basico') {
            (new ExigeAutenticacao())->tratar($requisicao);
            return;
        }
        if (str_starts_with($nome, 'papel:')) {
            $papeis = array_filter(explode(',', substr($nome, 6)));
            (new ExigePapel(...$papeis))->tratar($requisicao);
            return;
        }
        throw new \RuntimeException("Middleware desconhecido: {$nome}");
    }

    /** "8M" / "512K" / "1G" → bytes. */
    private static function limiteBytesIni(string $chave): int
    {
        $valor = trim((string)ini_get($chave));
        if ($valor === '' || $valor === '0' || $valor === '-1') {
            return PHP_INT_MAX;
        }
        $numero = (int)$valor;
        return match (strtoupper(substr($valor, -1))) {
            'G' => $numero * 1073741824, 'M' => $numero * 1048576, 'K' => $numero * 1024, default => $numero,
        };
    }

    /**
     * Webhooks e tarefas autenticam por token de serviço; o envio do pediatra,
     * pelo código de uso único (não há sessão de onde tirar CSRF).
     */
    private function rotaIsentaDeCsrf(Rota $rota): bool
    {
        return in_array($rota->nome, ['api.webhook.status', 'api.tarefas', 'api.consulta.enviar'], true);
    }

    private function invocarAcao(string $acao, Requisicao $requisicao): void
    {
        [$controlador, $metodo] = explode('@', $acao, 2);
        $classe = 'App\\Controllers\\' . $controlador;
        if (!class_exists($classe)) {
            throw new \RuntimeException("Controlador inexistente: {$classe}");
        }
        $instancia = new $classe();
        if (!method_exists($instancia, $metodo)) {
            throw new \RuntimeException("Ação inexistente: {$acao}");
        }
        $instancia->{$metodo}($requisicao);
    }

    private function tratarErroInterno(Requisicao $requisicao, \Throwable $excecao, string $mensagemPublica): never
    {
        error_log(sprintf(
            '[%s] %s em %s:%d',
            $excecao::class,
            $excecao->getMessage(),
            $excecao->getFile(),
            $excecao->getLine()
        ));
        if (Ambiente::ehDesenvolvimento()) {
            throw $excecao;
        }
        if ($requisicao->ehApi()) {
            Resposta::erroJson($mensagemPublica, 500);
        }
        Visao::exibir('erros/500', ['titulo' => 'Erro interno'], 'base', 500);
    }
}
