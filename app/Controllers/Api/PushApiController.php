<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Autenticacao;
use App\Core\Requisicao;
use App\Core\Resposta;
use App\Repositories\RepositorioPushInscricoes;
use App\Services\ServicoPush;

/**
 * Inscrição de Web Push do aparelho (sessão + header X-CSRF).
 *   POST /api/push/inscrever  {endpoint, keys:{p256dh, auth}}
 *   POST /api/push/remover    {endpoint}
 *   POST /api/push/testar     — manda uma notificação de teste para o próprio usuário
 */
final class PushApiController
{
    public function inscrever(Requisicao $requisicao): void
    {
        $dados = $requisicao->json();
        $endpoint = (string)($dados['endpoint'] ?? '');
        $p256dh = (string)($dados['keys']['p256dh'] ?? '');
        $auth = (string)($dados['keys']['auth'] ?? '');
        if (!str_starts_with($endpoint, 'https://') || $p256dh === '' || $auth === '') {
            Resposta::erroJson('Inscrição inválida.', 422);
        }
        (new RepositorioPushInscricoes())->salvar(
            Autenticacao::familiaId(),
            Autenticacao::id(),
            $endpoint,
            $p256dh,
            $auth,
            $requisicao->userAgent()
        );
        Resposta::json(['ok' => true]);
    }

    public function remover(Requisicao $requisicao): void
    {
        $endpoint = (string)($requisicao->json()['endpoint'] ?? '');
        if ($endpoint !== '') {
            (new RepositorioPushInscricoes())->removerPorEndpoint($endpoint);
        }
        Resposta::json(['ok' => true]);
    }

    public function testar(Requisicao $requisicao): void
    {
        $resultado = (new ServicoPush())->enviarParaUsuarios(
            [Autenticacao::id()],
            'Notificações ativas 🎉',
            'Este aparelho vai receber os avisos do Diário do Bebê.',
            url('avisos.index'),
            'teste'
        );
        Resposta::json(['ok' => $resultado['enviados'] > 0] + $resultado);
    }
}
