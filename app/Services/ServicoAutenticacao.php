<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Autenticacao;
use App\Repositories\RepositorioLogAcessos;
use App\Repositories\RepositorioTentativasLogin;
use App\Repositories\RepositorioUsuarios;

/**
 * Regras de autenticação: verificação de credenciais (Argon2id),
 * rate limit e trilha de auditoria de login/logout.
 */
final class ServicoAutenticacao
{
    private const MAXIMO_FALHAS = 5;
    private const JANELA_MINUTOS = 15;

    public function __construct(
        private readonly RepositorioUsuarios $usuarios = new RepositorioUsuarios(),
        private readonly RepositorioTentativasLogin $tentativas = new RepositorioTentativasLogin(),
        private readonly RepositorioLogAcessos $log = new RepositorioLogAcessos(),
    ) {
    }

    public static function gerarHashSenha(string $senha): string
    {
        return password_hash($senha, PASSWORD_ARGON2ID);
    }

    /**
     * Tenta autenticar. Retorna ['ok' => bool, 'mensagem' => string].
     * Mensagens de erro são genéricas de propósito: não revelar se o e-mail existe.
     */
    public function entrar(string $email, string $senha, string $ip, string $userAgent): array
    {
        if ($this->tentativas->falhasRecentes($email, $ip, self::JANELA_MINUTOS) >= self::MAXIMO_FALHAS) {
            return ['ok' => false, 'mensagem' => 'Muitas tentativas. Aguarde alguns minutos e tente de novo.'];
        }

        $usuario = $this->usuarios->buscarPorEmail($email);
        $senhaOk = $usuario !== null && password_verify($senha, (string)$usuario['senha_hash']);

        if (!$senhaOk || (int)($usuario['ativo'] ?? 0) !== 1) {
            $this->tentativas->registrar($email, $ip, false);
            $this->log->registrar(
                $usuario !== null ? (int)$usuario['familia_id'] : null,
                $usuario !== null ? (int)$usuario['id'] : null,
                'login_falhou',
                'usuarios',
                null,
                $ip,
                $userAgent
            );
            return ['ok' => false, 'mensagem' => 'E-mail ou senha inválidos.'];
        }

        // Família suspensa/encerrada bloqueia o login de todos, exceto super_admin
        if ($usuario['papel'] !== 'super_admin') {
            $familia = (new \App\Repositories\RepositorioFamilias())->buscarPorId((int)$usuario['familia_id']);
            if ($familia === null || $familia['status'] !== 'ativa') {
                $this->tentativas->registrar($email, $ip, false);
                return ['ok' => false, 'mensagem' => 'O acesso desta família está suspenso. Fale com o suporte.'];
            }
        }

        // Migra o hash se o custo/algoritmo padrão mudar no futuro
        if (password_needs_rehash((string)$usuario['senha_hash'], PASSWORD_ARGON2ID)) {
            $this->usuarios->atualizarSenha((int)$usuario['id'], self::gerarHashSenha($senha));
        }

        $this->tentativas->registrar($email, $ip, true);
        $this->usuarios->atualizarUltimoLogin((int)$usuario['id']);
        Autenticacao::autenticar($usuario);
        $this->log->registrar((int)$usuario['familia_id'], (int)$usuario['id'], 'login', 'usuarios', (int)$usuario['id'], $ip, $userAgent);

        return ['ok' => true, 'mensagem' => ''];
    }

    public function sair(string $ip, string $userAgent): void
    {
        if (Autenticacao::estaLogado()) {
            $this->log->registrar(
                Autenticacao::familiaId(),
                Autenticacao::id(),
                'logout',
                'usuarios',
                Autenticacao::id(),
                $ip,
                $userAgent
            );
        }
        Autenticacao::sair();
    }
}
