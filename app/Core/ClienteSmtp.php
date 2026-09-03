<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Cliente SMTP mínimo e sem dependências (padrão do projeto: nada de Composer).
 * Suporta SSL implícito (porta 465, padrão da HostGator) e STARTTLS (587),
 * com AUTH LOGIN. Usado pelo ServicoEmail quando SMTP_HOST está configurado —
 * entrega muito mais confiável que o mail() puro (SPF/DKIM da conta valem).
 */
final class ClienteSmtp
{
    /** @var resource|null */
    private $conexao = null;

    public function __construct(
        private readonly string $host,
        private readonly int $porta,
        private readonly string $usuario,
        private readonly string $senha,
        private readonly string $seguranca = 'ssl', // ssl (465) | tls (587 STARTTLS)
        private readonly int $tempoLimite = 15,
    ) {
    }

    /**
     * Envia uma mensagem já montada (cabeçalhos + corpo MIME).
     * @throws \RuntimeException com o diálogo SMTP em caso de falha
     */
    public function enviar(string $de, string $para, string $mensagem): void
    {
        $endereco = ($this->seguranca === 'ssl' ? 'ssl://' : 'tcp://') . $this->host . ':' . $this->porta;
        $contexto = stream_context_create(['ssl' => ['SNI_enabled' => true]]);
        $this->conexao = @stream_socket_client(
            $endereco, $codigoErro, $mensagemErro, $this->tempoLimite,
            STREAM_CLIENT_CONNECT, $contexto
        );
        if ($this->conexao === false) {
            $this->conexao = null;
            throw new \RuntimeException("SMTP: não conectou em {$endereco} ({$mensagemErro})");
        }
        stream_set_timeout($this->conexao, $this->tempoLimite);

        try {
            $this->esperar(220);
            $this->comando('EHLO ' . gethostname(), 250);

            if ($this->seguranca === 'tls') {
                $this->comando('STARTTLS', 220);
                if (!stream_socket_enable_crypto($this->conexao, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new \RuntimeException('SMTP: falha ao ativar TLS');
                }
                $this->comando('EHLO ' . gethostname(), 250);
            }

            $this->comando('AUTH LOGIN', 334);
            $this->comando(base64_encode($this->usuario), 334);
            $this->comando(base64_encode($this->senha), 235);

            $this->comando('MAIL FROM:<' . $de . '>', 250);
            $this->comando('RCPT TO:<' . $para . '>', 250);
            $this->comando('DATA', 354);

            // Escapa linhas iniciadas por "." (RFC 5321) e finaliza
            $corpo = preg_replace('/^\./m', '..', $mensagem);
            fwrite($this->conexao, $corpo . "\r\n.\r\n");
            $this->esperar(250);

            $this->comando('QUIT', 221);
        } finally {
            if ($this->conexao !== null) {
                fclose($this->conexao);
                $this->conexao = null;
            }
        }
    }

    private function comando(string $linha, int $codigoEsperado): void
    {
        fwrite($this->conexao, $linha . "\r\n");
        $this->esperar($codigoEsperado);
    }

    private function esperar(int $codigoEsperado): void
    {
        $resposta = '';
        while (($linha = fgets($this->conexao, 512)) !== false) {
            $resposta .= $linha;
            // Linhas de continuação vêm como "250-...", a última como "250 ..."
            if (isset($linha[3]) && $linha[3] === ' ') {
                break;
            }
        }
        $codigo = (int)substr($resposta, 0, 3);
        if ($codigo !== $codigoEsperado) {
            throw new \RuntimeException('SMTP esperava ' . $codigoEsperado . ', recebeu: ' . trim($resposta));
        }
    }
}
