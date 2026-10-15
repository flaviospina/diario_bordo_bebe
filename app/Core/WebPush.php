<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Web Push em PHP puro (sem Composer): RFC 8291 (criptografia aes128gcm do
 * conteúdo) + RFC 8292 (VAPID, assinatura ES256). Precisa apenas da
 * extensão openssl (padrão na HostGator).
 *
 * Uso:
 *   [$publica, $privadaPem] = WebPush::gerarChavesVapid();
 *   $status = (new WebPush($publica, $privadaPem, 'mailto:...'))
 *       ->enviar($endpoint, $p256dh, $auth, json_encode([...]));
 */
final class WebPush
{
    public function __construct(
        private readonly string $vapidPublica,     // base64url, 65 bytes (ponto EC não comprimido)
        private readonly string $vapidPrivadaPem,  // chave privada EC P-256 em PEM
        private readonly string $assunto,          // mailto:... ou https://...
        private readonly int $ttl = 86400,
    ) {
    }

    // ── VAPID: geração de chaves ──────────────────────────────

    /** @return array{0:string,1:string} [chave pública base64url, chave privada PEM] */
    public static function gerarChavesVapid(): array
    {
        $chave = openssl_pkey_new(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]);
        if ($chave === false || !openssl_pkey_export($chave, $privadaPem)) {
            throw new RuntimeException('Não foi possível gerar as chaves VAPID (openssl).');
        }
        return [self::base64url(self::pontoPublico($chave)), (string)$privadaPem];
    }

    // ── Envio ─────────────────────────────────────────────────

    /**
     * Envia a notificação. Devolve o status HTTP do serviço de push:
     * 201 = entregue ao serviço; 404/410 = inscrição morta (apagar).
     */
    public function enviar(string $endpoint, string $p256dh, string $auth, string $payload, ?array $teste = null): int
    {
        $corpo = self::criptografar($payload, $p256dh, $auth, $teste);
        $cabecalhos = [
            'Content-Type: application/octet-stream',
            'Content-Encoding: aes128gcm',
            'TTL: ' . $this->ttl,
            'Urgency: normal',
            'Authorization: vapid t=' . $this->tokenVapid($endpoint) . ', k=' . $this->vapidPublica,
            'Content-Length: ' . strlen($corpo),
        ];

        $curl = curl_init($endpoint);
        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $corpo,
            CURLOPT_HTTPHEADER => $cabecalhos,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);
        $resposta = curl_exec($curl);
        $status = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $erro = curl_error($curl);
        curl_close($curl);
        if ($resposta === false) {
            throw new RuntimeException('Serviço de push inacessível: ' . ($erro ?: 'falha de rede'));
        }
        return $status;
    }

    // ── RFC 8291: criptografia do conteúdo ────────────────────

    /**
     * Corpo aes128gcm completo (salt | rs | idlen | chave pública | cifra).
     * $teste permite fixar salt e chave efêmera para o vetor de teste da RFC.
     */
    public static function criptografar(string $payload, string $p256dhB64, string $authB64, ?array $teste = null): string
    {
        $uaPublica = self::base64urlDecodificar($p256dhB64);
        $authSecret = self::base64urlDecodificar($authB64);
        if (strlen($uaPublica) !== 65 || strlen($authSecret) !== 16) {
            throw new RuntimeException('Inscrição de push inválida (chaves com tamanho inesperado).');
        }

        // Par efêmero do servidor (as) e segredo ECDH com a chave do navegador (ua)
        $chaveLocal = $teste['chave_privada_pem'] ?? null
            ? openssl_pkey_get_private($teste['chave_privada_pem'])
            : openssl_pkey_new(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]);
        if ($chaveLocal === false) {
            throw new RuntimeException('Falha ao preparar a chave efêmera (openssl).');
        }
        $asPublica = self::pontoPublico($chaveLocal);
        $segredoEcdh = openssl_pkey_derive(self::pemDePonto($uaPublica), $chaveLocal, 32);
        if ($segredoEcdh === false) {
            throw new RuntimeException('Falha no acordo de chaves ECDH (openssl).');
        }

        $salt = $teste['salt'] ?? random_bytes(16);

        // HKDF em duas etapas (RFC 8291 §3.3 / §3.4)
        $ikm = hash_hkdf('sha256', $segredoEcdh, 32, "WebPush: info\0" . $uaPublica . $asPublica, $authSecret);
        $cek = hash_hkdf('sha256', $ikm, 16, "Content-Encoding: aes128gcm\0", $salt);
        $nonce = hash_hkdf('sha256', $ikm, 12, "Content-Encoding: nonce\0", $salt);

        // Único registro: payload + delimitador 0x02 (último registro)
        $tag = '';
        $cifra = openssl_encrypt($payload . "\x02", 'aes-128-gcm', $cek, OPENSSL_RAW_DATA, $nonce, $tag, '', 16);
        if ($cifra === false) {
            throw new RuntimeException('Falha ao cifrar o conteúdo (aes-128-gcm).');
        }

        // Cabeçalho do fluxo: salt(16) | rs(4, uint32) | idlen(1) | keyid(65)
        return $salt . pack('N', 4096) . chr(65) . $asPublica . $cifra . $tag;
    }

    // ── RFC 8292: token VAPID (JWT ES256) ─────────────────────

    private function tokenVapid(string $endpoint): string
    {
        $partes = parse_url($endpoint);
        $audiencia = ($partes['scheme'] ?? 'https') . '://' . ($partes['host'] ?? '');
        $cabecalho = self::base64url(json_encode(['typ' => 'JWT', 'alg' => 'ES256'], JSON_UNESCAPED_SLASHES) ?: '');
        $corpo = self::base64url(json_encode([
            'aud' => $audiencia,
            'exp' => time() + 12 * 3600,
            'sub' => $this->assunto,
        ], JSON_UNESCAPED_SLASHES) ?: '');
        $dados = $cabecalho . '.' . $corpo;

        $chave = openssl_pkey_get_private($this->vapidPrivadaPem);
        if ($chave === false || !openssl_sign($dados, $assinaturaDer, $chave, OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('Falha ao assinar o token VAPID.');
        }
        return $dados . '.' . self::base64url(self::derParaRaw($assinaturaDer));
    }

    /** Assinatura ECDSA DER (SEQUENCE{r,s}) → 64 bytes r||s, como o JWT exige. */
    private static function derParaRaw(string $der): string
    {
        $posicao = 2; // pula SEQUENCE + comprimento
        if (ord($der[1]) & 0x80) {
            $posicao += ord($der[1]) & 0x7F;
        }
        $inteiros = [];
        for ($i = 0; $i < 2; $i++) {
            $posicao++; // tag INTEGER
            $tamanho = ord($der[$posicao++]);
            $valor = substr($der, $posicao, $tamanho);
            $posicao += $tamanho;
            $valor = ltrim($valor, "\x00");
            $inteiros[] = str_pad($valor, 32, "\x00", STR_PAD_LEFT);
        }
        return $inteiros[0] . $inteiros[1];
    }

    // ── Utilidades EC ─────────────────────────────────────────

    /** Ponto público não comprimido (0x04 | x | y), 65 bytes. */
    private static function pontoPublico(\OpenSSLAsymmetricKey $chave): string
    {
        $detalhes = openssl_pkey_get_details($chave);
        if ($detalhes === false || !isset($detalhes['ec']['x'], $detalhes['ec']['y'])) {
            throw new RuntimeException('Chave EC sem coordenadas públicas.');
        }
        return "\x04"
            . str_pad($detalhes['ec']['x'], 32, "\x00", STR_PAD_LEFT)
            . str_pad($detalhes['ec']['y'], 32, "\x00", STR_PAD_LEFT);
    }

    /** PEM SubjectPublicKeyInfo a partir de um ponto P-256 bruto de 65 bytes. */
    private static function pemDePonto(string $ponto): string
    {
        $der = hex2bin('3059301306072a8648ce3d020106082a8648ce3d030107034200') . $ponto;
        return "-----BEGIN PUBLIC KEY-----\n" . chunk_split(base64_encode($der), 64, "\n") . "-----END PUBLIC KEY-----\n";
    }

    public static function base64url(string $dados): string
    {
        return rtrim(strtr(base64_encode($dados), '+/', '-_'), '=');
    }

    public static function base64urlDecodificar(string $texto): string
    {
        $texto = strtr($texto, '-_', '+/');
        return (string)base64_decode(str_pad($texto, strlen($texto) + (4 - strlen($texto) % 4) % 4, '='), true);
    }
}
