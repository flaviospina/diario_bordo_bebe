<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Identificadores;

/**
 * Convites de FAMÍLIA (auto-cadastro): link único que o super_admin gera e
 * envia a um casal; quem abre cria a própria família. Diferente de `convites`
 * (que convida uma pessoa para uma família já existente).
 */
final class RepositorioConvitesFamilia extends RepositorioSistema
{
    public function criar(string $plano, int $criadoPor, int $validadeDias = 7, ?string $observacao = null): string
    {
        $codigo = Identificadores::codigoPublico();
        $this->executar(
            'INSERT INTO convites_familia (codigo_publico, plano, observacao, criado_por, expira_em)
             VALUES (:codigo, :plano, :obs, :autor, DATE_ADD(NOW(), INTERVAL :dias DAY))',
            ['codigo' => $codigo, 'plano' => $plano, 'obs' => $observacao, 'autor' => $criadoPor, 'dias' => $validadeDias]
        );
        return $codigo;
    }

    /** Válido = aberto e não expirado (expiração no relógio do banco). */
    public function buscarValido(string $codigo): ?array
    {
        if (preg_match('/^[0-9a-z]{12}$/', $codigo) !== 1) {
            return null;
        }
        return $this->buscarUm(
            "SELECT * FROM convites_familia
              WHERE codigo_publico = :codigo AND status = 'aberto' AND expira_em > NOW() LIMIT 1",
            ['codigo' => $codigo]
        );
    }

    public function marcarUsado(int $id, int $familiaId): void
    {
        $this->executar(
            "UPDATE convites_familia SET status = 'usado', usado_em = NOW(), familia_id = :familia
              WHERE id = :id AND status = 'aberto'",
            ['id' => $id, 'familia' => $familiaId]
        );
    }

    public function revogar(int $id): void
    {
        $this->executar(
            "UPDATE convites_familia SET status = 'revogado' WHERE id = :id AND status = 'aberto'",
            ['id' => $id]
        );
    }

    /** @return array<int,array<string,mixed>> últimos convites, com a família criada quando houver */
    public function listarRecentes(int $limite = 30): array
    {
        return $this->executar(
            'SELECT cf.*, f.nome AS familia_nome, (cf.expira_em <= NOW()) AS ja_expirado
               FROM convites_familia cf
               LEFT JOIN familias f ON f.id = cf.familia_id
              ORDER BY cf.criado_em DESC
              LIMIT ' . max(1, min(200, $limite))
        )->fetchAll();
    }
}
