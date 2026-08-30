<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Ambiente;
use App\Core\Autenticacao;
use App\Repositories\RepositorioFilaNotificacoes;
use App\Repositories\RepositorioIntercorrencias;
use App\Repositories\RepositorioUsuariosFamilia;

/**
 * Notificações (seção 9): o PHP NUNCA fala com a Evolution API diretamente.
 * Ele grava em fila_notificacoes e chama o webhook do n8n, que entrega
 * WhatsApp/e-mail e confirma via /api/webhook/status.
 */
final class ServicoNotificacoes
{
    public function __construct(
        private readonly RepositorioFilaNotificacoes $fila = new RepositorioFilaNotificacoes(),
    ) {
    }

    /**
     * Enfileira uma mensagem para todos os responsáveis da família nos canais
     * pedidos e tenta o disparo imediato quando $imediato = true.
     * @param string[] $canais
     */
    public function enfileirarParaResponsaveis(
        int $familiaId,
        string $evento,
        string $titulo,
        string $mensagem,
        array $dados = [],
        array $canais = ['whatsapp', 'email'],
        bool $imediato = false
    ): void {
        $responsaveis = (new RepositorioUsuariosFamilia($familiaId))->responsaveisParaNotificar();
        foreach ($responsaveis as $responsavel) {
            foreach ($canais as $canal) {
                $destinatario = $canal === 'whatsapp'
                    ? (string)($responsavel['telefone_whatsapp'] ?? '')
                    : (string)$responsavel['email'];
                if ($destinatario === '' || $canal === 'push') {
                    continue; // push chega numa fase futura; sem telefone não há WhatsApp
                }
                $this->fila->enfileirar($familiaId, $evento, $canal, $destinatario, [
                    'titulo' => $titulo,
                    'mensagem' => $mensagem,
                    'nome_destinatario' => (string)$responsavel['nome'],
                    'dados' => $dados,
                ]);
            }
        }
        if ($imediato) {
            $this->dispararPendentes();
        }
    }

    /** Intercorrência: grave dispara em todos os canais, na hora (regra 8.6). */
    public function notificarIntercorrencia(string $codigo, string $gravidade): void
    {
        $familiaId = Autenticacao::familiaId();
        if ($familiaId <= 0) {
            return;
        }
        $intercorrencia = (new RepositorioIntercorrencias($familiaId))->buscarPorCodigo($codigo);
        if ($intercorrencia === null) {
            return;
        }
        $rotulo = ['leve' => 'leve', 'moderada' => 'MODERADA', 'grave' => 'GRAVE'][$gravidade] ?? $gravidade;
        $mensagem = "Intercorrência {$rotulo} com {$intercorrencia['crianca_nome']} às "
            . data_br((string)$intercorrencia['ocorrido_em'], 'H:i') . ".\n"
            . $intercorrencia['descricao'] . "\n"
            . ($intercorrencia['acao_tomada'] !== null ? 'O que foi feito: ' . $intercorrencia['acao_tomada'] . "\n" : '')
            . 'Confirme a ciência em: ' . url_absoluta('intercorrencia.ver', ['codigo' => $codigo]);

        $this->enfileirarParaResponsaveis(
            $familiaId,
            'intercorrencia',
            'Intercorrência ' . $rotulo . ' — Diário do Bebê',
            $mensagem,
            ['codigo' => $codigo, 'gravidade' => $gravidade],
            ['whatsapp', 'email'],
            $gravidade === 'grave' // grave: independe das configurações de resumo, na hora
        );
    }

    public function notificarSolicitacao(int $familiaId, string $codigoSolicitacao, string $solicitanteNome): void
    {
        $this->enfileirarParaResponsaveis(
            $familiaId,
            'solicitacao_edicao',
            'Solicitação de alteração — Diário do Bebê',
            $solicitanteNome . ' pediu uma alteração em um registro. Aprove ou recuse em: '
            . url_absoluta('solicitacoes.decidir', ['codigo' => $codigoSolicitacao]),
            ['codigo' => $codigoSolicitacao]
        );
    }

    public function notificarSuprimento(int $familiaId, string $item, string $nivel): void
    {
        $this->enfileirarParaResponsaveis(
            $familiaId,
            'suprimento_baixo',
            'Suprimento em falta — Diário do Bebê',
            $nivel === 'acabou'
                ? "Acabou: {$item}. Veja a lista em " . url_absoluta('suprimentos.index')
                : "Está acabando: {$item}.",
            ['item' => $item, 'nivel' => $nivel]
        );
    }

    // ── Despacho para o n8n ───────────────────────────────────

    /**
     * Envia as notificações pendentes ao webhook do n8n.
     * @return array{enviadas:int, falhas:int, sem_webhook:bool}
     */
    public function dispararPendentes(): array
    {
        $webhook = Ambiente::obter('N8N_WEBHOOK_URL');
        if ($webhook === '') {
            // Sem n8n configurado a fila apenas acumula (nada é perdido)
            return ['enviadas' => 0, 'falhas' => 0, 'sem_webhook' => true];
        }
        $enviadas = 0;
        $falhas = 0;
        foreach ($this->fila->pendentesParaEnvio() as $notificacao) {
            $this->fila->marcarEnviando((int)$notificacao['id']);
            $payload = json_decode((string)$notificacao['payload'], true) ?: [];
            $corpo = [
                'notificacao_id' => (int)$notificacao['id'],
                'evento' => (string)$notificacao['evento'],
                'familia_id' => (int)$notificacao['familia_id'],
                'canal' => (string)$notificacao['canal'],
                'destinatarios' => [(string)$notificacao['destinatario']],
                'titulo' => (string)($payload['titulo'] ?? ''),
                'mensagem' => (string)($payload['mensagem'] ?? ''),
                'dados' => $payload['dados'] ?? [],
            ];
            $erro = $this->postarWebhook($webhook, $corpo);
            if ($erro === null) {
                // O n8n confirma a ENTREGA via /api/webhook/status; aqui marcamos
                // como enviada ao webhook (se a entrega falhar, o callback reabre).
                $this->fila->marcarEnviada((int)$notificacao['id']);
                $enviadas++;
            } else {
                $this->fila->registrarFalha((int)$notificacao['id'], $erro);
                $falhas++;
            }
        }
        return ['enviadas' => $enviadas, 'falhas' => $falhas, 'sem_webhook' => false];
    }

    /** @return ?string mensagem de erro (null = HTTP 2xx) */
    private function postarWebhook(string $url, array $corpo): ?string
    {
        $contexto = stream_context_create(['http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\n"
                . 'X-Api-Key: ' . Ambiente::obter('N8N_API_KEY') . "\r\n",
            'content' => json_encode($corpo, JSON_UNESCAPED_UNICODE),
            'timeout' => 10,
            'ignore_errors' => true,
        ]]);
        $resposta = @file_get_contents($url, false, $contexto);
        $status = 0;
        foreach ($http_response_header ?? [] as $cabecalho) {
            if (preg_match('#^HTTP/\S+\s+(\d{3})#', $cabecalho, $m) === 1) {
                $status = (int)$m[1];
            }
        }
        if ($resposta === false || $status < 200 || $status >= 300) {
            return 'Webhook respondeu HTTP ' . $status;
        }
        return null;
    }
}
