<?php

declare(strict_types=1);

namespace App\Repositories;

final class RepositorioVacinas extends RepositorioBase
{
    /** @param array<string,mixed> $vacina */
    public function criar(array $vacina): int
    {
        $this->executar(
            'INSERT INTO vacinas (familia_id, crianca_id, imunizante, dose, aplicada_em, lote,
                                  local_aplicacao, origem, profissional_id, status, observacao)
             VALUES (:familia_id, :crianca, :imunizante, :dose, :aplicada_em, :lote,
                     :local, :origem, :profissional, :status, :observacao)',
            [
                'crianca'      => (int)$vacina['crianca_id'],
                'imunizante'   => (string)$vacina['imunizante'],
                'dose'         => (string)$vacina['dose'],
                'aplicada_em'  => $vacina['aplicada_em'] ?? null,
                'lote'         => $vacina['lote'] ?? null,
                'local'        => $vacina['local_aplicacao'] ?? null,
                'origem'       => (string)($vacina['origem'] ?? 'pais'),
                'profissional' => $vacina['profissional_id'] ?? null,
                'status'       => (string)($vacina['status'] ?? 'aplicada'),
                'observacao'   => $vacina['observacao'] ?? null,
            ]
        );
        return $this->ultimoId();
    }

    /** @return array<int,array<string,mixed>> */
    public function listar(int $criancaId): array
    {
        return $this->buscarTodos(
            'SELECT v.*, p.nome AS profissional_nome
               FROM vacinas v
               LEFT JOIN profissionais p ON p.id = v.profissional_id
              WHERE v.familia_id = :familia_id AND v.crianca_id = :crianca
              ORDER BY v.aplicada_em, v.id',
            ['crianca' => $criancaId]
        );
    }
}
