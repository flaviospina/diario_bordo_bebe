<?php

declare(strict_types=1);

namespace App\Repositories;

final class RepositorioTurnos extends RepositorioBase
{
    public function turnoAbertoDoDia(int $usuarioId, string $data): ?array
    {
        return $this->buscarUm(
            'SELECT * FROM turnos
              WHERE familia_id = :familia_id AND usuario_id = :usuario
                AND entrada >= :inicio AND entrada < :fim AND saida IS NULL
              ORDER BY entrada DESC LIMIT 1',
            ['usuario' => $usuarioId, 'inicio' => $data . ' 00:00:00', 'fim' => $data . ' 23:59:59']
        );
    }

    public function temTurnoNoDia(int $usuarioId, string $data): bool
    {
        return $this->buscarUm(
            'SELECT id FROM turnos
              WHERE familia_id = :familia_id AND usuario_id = :usuario
                AND entrada >= :inicio AND entrada < :fim LIMIT 1',
            ['usuario' => $usuarioId, 'inicio' => $data . ' 00:00:00', 'fim' => $data . ' 23:59:59']
        ) !== null;
    }

    public function abrir(int $usuarioId, string $entrada, bool $manual, ?string $observacao = null): int
    {
        $this->executar(
            'INSERT INTO turnos (familia_id, usuario_id, entrada, entrada_manual, observacao)
             VALUES (:familia_id, :usuario, :entrada, :manual, :observacao)',
            ['usuario' => $usuarioId, 'entrada' => $entrada, 'manual' => $manual ? 1 : 0, 'observacao' => $observacao]
        );
        return $this->ultimoId();
    }

    public function fechar(int $turnoId, string $saida): void
    {
        $this->executar(
            'UPDATE turnos SET saida = :saida WHERE familia_id = :familia_id AND id = :id AND saida IS NULL',
            ['id' => $turnoId, 'saida' => $saida]
        );
    }

    public function ajustar(int $turnoId, string $entrada, ?string $saida, ?string $observacao): void
    {
        $this->executar(
            'UPDATE turnos SET entrada = :entrada, saida = :saida, entrada_manual = 1, observacao = :observacao
              WHERE familia_id = :familia_id AND id = :id',
            ['id' => $turnoId, 'entrada' => $entrada, 'saida' => $saida, 'observacao' => $observacao]
        );
    }

    /** Fecha turnos abertos de dias anteriores usando o último registro do cuidador no dia. */
    public function buscarAbertosAntigos(): array
    {
        return $this->buscarTodos(
            'SELECT * FROM turnos
              WHERE familia_id = :familia_id AND saida IS NULL AND entrada < CURDATE()'
        );
    }

    /** @return array<int,array<string,mixed>> */
    public function listar(int $limite = 60): array
    {
        return $this->buscarTodos(
            'SELECT t.*, u.nome AS usuario_nome
               FROM turnos t JOIN usuarios u ON u.id = t.usuario_id
              WHERE t.familia_id = :familia_id
              ORDER BY t.entrada DESC
              LIMIT ' . max(1, min(365, $limite))
        );
    }

    public function buscar(int $id): ?array
    {
        return $this->buscarUm(
            'SELECT * FROM turnos WHERE familia_id = :familia_id AND id = :id LIMIT 1',
            ['id' => $id]
        );
    }
}
