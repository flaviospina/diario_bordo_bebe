<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Identificadores;
use App\Repositories\RepositorioLogAcessos;
use App\Repositories\RepositorioTokensSenha;
use App\Repositories\RepositorioUsuarios;

/**
 * Recuperação e redefinição de senha por e-mail com token expirável.
 */
final class ServicoSenha
{
    public function __construct(
        private readonly RepositorioUsuarios $usuarios = new RepositorioUsuarios(),
        private readonly RepositorioTokensSenha $tokens = new RepositorioTokensSenha(),
        private readonly ServicoEmail $email = new ServicoEmail(),
        private readonly RepositorioLogAcessos $log = new RepositorioLogAcessos(),
    ) {
    }

    /**
     * Sempre responde como sucesso para o usuário final (não revelar se o
     * e-mail existe); só envia de fato quando a conta existe e está ativa.
     */
    public function solicitarRecuperacao(string $emailInformado, string $ip): void
    {
        $usuario = $this->usuarios->buscarPorEmail($emailInformado);
        if ($usuario === null || (int)$usuario['ativo'] !== 1) {
            return;
        }

        $token = Identificadores::token();
        $this->tokens->criar((int)$usuario['id'], Identificadores::hashToken($token));

        $link = url_absoluta('senha.redefinir', ['token' => $token]);

        $this->email->enviar(
            (string)$usuario['email'],
            'Redefinição de senha — Diário do Bebê',
            "Olá, {$usuario['nome']}.\n\n"
            . "Recebemos um pedido para redefinir a sua senha no Diário do Bebê.\n"
            . "Para criar uma nova senha, acesse o link abaixo (válido por 1 hora):\n\n"
            . $link . "\n\n"
            . "Se você não pediu a redefinição, ignore este e-mail — nada será alterado.\n"
        );

        $this->log->registrar((int)$usuario['familia_id'], (int)$usuario['id'], 'senha_recuperacao_solicitada', 'usuarios', (int)$usuario['id'], $ip);
    }

    /** Valida o token puro vindo da URL; retorna dados do token ou null. */
    public function validarToken(string $token): ?array
    {
        if ($token === '' || !preg_match('/^[0-9a-f]{64}$/', $token)) {
            return null;
        }
        return $this->tokens->buscarValidoPorHash(Identificadores::hashToken($token));
    }

    /** Retorna mensagem de erro ou null em caso de sucesso. */
    public function redefinir(string $token, string $novaSenha, string $ip): ?string
    {
        $registroToken = $this->validarToken($token);
        if ($registroToken === null) {
            return 'Link inválido ou expirado. Peça uma nova recuperação de senha.';
        }
        $erroSenha = self::validarForcaSenha($novaSenha);
        if ($erroSenha !== null) {
            return $erroSenha;
        }

        $usuarioId = (int)$registroToken['usuario_id'];
        $this->usuarios->atualizarSenha($usuarioId, ServicoAutenticacao::gerarHashSenha($novaSenha));
        $this->tokens->marcarUsado((int)$registroToken['id']);

        $usuario = $this->usuarios->buscarPorId($usuarioId);
        $this->log->registrar(
            $usuario !== null ? (int)$usuario['familia_id'] : null,
            $usuarioId,
            'senha_redefinida',
            'usuarios',
            $usuarioId,
            $ip
        );
        return null;
    }

    public static function validarForcaSenha(string $senha): ?string
    {
        if (mb_strlen($senha) < 10) {
            return 'A senha precisa ter pelo menos 10 caracteres.';
        }
        return null;
    }
}
