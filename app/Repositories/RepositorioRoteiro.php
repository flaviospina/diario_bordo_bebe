<?php

declare(strict_types=1);

namespace App\Repositories;

final class RepositorioRoteiro extends RepositorioBase
{
    /** @return array<int,array<string,mixed>> todos os blocos ativos da criança */
    public function listar(int $criancaId): array
    {
        return $this->buscarTodos(
            'SELECT rb.*, c.nome AS categoria_nome, c.slug AS categoria_slug, c.icone AS categoria_icone
               FROM roteiro_blocos rb
               LEFT JOIN categorias c ON c.id = rb.categoria_id
              WHERE rb.familia_id = :familia_id AND rb.crianca_id = :crianca AND rb.ativo = 1
              ORDER BY rb.hora_inicio, rb.id',
            ['crianca' => $criancaId]
        );
    }

    /** @return array<int,array<string,mixed>> blocos do dia da semana (dom..sab) */
    public function listarParaDia(int $criancaId, string $diaSemana): array
    {
        return $this->buscarTodos(
            'SELECT rb.*, c.nome AS categoria_nome, c.slug AS categoria_slug, c.icone AS categoria_icone
               FROM roteiro_blocos rb
               LEFT JOIN categorias c ON c.id = rb.categoria_id
              WHERE rb.familia_id = :familia_id AND rb.crianca_id = :crianca AND rb.ativo = 1
                AND FIND_IN_SET(:dia, rb.dias_semana) > 0
              ORDER BY rb.hora_inicio, rb.id',
            ['crianca' => $criancaId, 'dia' => $diaSemana]
        );
    }

    public function buscar(int $id): ?array
    {
        return $this->buscarUm(
            'SELECT * FROM roteiro_blocos WHERE familia_id = :familia_id AND id = :id LIMIT 1',
            ['id' => $id]
        );
    }

    /** @param array<string,mixed> $bloco */
    public function criar(array $bloco): int
    {
        $this->executar(
            'INSERT INTO roteiro_blocos (familia_id, crianca_id, dias_semana, hora_inicio, hora_fim,
                                         titulo, categoria_id, instrucao, obrigatorio)
             VALUES (:familia_id, :crianca, :dias, :inicio, :fim, :titulo, :categoria, :instrucao, :obrigatorio)',
            [
                'crianca'     => (int)$bloco['crianca_id'],
                'dias'        => (string)$bloco['dias_semana'],
                'inicio'      => (string)$bloco['hora_inicio'],
                'fim'         => (string)$bloco['hora_fim'],
                'titulo'      => (string)$bloco['titulo'],
                'categoria'   => $bloco['categoria_id'] ?? null,
                'instrucao'   => $bloco['instrucao'] ?? null,
                'obrigatorio' => (int)($bloco['obrigatorio'] ?? 0),
            ]
        );
        return $this->ultimoId();
    }

    /** @param array<string,mixed> $bloco */
    public function atualizar(int $id, array $bloco): void
    {
        $this->executar(
            'UPDATE roteiro_blocos SET dias_semana = :dias, hora_inicio = :inicio, hora_fim = :fim,
                    titulo = :titulo, categoria_id = :categoria, instrucao = :instrucao,
                    obrigatorio = :obrigatorio
              WHERE familia_id = :familia_id AND id = :id',
            [
                'id'          => $id,
                'dias'        => (string)$bloco['dias_semana'],
                'inicio'      => (string)$bloco['hora_inicio'],
                'fim'         => (string)$bloco['hora_fim'],
                'titulo'      => (string)$bloco['titulo'],
                'categoria'   => $bloco['categoria_id'] ?? null,
                'instrucao'   => $bloco['instrucao'] ?? null,
                'obrigatorio' => (int)($bloco['obrigatorio'] ?? 0),
            ]
        );
    }

    public function remover(int $id): void
    {
        // Desativação lógica: blocos antigos podem estar referenciados por registros
        $this->executar(
            'UPDATE roteiro_blocos SET ativo = 0 WHERE familia_id = :familia_id AND id = :id',
            ['id' => $id]
        );
    }
}
