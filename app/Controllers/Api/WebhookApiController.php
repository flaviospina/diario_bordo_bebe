<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Ambiente;
use App\Core\Requisicao;
use App\Core\Resposta;
use App\Repositories\RepositorioFilaNotificacoes;

/**
 * Callback do n8n: confirma (ou não) a ENTREGA de cada notificação.
 * POST /api/webhook/status  { "notificacao_id": 1, "status": "entregue"|"falha", "erro": "..." }
 * Autenticado pelo mesmo segredo compartilhado (header X-Api-Key).
 */
final class WebhookApiController
{
    public function status(Requisicao $requisicao): void
    {
        $chaveEsperada = Ambiente::obter('N8N_API_KEY');
        if ($chaveEsperada === '' || !hash_equals($chaveEsperada, $requisicao->cabecalho('X-Api-Key'))) {
            Resposta::erroJson('Chave inválida.', 403);
        }

        $corpo = $requisicao->json();
        $id = (int)($corpo['notificacao_id'] ?? 0);
        $status = (string)($corpo['status'] ?? '');
        $fila = new RepositorioFilaNotificacoes();
        if ($id <= 0 || $fila->buscar($id) === null) {
            Resposta::erroJson('Notificação inexistente.', 404);
        }

        if ($status === 'entregue') {
            $fila->marcarEnviada($id);
        } elseif ($status === 'falha') {
            // Reabre com backoff: o n8n tentou entregar e não conseguiu
            $fila->registrarFalha($id, (string)($corpo['erro'] ?? 'Falha reportada pelo n8n.'));
        } else {
            Resposta::erroJson('Status desconhecido (use "entregue" ou "falha").', 422);
        }
        Resposta::json(['ok' => true]);
    }
}
