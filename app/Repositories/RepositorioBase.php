<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Autenticacao;
use App\Core\BancoDados;
use PDO;
use PDOStatement;

/**
 * Base para repositórios de DADOS de família (multi-tenant).
 *
 * Regra central do produto: nenhuma query de dados roda sem filtro de
 * familia_id. O isolamento vive AQUI, não espalhado pelos controllers:
 *  - o familia_id vem da sessão autenticada (nunca de request);
 *  - toda query precisa referenciar o placeholder :familia_id — a base
 *    recusa SQL sem ele (erro de programação aparece cedo, em desenvolvimento).
 */
abstract class RepositorioBase
{
    protected PDO $bd;
    protected int $familiaId;

    public function __construct(?int $familiaId = null)
    {
        $this->bd = BancoDados::conexao();
        $this->familiaId = $familiaId ?? Autenticacao::familiaId();
        if ($this->familiaId <= 0) {
            throw new \LogicException('Repositório de dados instanciado sem contexto de família.');
        }
    }

    protected function executar(string $sql, array $parametros = []): PDOStatement
    {
        if (!str_contains($sql, ':familia_id')) {
            throw new \LogicException('Query de dados sem filtro de familia_id: ' . strtok($sql, "\n"));
        }
        $parametros['familia_id'] = $this->familiaId;
        $declaracao = $this->bd->prepare($sql);
        $declaracao->execute($parametros);
        return $declaracao;
    }

    protected function buscarUm(string $sql, array $parametros = []): ?array
    {
        $linha = $this->executar($sql, $parametros)->fetch();
        return $linha === false ? null : $linha;
    }

    /** @return array<int,array<string,mixed>> */
    protected function buscarTodos(string $sql, array $parametros = []): array
    {
        return $this->executar($sql, $parametros)->fetchAll();
    }

    protected function ultimoId(): int
    {
        return (int)$this->bd->lastInsertId();
    }
}
