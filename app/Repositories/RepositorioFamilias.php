<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Identificadores;

/**
 * Famílias (tenants). Base "sistema": usada pelas tarefas agendadas, que
 * percorrem todas as famílias, e pelo painel do super_admin (que gerencia
 * tenants mas nunca vê conteúdo de registros).
 */
final class RepositorioFamilias extends RepositorioSistema
{
    /** @return array<int,array<string,mixed>> */
    public function listarAtivas(): array
    {
        return $this->executar(
            "SELECT * FROM familias WHERE status = 'ativa' AND plano <> 'plataforma' ORDER BY nome"
        )->fetchAll();
    }

    /** @return array<int,array<string,mixed>> todas, para o painel do super_admin */
    public function listarTodas(): array
    {
        return $this->executar(
            "SELECT f.*,
                    (SELECT COUNT(*) FROM usuarios u WHERE u.familia_id = f.id) AS total_usuarios,
                    (SELECT COUNT(*) FROM criancas c WHERE c.familia_id = f.id) AS total_criancas,
                    (SELECT COUNT(*) FROM registros r WHERE r.familia_id = f.id) AS total_registros
               FROM familias f
              WHERE f.plano <> 'plataforma'
              ORDER BY f.criado_em DESC"
        )->fetchAll();
    }

    public function buscarPorId(int $id): ?array
    {
        return $this->buscarUm('SELECT * FROM familias WHERE id = :id LIMIT 1', ['id' => $id]);
    }

    public function buscarPorCodigo(string $codigo): ?array
    {
        return $this->buscarUm(
            'SELECT * FROM familias WHERE codigo_publico = :codigo LIMIT 1',
            ['codigo' => $codigo]
        );
    }

    public function criar(string $nome, string $plano = 'familiar'): int
    {
        $base = Identificadores::slug($nome);
        $slug = $base;
        $sufixo = 2;
        while ($this->buscarUm('SELECT id FROM familias WHERE slug = :slug LIMIT 1', ['slug' => $slug]) !== null) {
            $slug = $base . '-' . $sufixo;
            $sufixo++;
        }
        $this->executar(
            'INSERT INTO familias (codigo_publico, slug, nome, plano) VALUES (:codigo, :slug, :nome, :plano)',
            ['codigo' => Identificadores::codigoPublico(), 'slug' => $slug, 'nome' => $nome, 'plano' => $plano]
        );
        return (int)$this->bd->lastInsertId();
    }

    public function alterarStatus(int $id, string $status): void
    {
        $this->executar(
            'UPDATE familias SET status = :status WHERE id = :id',
            ['id' => $id, 'status' => $status]
        );
    }

    public function alterarPlano(int $id, string $plano): void
    {
        $this->executar(
            'UPDATE familias SET plano = :plano WHERE id = :id',
            ['id' => $id, 'plano' => $plano]
        );
    }
}
