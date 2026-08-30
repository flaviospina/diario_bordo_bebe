<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Identificadores;

/**
 * Usuários. Busca por e-mail é global (login é anterior ao contexto de tenant);
 * qualquer listagem/gestão de usuários por família virá com filtro (Fase 2).
 */
final class RepositorioUsuarios extends RepositorioSistema
{
    public function buscarPorEmail(string $email): ?array
    {
        return $this->buscarUm(
            'SELECT * FROM usuarios WHERE email = :email LIMIT 1',
            ['email' => mb_strtolower($email)]
        );
    }

    public function buscarPorId(int $id): ?array
    {
        return $this->buscarUm('SELECT * FROM usuarios WHERE id = :id LIMIT 1', ['id' => $id]);
    }

    public function atualizarUltimoLogin(int $id): void
    {
        $this->executar('UPDATE usuarios SET ultimo_login = NOW() WHERE id = :id', ['id' => $id]);
    }

    public function atualizarSenha(int $id, string $senhaHash): void
    {
        $this->executar(
            'UPDATE usuarios SET senha_hash = :senha WHERE id = :id',
            ['senha' => $senhaHash, 'id' => $id]
        );
    }

    /** Cria usuário a partir de um convite aceito. Retorna o id criado. */
    public function criar(int $familiaId, string $nome, string $email, string $senhaHash, string $papel, ?string $telefone = null): int
    {
        $this->executar(
            'INSERT INTO usuarios (familia_id, codigo_publico, nome, email, senha_hash, papel, telefone_whatsapp)
             VALUES (:familia_id, :codigo, :nome, :email, :senha, :papel, :telefone)',
            [
                'familia_id' => $familiaId,
                'codigo'     => Identificadores::codigoPublico(),
                'nome'       => $nome,
                'email'      => mb_strtolower($email),
                'senha'      => $senhaHash,
                'papel'      => $papel,
                'telefone'   => $telefone,
            ]
        );
        return (int)$this->bd->lastInsertId();
    }
}
