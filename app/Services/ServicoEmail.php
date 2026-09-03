<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Ambiente;
use App\Core\ClienteSmtp;
use App\Repositories\RepositorioEmails;

/**
 * E-mail transacional do sistema, com três transportes (nesta ordem):
 *   1. SMTP autenticado (SMTP_HOST no .env) — recomendado: usa a conta do
 *      domínio, então SPF/DKIM valem e o e-mail não cai em spam;
 *   2. mail() do PHP — fallback da hospedagem;
 *   3. arquivo em storage/logs/emails/ — só em desenvolvimento sem SMTP.
 * TODO envio (sucesso ou falha) fica em `emails_enviados` (painel do admin).
 */
final class ServicoEmail
{
    /** Compatibilidade: e-mails simples em texto (senha, convite de usuário). */
    public function enviar(string $destinatario, string $assunto, string $corpoTexto): bool
    {
        return $this->enviarHtml(
            $destinatario,
            $assunto,
            '<p style="margin:0 0 12px; white-space:pre-line;">' . nl2br(e($corpoTexto)) . '</p>',
            $corpoTexto,
            'transacional'
        );
    }

    /**
     * Envia HTML (com alternativa em texto) e registra no relatório.
     */
    public function enviarHtml(
        string $destinatario,
        string $assunto,
        string $conteudoHtml,
        string $corpoTexto,
        string $tipo,
        ?int $referenciaId = null,
        ?int $familiaId = null
    ): bool {
        $remetente = Ambiente::obter('EMAIL_REMETENTE', 'nao-responda@localhost');
        $nomeRemetente = Ambiente::obter('EMAIL_NOME_REMETENTE', 'Diário do Bebê');
        $html = self::layout($assunto, $conteudoHtml);

        $limite = "=_diariobebe_" . bin2hex(random_bytes(12));
        $cabecalhos = [
            'From: ' . mb_encode_mimeheader($nomeRemetente, 'UTF-8') . ' <' . $remetente . '>',
            'Reply-To: ' . $remetente,
            'MIME-Version: 1.0',
            'Content-Type: multipart/alternative; boundary="' . $limite . '"',
        ];
        $corpoMime = "--{$limite}\r\n"
            . "Content-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit\r\n\r\n"
            . $corpoTexto . "\r\n"
            . "--{$limite}\r\n"
            . "Content-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit\r\n\r\n"
            . $html . "\r\n"
            . "--{$limite}--";

        $erro = null;
        $ok = false;
        $hostSmtp = Ambiente::obter('SMTP_HOST', '');
        try {
            if ($hostSmtp !== '') {
                $smtp = new ClienteSmtp(
                    $hostSmtp,
                    (int)Ambiente::obter('SMTP_PORTA', '465'),
                    Ambiente::obter('SMTP_USUARIO', $remetente),
                    Ambiente::obter('SMTP_SENHA', ''),
                    Ambiente::obter('SMTP_SEGURANCA', 'ssl')
                );
                $mensagem = 'To: ' . $destinatario . "\r\n"
                    . 'Subject: ' . mb_encode_mimeheader($assunto, 'UTF-8') . "\r\n"
                    . implode("\r\n", $cabecalhos) . "\r\n\r\n" . $corpoMime;
                $smtp->enviar($remetente, $destinatario, $mensagem);
                $ok = true;
            } elseif (\App\Core\Ambiente::ehDesenvolvimento()) {
                // Dev sem SMTP: grava o e-mail em arquivo para conferência visual
                $pasta = STORAGE_PATH . '/logs/emails';
                if (!is_dir($pasta)) {
                    mkdir($pasta, 0755, true);
                }
                file_put_contents(
                    $pasta . '/' . date('Ymd-His') . '-' . preg_replace('/[^a-z0-9]/', '-', mb_strtolower($destinatario)) . '.html',
                    $html
                );
                $ok = true;
                $erro = 'simulado (dev sem SMTP)';
            } else {
                $ok = @mail(
                    $destinatario,
                    mb_encode_mimeheader($assunto, 'UTF-8'),
                    $corpoMime,
                    implode("\r\n", $cabecalhos)
                );
                if (!$ok) {
                    $erro = 'mail() retornou falso';
                }
            }
        } catch (\Throwable $excecao) {
            $erro = $excecao->getMessage();
            error_log('Falha de e-mail para ' . $destinatario . ': ' . $erro);
        }

        try {
            (new RepositorioEmails())->registrar($destinatario, $assunto, $tipo, $ok, $erro, $referenciaId, $familiaId);
        } catch (\Throwable) {
            // O relatório nunca pode derrubar o envio
        }
        return $ok;
    }

    /**
     * Moldura HTML minimalista no padrão do app (CSS inline — regra de e-mail).
     */
    public static function layout(string $titulo, string $conteudoHtml): string
    {
        $ano = date('Y');
        return <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<body style="margin:0; padding:0; background-color:#FAF6F0;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#FAF6F0; padding:28px 12px;">
<tr><td align="center">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
         style="max-width:520px; background-color:#FFFFFF; border-radius:18px; border:1px solid #EDE6DB;
                font-family:-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif; color:#33302B;">
    <tr><td style="padding:26px 28px 8px;">
      <div style="font-size:20px; font-weight:800; letter-spacing:-.3px;">
        <span style="display:inline-block; width:14px; height:14px; border-radius:50%; background:#4F837C; margin-right:8px;"></span>diário
        <span style="color:#A8A296; font-weight:500;">do</span> bebê
      </div>
    </td></tr>
    <tr><td style="padding:10px 28px 4px; font-size:15px; line-height:1.55; color:#33302B;">
      {$conteudoHtml}
    </td></tr>
    <tr><td style="padding:14px 28px 24px; font-size:12px; color:#A8A296; line-height:1.5;">
      Você recebeu este e-mail porque faz parte do Diário do Bebê.<br>
      Sem anúncios, sem rastreadores — os dados são da família, sempre. · {$ano}
    </td></tr>
  </table>
</td></tr>
</table>
</body>
</html>
HTML;
    }

    /** Botão de ação no padrão do app (para usar dentro do conteúdo). */
    public static function botao(string $texto, string $url): string
    {
        $textoSeguro = e($texto);
        $urlSegura = e($url);
        return '<p style="margin:20px 0; text-align:center;">'
            . '<a href="' . $urlSegura . '" style="display:inline-block; background-color:#4F837C; color:#FFFFFF; '
            . 'text-decoration:none; font-weight:700; font-size:15px; padding:13px 30px; border-radius:999px;">'
            . $textoSeguro . '</a></p>'
            . '<p style="margin:0 0 8px; font-size:12px; color:#A8A296; text-align:center;">'
            . 'Se o botão não funcionar, copie e cole este link:<br>'
            . '<span style="word-break:break-all;">' . $urlSegura . '</span></p>';
    }
}
