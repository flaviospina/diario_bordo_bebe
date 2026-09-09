<?php

declare(strict_types=1);

namespace App\Repositories;

/**
 * Eventos importantes do bebê: marcos, intercorrências, medições confirmadas
 * e vacinas. Alimentam o e-mail imediato aos responsáveis e a seção
 * "novidades desde a última consulta" da ficha do pediatra.
 */
final class RepositorioEventos extends RepositorioBase
{
    public function criar(
        int $criancaId,
        string $tipo,
        string $titulo,
        ?string $descricao,
        ?string $referenciaTabela,
        ?int $referenciaId,
        string $ocorridoEm
    ): int {
        $this->executar(
            'INSERT INTO eventos_importantes
                    (familia_id, crianca_id, tipo, titulo, descricao,
                     referencia_tabela, referencia_id, ocorrido_em)
             VALUES (:familia_id, :crianca, :tipo, :titulo, :descricao, :ref_tabela, :ref_id, :ocorrido)',
            [
                'crianca' => $criancaId,
                'tipo' => $tipo,
                'titulo' => mb_substr($titulo, 0, 160),
                'descricao' => $descricao !== null ? mb_substr($descricao, 0, 2000) : null,
                'ref_tabela' => $referenciaTabela,
                'ref_id' => $referenciaId,
                'ocorrido' => $ocorridoEm,
            ]
        );
        return $this->ultimoId();
    }

    /** Idempotência: um mesmo registro de origem nunca gera dois eventos. */
    public function existePorReferencia(string $tipo, string $referenciaTabela, int $referenciaId): bool
    {
        return $this->buscarUm(
            'SELECT id FROM eventos_importantes
              WHERE familia_id = :familia_id AND tipo = :tipo
                AND referencia_tabela = :ref_tabela AND referencia_id = :ref_id
              LIMIT 1',
            ['tipo' => $tipo, 'ref_tabela' => $referenciaTabela, 'ref_id' => $referenciaId]
        ) !== null;
    }

    /** @return array<int,array<string,mixed>> eventos desde uma data (mais recentes primeiro) */
    public function listarDesde(int $criancaId, string $desde, int $limite = 30): array
    {
        return $this->buscarTodos(
            'SELECT * FROM eventos_importantes
              WHERE familia_id = :familia_id AND crianca_id = :crianca
                AND ocorrido_em >= :desde
              ORDER BY ocorrido_em DESC, id DESC
              LIMIT ' . max(1, min(100, $limite)),
            ['crianca' => $criancaId, 'desde' => $desde]
        );
    }

    public function marcarEmailEnviado(int $id): void
    {
        $this->executar(
            'UPDATE eventos_importantes SET email_enviado_em = NOW()
              WHERE familia_id = :familia_id AND id = :id',
            ['id' => $id]
        );
    }
}
