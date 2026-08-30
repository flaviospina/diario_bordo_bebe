<?php

declare(strict_types=1);

namespace App\Repositories;

/**
 * Gestão de usuários DENTRO de uma família (tenant-scoped) — diferente do
 * RepositorioUsuarios (sistema), usado só no login/tokens.
 */
final class RepositorioUsuariosFamilia extends RepositorioBase
{
    /** @return array<int,array<string,mixed>> */
    public function listar(): array
    {
        return $this->buscarTodos(
            'SELECT id, codigo_publico, nome, email, papel, telefone_whatsapp, ativo, ultimo_login, criado_em
               FROM usuarios
              WHERE familia_id = :familia_id AND papel <> \'super_admin\'
              ORDER BY ativo DESC, nome'
        );
    }

    public function buscarPorCodigo(string $codigoPublico): ?array
    {
        return $this->buscarUm(
            'SELECT * FROM usuarios WHERE familia_id = :familia_id AND codigo_publico = :codigo LIMIT 1',
            ['codigo' => $codigoPublico]
        );
    }

    public function definirAtivo(int $id, bool $ativo): void
    {
        $this->executar(
            'UPDATE usuarios SET ativo = :ativo WHERE familia_id = :familia_id AND id = :id',
            ['id' => $id, 'ativo' => $ativo ? 1 : 0]
        );
    }

    public function alterarPapel(int $id, string $papel): void
    {
        $this->executar(
            'UPDATE usuarios SET papel = :papel
              WHERE familia_id = :familia_id AND id = :id AND papel <> \'super_admin\'',
            ['id' => $id, 'papel' => $papel]
        );
    }

    /** @return array<int,array<string,mixed>> responsáveis/admins com WhatsApp ou e-mail para notificar */
    public function responsaveisParaNotificar(): array
    {
        return $this->buscarTodos(
            'SELECT id, nome, email, telefone_whatsapp
               FROM usuarios
              WHERE familia_id = :familia_id AND ativo = 1
                AND papel IN (\'admin_familia\', \'responsavel\')'
        );
    }

    // ── Convites ──────────────────────────────────────────────

    /** @return array<int,array<string,mixed>> */
    public function listarConvites(): array
    {
        return $this->buscarTodos(
            'SELECT c.*, u.nome AS convidado_por_nome
               FROM convites c
               JOIN usuarios u ON u.id = c.convidado_por
              WHERE c.familia_id = :familia_id
              ORDER BY c.criado_em DESC
              LIMIT 50'
        );
    }

    public function criarConvite(string $email, string $papel, string $tokenHash, int $convidadoPor, int $validadeDias = 7): int
    {
        $this->executar(
            'INSERT INTO convites (familia_id, email, papel, token_hash, convidado_por, expira_em)
             VALUES (:familia_id, :email, :papel, :hash, :autor, DATE_ADD(NOW(), INTERVAL :dias DAY))',
            ['email' => mb_strtolower($email), 'papel' => $papel, 'hash' => $tokenHash,
             'autor' => $convidadoPor, 'dias' => $validadeDias]
        );
        return $this->ultimoId();
    }

    public function cancelarConvite(int $id): void
    {
        // Cancelar = expirar imediatamente (mantém o rastro)
        $this->executar(
            'UPDATE convites SET expira_em = NOW()
              WHERE familia_id = :familia_id AND id = :id AND aceito_em IS NULL',
            ['id' => $id]
        );
    }

    public function buscarConvite(int $id): ?array
    {
        return $this->buscarUm(
            'SELECT * FROM convites WHERE familia_id = :familia_id AND id = :id LIMIT 1',
            ['id' => $id]
        );
    }

    public function temConvitePendente(string $email): bool
    {
        return $this->buscarUm(
            'SELECT id FROM convites
              WHERE familia_id = :familia_id AND email = :email
                AND aceito_em IS NULL AND expira_em > NOW()
              LIMIT 1',
            ['email' => mb_strtolower($email)]
        ) !== null;
    }
}
