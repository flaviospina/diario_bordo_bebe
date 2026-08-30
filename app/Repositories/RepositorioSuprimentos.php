<?php

declare(strict_types=1);

namespace App\Repositories;

final class RepositorioSuprimentos extends RepositorioBase
{
    public function criar(string $item, string $nivel, int $solicitadoPor): int
    {
        $this->executar(
            'INSERT INTO suprimentos (familia_id, item, nivel, solicitado_por)
             VALUES (:familia_id, :item, :nivel, :usuario)',
            ['item' => $item, 'nivel' => $nivel, 'usuario' => $solicitadoPor]
        );
        return $this->ultimoId();
    }

    /** @return array<int,array<string,mixed>> */
    public function listar(bool $somenteAbertos = false, int $limite = 100): array
    {
        $filtro = $somenteAbertos ? 'AND s.resolvido_em IS NULL' : '';
        return $this->buscarTodos(
            "SELECT s.*, u.nome AS solicitante_nome
               FROM suprimentos s JOIN usuarios u ON u.id = s.solicitado_por
              WHERE s.familia_id = :familia_id {$filtro}
              ORDER BY s.resolvido_em IS NOT NULL, s.solicitado_em DESC
              LIMIT " . max(1, min(500, $limite))
        );
    }

    public function resolver(int $id): void
    {
        $this->executar(
            'UPDATE suprimentos SET resolvido_em = NOW()
              WHERE familia_id = :familia_id AND id = :id AND resolvido_em IS NULL',
            ['id' => $id]
        );
    }

    public function contarAbertos(): int
    {
        $linha = $this->buscarUm(
            'SELECT COUNT(*) AS total FROM suprimentos
              WHERE familia_id = :familia_id AND resolvido_em IS NULL'
        );
        return (int)($linha['total'] ?? 0);
    }
}
