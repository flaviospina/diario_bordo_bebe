<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Ambiente;

/**
 * Envio de e-mail transacional via mail() do PHP (disponível na HostGator).
 * Os envios em massa/notificações de rotina NÃO passam por aqui — vão para a
 * fila_notificacoes e são entregues pelo n8n (Fase 5). Aqui ficam apenas os
 * e-mails de conta: redefinição de senha e convite.
 */
final class ServicoEmail
{
    public function enviar(string $destinatario, string $assunto, string $corpoTexto): bool
    {
        $remetente = Ambiente::obter('EMAIL_REMETENTE', 'nao-responda@localhost');
        $nome = Ambiente::obter('EMAIL_NOME_REMETENTE', 'Diário do Bebê');

        $cabecalhos = [
            'From: ' . mb_encode_mimeheader($nome, 'UTF-8') . ' <' . $remetente . '>',
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
        ];

        $ok = @mail(
            $destinatario,
            mb_encode_mimeheader($assunto, 'UTF-8'),
            $corpoTexto,
            implode("\r\n", $cabecalhos)
        );

        if (!$ok) {
            error_log('Falha ao enviar e-mail para ' . $destinatario . ' (assunto: ' . $assunto . ')');
        }
        return $ok;
    }
}
