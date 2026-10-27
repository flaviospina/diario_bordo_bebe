<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Ambiente;
use App\Repositories\RepositorioConfiguracoesPlataforma;

/**
 * De onde vêm os dados da conta de envio de e-mail (Rodada 5):
 *   1. Painel → "Conta de e-mail de envio" (configuracoes_plataforma) — se o
 *      super admin preencheu o servidor SMTP ali, é o que vale;
 *   2. senão, o .env (SMTP_* e EMAIL_*), como sempre foi.
 * A senha gravada pelo Painel fica criptografada no banco.
 */
final class ConfiguracaoEmail
{
    /**
     * @return array{origem:string, host:string, porta:int, seguranca:string, usuario:string,
     *               senha:string, remetente:string, nome:string}
     */
    public static function obter(): array
    {
        $env = [
            'origem' => 'env',
            'host' => Ambiente::obter('SMTP_HOST', ''),
            'porta' => (int)Ambiente::obter('SMTP_PORTA', '465'),
            'seguranca' => Ambiente::obter('SMTP_SEGURANCA', 'ssl'),
            'usuario' => Ambiente::obter('SMTP_USUARIO', ''),
            'senha' => Ambiente::obter('SMTP_SENHA', ''),
            'remetente' => Ambiente::obter('EMAIL_REMETENTE', 'nao-responda@localhost'),
            'nome' => Ambiente::obter('EMAIL_NOME_REMETENTE', 'Diário do Bebê'),
        ];
        try {
            $config = new RepositorioConfiguracoesPlataforma();
            $host = $config->obter('smtp_host');
            if ($host === '') {
                // Sem SMTP no Painel: ainda assim o remetente pode vir de lá
                $remetente = $config->obter('email_remetente');
                $nome = $config->obter('email_nome');
                if ($remetente !== '') {
                    $env['remetente'] = $remetente;
                }
                if ($nome !== '') {
                    $env['nome'] = $nome;
                }
                return $env;
            }
            $remetente = $config->obter('email_remetente') ?: $config->obter('smtp_usuario') ?: $env['remetente'];
            return [
                'origem' => 'painel',
                'host' => $host,
                'porta' => (int)($config->obter('smtp_porta') ?: 465),
                'seguranca' => $config->obter('smtp_seguranca') ?: 'ssl',
                'usuario' => $config->obter('smtp_usuario') ?: $remetente,
                'senha' => $config->obterSegredo('smtp_senha'),
                'remetente' => $remetente,
                'nome' => $config->obter('email_nome') ?: $env['nome'],
            ];
        } catch (\Throwable) {
            return $env; // tabela ainda não migrada
        }
    }
}
