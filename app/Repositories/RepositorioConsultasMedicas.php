<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Identificadores;

/**
 * Consultas registradas e links de uso único para o pediatra
 * (convites_consulta). Tenant-scoped como todo dado de família.
 */
final class RepositorioConsultasMedicas extends RepositorioBase
{
    // ── Consultas ─────────────────────────────────────────────

    /** @param array<string,mixed> $consulta */
    public function criarConsulta(array $consulta): int
    {
        $this->executar(
            'INSERT INTO consultas (familia_id, crianca_id, profissional_id, realizada_em,
                                    motivo, conduta, retorno_em, origem)
             VALUES (:familia_id, :crianca, :profissional, :realizada_em, :motivo, :conduta, :retorno, :origem)',
            [
                'crianca'      => (int)$consulta['crianca_id'],
                'profissional' => $consulta['profissional_id'] ?? null,
                'realizada_em' => (string)$consulta['realizada_em'],
                'motivo'       => $consulta['motivo'] ?? null,
                'conduta'      => $consulta['conduta'] ?? null,
                'retorno'      => $consulta['retorno_em'] ?? null,
                'origem'       => (string)($consulta['origem'] ?? 'pais'),
            ]
        );
        return $this->ultimoId();
    }

    /** @return array<int,array<string,mixed>> */
    public function listarConsultas(int $criancaId, int $limite = 50): array
    {
        return $this->buscarTodos(
            'SELECT c.*, p.nome AS profissional_nome, p.conselho_sigla, p.conselho_numero, p.conselho_uf
               FROM consultas c
               LEFT JOIN profissionais p ON p.id = c.profissional_id
              WHERE c.familia_id = :familia_id AND c.crianca_id = :crianca
              ORDER BY c.realizada_em DESC, c.id DESC
              LIMIT ' . max(1, min(200, $limite)),
            ['crianca' => $criancaId]
        );
    }

    // ── Links de consulta (uso único) ─────────────────────────

    public function criarConvite(int $criancaId, int $usuarioId, int $validadeHoras = 48): string
    {
        $codigo = Identificadores::codigoPublico();
        $this->executar(
            'INSERT INTO convites_consulta (familia_id, crianca_id, codigo_publico,
                                            criado_por_usuario_id, expira_em)
             VALUES (:familia_id, :crianca, :codigo, :usuario, DATE_ADD(NOW(), INTERVAL :horas HOUR))',
            ['crianca' => $criancaId, 'codigo' => $codigo, 'usuario' => $usuarioId, 'horas' => $validadeHoras]
        );
        return $codigo;
    }

    /** @return array<int,array<string,mixed>> links ainda válidos da criança */
    public function convitesAtivos(int $criancaId): array
    {
        return $this->buscarTodos(
            "SELECT * FROM convites_consulta
              WHERE familia_id = :familia_id AND crianca_id = :crianca
                AND status = 'aberto' AND expira_em > NOW()
              ORDER BY criado_em DESC",
            ['crianca' => $criancaId]
        );
    }

    public function revogarConvite(int $conviteId): void
    {
        $this->executar(
            "UPDATE convites_consulta SET status = 'revogado'
              WHERE familia_id = :familia_id AND id = :id AND status = 'aberto'",
            ['id' => $conviteId]
        );
    }
}
