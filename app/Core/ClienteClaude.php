<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Cliente mínimo da API da Anthropic (Claude), em HTTP puro — sem Composer,
 * como todo o projeto. Uma única operação: enviar uma instrução de sistema e
 * uma mensagem, receber o texto da resposta.
 *
 * A chave fica SÓ no .env (ANTHROPIC_API_KEY) e nunca aparece em log ou erro.
 */
final class ClienteClaude
{
    private const URL = 'https://api.anthropic.com/v1/messages';
    private const VERSAO_API = '2023-06-01';

    public function __construct(
        private readonly string $chave,
        private readonly string $modelo = 'claude-opus-5',
        private readonly int $tempoLimite = 90,
    ) {
    }

    /** null quando a chave não está configurada no .env — quem chama decide o fallback. */
    public static function daConfiguracao(): ?self
    {
        $chave = Ambiente::obter('ANTHROPIC_API_KEY', '');
        if (trim($chave) === '') {
            return null;
        }
        return new self(trim($chave), Ambiente::obter('IA_MODELO', 'claude-opus-5'));
    }

    public function modelo(): string
    {
        return $this->modelo;
    }

    /**
     * Envia a pergunta e devolve o texto da resposta (blocos "thinking" são
     * descartados). Lança RuntimeException com mensagem SEGURA em caso de falha.
     */
    public function perguntar(string $instrucaoDeSistema, string $mensagem, int $maxTokens = 3000): string
    {
        $corpo = json_encode([
            'model' => $this->modelo,
            'max_tokens' => $maxTokens,
            'system' => $instrucaoDeSistema,
            'messages' => [
                ['role' => 'user', 'content' => $mensagem],
            ],
        ], JSON_UNESCAPED_UNICODE);
        if ($corpo === false) {
            throw new RuntimeException('Não foi possível montar a requisição para a IA.');
        }

        [$status, $resposta] = function_exists('curl_init')
            ? $this->enviarComCurl($corpo)
            : $this->enviarComStream($corpo);

        $json = json_decode($resposta, true);
        if (!is_array($json)) {
            throw new RuntimeException('A IA respondeu em um formato inesperado (HTTP ' . $status . ').');
        }
        if ($status >= 400 || ($json['type'] ?? '') === 'error') {
            $tipoErro = (string)($json['error']['type'] ?? 'erro');
            // Mensagens amigáveis para os erros mais comuns — sem vazar detalhes
            $mensagemErro = match ($tipoErro) {
                'authentication_error' => 'A chave da API da IA é inválida. Confira ANTHROPIC_API_KEY no .env.',
                'rate_limit_error' => 'A IA está com muitas requisições agora. Tente de novo em alguns minutos.',
                'overloaded_error' => 'O serviço de IA está sobrecarregado. Tente de novo em alguns minutos.',
                'invalid_request_error' => 'A requisição à IA foi recusada: '
                    . mb_substr((string)($json['error']['message'] ?? ''), 0, 200),
                default => 'O serviço de IA falhou (HTTP ' . $status . ', ' . $tipoErro . ').',
            };
            throw new RuntimeException($mensagemErro);
        }

        $texto = '';
        foreach (($json['content'] ?? []) as $bloco) {
            if (($bloco['type'] ?? '') === 'text') {
                $texto .= (string)($bloco['text'] ?? '');
            }
        }
        if (trim($texto) === '') {
            throw new RuntimeException('A IA devolveu uma resposta vazia.');
        }
        return $texto;
    }

    /** @return array{0:int,1:string} status HTTP e corpo */
    private function enviarComCurl(string $corpo): array
    {
        $curl = curl_init(self::URL);
        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $corpo,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->tempoLimite,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_HTTPHEADER => [
                'x-api-key: ' . $this->chave,
                'anthropic-version: ' . self::VERSAO_API,
                'content-type: application/json',
            ],
        ]);
        $resposta = curl_exec($curl);
        $status = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $erroRede = curl_error($curl);
        curl_close($curl);
        if ($resposta === false) {
            throw new RuntimeException('Sem conexão com o serviço de IA: ' . ($erroRede ?: 'falha de rede'));
        }
        return [$status, (string)$resposta];
    }

    /** @return array{0:int,1:string} fallback sem extensão curl */
    private function enviarComStream(string $corpo): array
    {
        $contexto = stream_context_create(['http' => [
            'method' => 'POST',
            'header' => 'x-api-key: ' . $this->chave . "\r\n"
                . 'anthropic-version: ' . self::VERSAO_API . "\r\n"
                . "content-type: application/json\r\n",
            'content' => $corpo,
            'timeout' => $this->tempoLimite,
            'ignore_errors' => true,
        ]]);
        $resposta = @file_get_contents(self::URL, false, $contexto);
        if ($resposta === false) {
            throw new RuntimeException('Sem conexão com o serviço de IA.');
        }
        $status = 0;
        foreach ($http_response_header ?? [] as $cabecalho) {
            if (preg_match('#^HTTP/\S+\s+(\d{3})#', $cabecalho, $m) === 1) {
                $status = (int)$m[1];
            }
        }
        return [$status, $resposta];
    }
}
