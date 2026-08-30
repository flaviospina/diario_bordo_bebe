<?php

declare(strict_types=1);

namespace App\Repositories;

/**
 * Tokens de redefinição de senha (expiráveis, uso único, armazenados por hash).
 */
final class RepositorioTokensSenha extends RepositorioSistema
{
    public function criar(int $usuarioId, string $tokenHash, int $validadeMinutos = 60): void
    {
        // Invalida tokens anteriores ainda abertos do mesmo usuário
        $this->executar(
            'UPDATE tokens_senha SET usado_em = NOW() WHERE usuario_id = :usuario AND usado_em IS NULL',
            ['usuario' => $usuarioId]
        );
        $this->executar(
            'INSERT INTO tokens_senha (usuario_id, token_hash, expira_em)
             VALUES (:usuario, :hash, DATE_ADD(NOW(), INTERVAL :minutos MINUTE))',
            ['usuario' => $usuarioId, 'hash' => $tokenHash, 'minutos' => $validadeMinutos]
        );
    }

    public function buscarValidoPorHash(string $tokenHash): ?array
    {
        return $this->buscarUm(
            'SELECT * FROM tokens_senha
              WHERE token_hash = :hash AND usado_em IS NULL AND expira_em > NOW()
              LIMIT 1',
            ['hash' => $tokenHash]
        );
    }

    public function marcarUsado(int $id): void
    {
        $this->executar('UPDATE tokens_senha SET usado_em = NOW() WHERE id = :id', ['id' => $id]);
    }
}
