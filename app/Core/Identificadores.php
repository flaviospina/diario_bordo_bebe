<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Identificadores públicos e slugs.
 *
 * Regra de produto: nunca expor ID sequencial do banco em URL. Toda entidade
 * visível recebe um codigo_publico curto, aleatório e NÃO sequencial.
 * Tudo é gerado em minúsculas — pré-requisito do redirecionamento 301 canônico.
 */
final class Identificadores
{
    /** Alfabeto Crockford base32 minúsculo, sem caracteres ambíguos (i, l, o, u). */
    private const ALFABETO = '0123456789abcdefghjkmnpqrstvwxyz';

    /** Código público de 12 caracteres (~60 bits de aleatoriedade). */
    public static function codigoPublico(int $tamanho = 12): string
    {
        $codigo = '';
        $maximo = strlen(self::ALFABETO) - 1;
        for ($i = 0; $i < $tamanho; $i++) {
            $codigo .= self::ALFABETO[random_int(0, $maximo)];
        }
        return $codigo;
    }

    /** UUID v4 (usado como uuid_cliente quando o registro nasce no servidor). */
    public static function uuidV4(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }

    /** Token opaco para links (convite, redefinição de senha) — hex minúsculo. */
    public static function token(int $bytes = 32): string
    {
        return bin2hex(random_bytes($bytes));
    }

    /** Hash de token para armazenamento (o token puro só existe no link enviado). */
    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    /**
     * Slug a partir de um nome: minúsculas, acentos convertidos, hífens.
     * Colisões são resolvidas pelo chamador com sufixo numérico (-2, -3...).
     */
    public static function slug(string $texto): string
    {
        $texto = mb_strtolower(trim($texto));
        $mapa = [
            'á'=>'a','à'=>'a','ã'=>'a','â'=>'a','ä'=>'a',
            'é'=>'e','è'=>'e','ê'=>'e','ë'=>'e',
            'í'=>'i','ì'=>'i','î'=>'i','ï'=>'i',
            'ó'=>'o','ò'=>'o','õ'=>'o','ô'=>'o','ö'=>'o',
            'ú'=>'u','ù'=>'u','û'=>'u','ü'=>'u',
            'ç'=>'c','ñ'=>'n',
        ];
        $texto = strtr($texto, $mapa);
        $texto = preg_replace('/[^a-z0-9]+/', '-', $texto) ?? '';
        $texto = trim($texto, '-');
        return $texto !== '' ? mb_substr($texto, 0, 70) : 'item';
    }
}
