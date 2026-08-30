<?php

declare(strict_types=1);

namespace App\Repositories;

/**
 * Catálogo de categorias visível a uma família: globais (familia_id NULL)
 * + personalizadas da família. A (des)ativação por família fica na
 * configuração 'categorias_inativas' (lista de slugs), não em linhas clonadas.
 */
final class RepositorioCategorias extends RepositorioBase
{
    /** @return array<int,array<string,mixed>> catálogo completo, ordenado por grupo */
    public function catalogo(): array
    {
        return $this->buscarTodos(
            'SELECT * FROM categorias
              WHERE (familia_id IS NULL OR familia_id = :familia_id) AND ativo = 1
              ORDER BY grupo, ordem, nome'
        );
    }

    /** @return array<int,array<string,mixed>> apenas as ativas para esta família */
    public function ativasParaFamilia(array $slugsInativos): array
    {
        return array_values(array_filter(
            $this->catalogo(),
            static fn(array $c): bool => !in_array($c['slug'], $slugsInativos, true)
        ));
    }

    public function buscarPorSlug(string $slug): ?array
    {
        return $this->buscarUm(
            'SELECT * FROM categorias
              WHERE (familia_id IS NULL OR familia_id = :familia_id)
                AND slug = :slug AND ativo = 1
              LIMIT 1',
            ['slug' => $slug]
        );
    }

    public function buscarPorId(int $id): ?array
    {
        return $this->buscarUm(
            'SELECT * FROM categorias
              WHERE (familia_id IS NULL OR familia_id = :familia_id) AND id = :id
              LIMIT 1',
            ['id' => $id]
        );
    }
}
