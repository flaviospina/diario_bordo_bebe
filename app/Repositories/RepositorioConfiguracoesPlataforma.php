<?php

declare(strict_types=1);

namespace App\Repositories;

/**
 * Configurações da plataforma (chave/valor) — editáveis pelo super_admin no
 * Painel. Hoje: contatos de suporte (WhatsApp e e-mail) usados nos e-mails
 * automáticos de engajamento.
 */
final class RepositorioConfiguracoesPlataforma extends RepositorioSistema
{
    public function obter(string $chave, string $padrao = ''): string
    {
        $linha = $this->buscarUm(
            'SELECT valor FROM configuracoes_plataforma WHERE chave = :chave LIMIT 1',
            ['chave' => $chave]
        );
        $valor = $linha !== null ? trim((string)($linha['valor'] ?? '')) : '';
        return $valor !== '' ? $valor : $padrao;
    }

    public function salvar(string $chave, string $valor): void
    {
        $this->executar(
            'INSERT INTO configuracoes_plataforma (chave, valor) VALUES (:chave, :valor)
             ON DUPLICATE KEY UPDATE valor = VALUES(valor)',
            ['chave' => $chave, 'valor' => $valor]
        );
    }

    // ── Segredos (senha do SMTP): AES-256-GCM com a chave do .env ──

    public function salvarSegredo(string $chave, string $valor): void
    {
        if ($valor === '') {
            $this->salvar($chave, '');
            return;
        }
        $iv = random_bytes(12);
        $tag = '';
        $cifra = openssl_encrypt($valor, 'aes-256-gcm', self::chaveCripto(), OPENSSL_RAW_DATA, $iv, $tag, '', 16);
        $this->salvar($chave, 'enc:' . base64_encode($iv . $tag . (string)$cifra));
    }

    public function obterSegredo(string $chave): string
    {
        $bruto = $this->obter($chave);
        if (!str_starts_with($bruto, 'enc:')) {
            return $bruto;
        }
        $dados = base64_decode(substr($bruto, 4), true) ?: '';
        if (strlen($dados) < 29) {
            return '';
        }
        $texto = openssl_decrypt(substr($dados, 28), 'aes-256-gcm', self::chaveCripto(), OPENSSL_RAW_DATA, substr($dados, 0, 12), substr($dados, 12, 16));
        return $texto === false ? '' : $texto;
    }

    private static function chaveCripto(): string
    {
        $semente = \App\Core\Ambiente::obter('APP_CHAVE_SECRETA', '') ?: \App\Core\Ambiente::obter('BD_SENHA', 'diario-do-bebe');
        return hash('sha256', 'config-plataforma|' . $semente, true);
    }

    /**
     * Link wa.me a partir do número salvo: aceita "11 99335-8259",
     * "(11) 99335-8259" ou "5511993358259" e devolve null sem número.
     */
    public function linkWhatsappSuporte(?string $mensagem = null): ?string
    {
        $digitos = preg_replace('/\D+/', '', $this->obter('whatsapp_suporte'));
        if ($digitos === '' || strlen($digitos) < 10) {
            return null;
        }
        if (!str_starts_with($digitos, '55')) {
            $digitos = '55' . $digitos;
        }
        return 'https://wa.me/' . $digitos
            . ($mensagem !== null ? '?text=' . rawurlencode($mensagem) : '');
    }
}
