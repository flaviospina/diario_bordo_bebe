<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Leitor de PDF mínimo e sem dependências (hospedagem compartilhada, sem
 * Composer nem binários): valida a estrutura e extrai texto "best-effort"
 * de PDFs com fluxos FlateDecode e operadores Tj/TJ (caso dos contracheques
 * gerados pelo eSocial). Nunca executa nada do arquivo; quando o texto não
 * é legível (fontes com codificação própria, PDF só imagem), devolve vazio e
 * o preenchimento segue manual.
 */
final class LeitorPdf
{
    /** Tokens que indicam conteúdo ativo — recusados por segurança. */
    private const PERIGOSOS = ['/JavaScript', '/JS ', '/JS(', '/JS<', '/Launch', '/RichMedia', '/EmbeddedFile', '/OpenAction', '/AA '];

    /**
     * Valida um PDF: cabeçalho, marcador de fim, MIME real e ausência de conteúdo ativo.
     * @return ?string mensagem de erro (null = válido)
     */
    public static function validar(string $caminho, int $limiteBytes): ?string
    {
        if (!is_file($caminho)) {
            return 'Arquivo não recebido.';
        }
        $tamanho = (int)filesize($caminho);
        if ($tamanho <= 0) {
            return 'Arquivo vazio.';
        }
        if ($tamanho > $limiteBytes) {
            return 'Arquivo grande demais (limite de ' . (int)round($limiteBytes / 1048576) . ' MB).';
        }
        $conteudo = (string)file_get_contents($caminho);
        if (!str_starts_with($conteudo, '%PDF-1.') && !str_starts_with($conteudo, '%PDF-2.')) {
            return 'O arquivo não é um PDF válido (cabeçalho ausente).';
        }
        if (!str_contains(substr($conteudo, -2048), '%%EOF') && !str_contains($conteudo, '%%EOF')) {
            return 'PDF incompleto ou corrompido (marcador de fim ausente).';
        }
        if (!str_contains($conteudo, 'obj') || !str_contains($conteudo, 'endobj')) {
            return 'PDF corrompido (estrutura de objetos ausente).';
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($caminho);
        if ($mime !== 'application/pdf') {
            return 'O conteúdo real do arquivo não é PDF (' . $mime . ').';
        }
        foreach (self::PERIGOSOS as $token) {
            if (str_contains($conteudo, $token)) {
                return 'PDF com conteúdo ativo (scripts, anexos ou ações automáticas) não é aceito.';
            }
        }
        return null;
    }

    /** Extrai o texto legível (best-effort). Vazio quando o PDF não permite. */
    public static function extrairTexto(string $conteudo): string
    {
        $texto = '';
        // Fluxos de conteúdo: comprimidos (FlateDecode) ou em claro
        if (preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $conteudo, $fluxos) > 0) {
            foreach ($fluxos[1] as $fluxo) {
                $dados = @gzuncompress($fluxo);
                if ($dados === false) {
                    $dados = @gzinflate(substr($fluxo, 2));
                }
                if ($dados === false) {
                    $dados = $fluxo;
                }
                if (!str_contains($dados, 'Tj') && !str_contains($dados, 'TJ')) {
                    continue;
                }
                $texto .= self::textoDoFluxo($dados) . "\n";
            }
        }
        $texto = preg_replace('/[ \t]+/', ' ', $texto) ?? $texto;
        return trim($texto);
    }

    /** Converte os operadores de texto de um fluxo em linhas aproximadas. */
    private static function textoDoFluxo(string $dados): string
    {
        $saida = '';
        // TJ: [(abc) -200 (def)] TJ  — junta os pedaços; espaços grandes viram separador
        if (preg_match_all('/\[((?:[^\[\]]|\\\\[\[\]])*)\]\s*TJ|(\((?:[^()\\\\]|\\\\.)*\))\s*Tj|T\*|Td|TD|\'|"/s', $dados, $ops, PREG_SET_ORDER) > 0) {
            foreach ($ops as $op) {
                if (isset($op[2]) && $op[2] !== '') {
                    $saida .= self::decodificar($op[2]);
                    continue;
                }
                if (isset($op[1]) && $op[1] !== '') {
                    if (preg_match_all('/\((?:[^()\\\\]|\\\\.)*\)|(-?\d+(?:\.\d+)?)/', $op[1], $pedacos, PREG_SET_ORDER) > 0) {
                        foreach ($pedacos as $pedaco) {
                            if (isset($pedaco[1]) && $pedaco[1] !== '') {
                                if ((float)$pedaco[1] < -150) {
                                    $saida .= ' ';
                                }
                            } else {
                                $saida .= self::decodificar($pedaco[0]);
                            }
                        }
                    }
                    continue;
                }
                // Operadores de nova linha / deslocamento
                $saida .= "\n";
            }
        }
        return $saida;
    }

    /** Decodifica uma string literal PDF "(...)" com escapes. */
    private static function decodificar(string $literal): string
    {
        $s = substr($literal, 1, -1);
        $s = preg_replace_callback('/\\\\(\d{1,3})/', static fn(array $m): string => chr((int)octdec($m[1])), $s) ?? $s;
        $s = str_replace(['\\n', '\\r', '\\t', '\\(', '\\)', '\\\\'], ["\n", '', ' ', '(', ')', '\\'], $s);
        // PDFDoc/WinAnsi → UTF-8 (acentos do português)
        if (!mb_check_encoding($s, 'UTF-8')) {
            $s = mb_convert_encoding($s, 'UTF-8', 'Windows-1252');
        }
        return $s;
    }
}
