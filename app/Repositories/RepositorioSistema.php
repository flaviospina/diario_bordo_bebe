<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\BancoDados;
use PDO;
use PDOStatement;

/**
 * Base para repositórios de SISTEMA — os poucos que legitimamente operam sem
 * filtro de tenant: autenticação por e-mail (global), convites e tokens por
 * hash, tentativas de login e log de acessos. Repositórios de DADOS de família
 * devem estender RepositorioBase, que impõe familia_id em toda query.
 */
abstract class RepositorioSistema
{
    protected PDO $bd;

    public function __construct()
    {
        $this->bd = BancoDados::conexao();
    }

    protected function executar(string $sql, array $parametros = []): PDOStatement
    {
        $declaracao = $this->bd->prepare($sql);
        $declaracao->execute($parametros);
        return $declaracao;
    }

    protected function buscarUm(string $sql, array $parametros = []): ?array
    {
        $linha = $this->executar($sql, $parametros)->fetch();
        return $linha === false ? null : $linha;
    }
}
