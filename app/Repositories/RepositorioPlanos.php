<?php

declare(strict_types=1);

namespace App\Repositories;

/**
 * Catálogo de planos da plataforma. Os LIMITES moram no JSON `limites`
 * (null em um limite = ilimitado) — regra de produto: nada de limite
 * hardcoded no código.
 */
final class RepositorioPlanos extends RepositorioSistema
{
    /** @return array<int,array<string,mixed>> planos ativos, na ordem de exibição */
    public function ativos(): array
    {
        return $this->executar('SELECT * FROM planos WHERE ativo = 1 ORDER BY ordem')->fetchAll();
    }

    public function porChave(string $chave): ?array
    {
        return $this->buscarUm('SELECT * FROM planos WHERE chave = :chave LIMIT 1', ['chave' => $chave]);
    }
}
