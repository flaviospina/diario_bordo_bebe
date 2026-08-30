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

    /** Webhooks e tarefas agendadas autenticam por token de serviço, não por sessão. */
    private function rotaIsentaDeCsrf(Rota $rota): bool
    {
        return in_array($rota->nome, ['api.webhook.status', 'api.tarefas'], true);
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
