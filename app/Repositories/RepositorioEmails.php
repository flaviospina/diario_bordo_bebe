<?php

declare(strict_types=1);

namespace App\Repositories;

/**
 * Relatório de e-mails transacionais enviados (auditoria de entrega).
 */
final class RepositorioEmails extends RepositorioSistema
{
    public function registrar(
        string $destinatario,
        string $assunto,
        string $tipo,
        bool $enviado,
        ?string $erro = null,
        ?int $referenciaId = null,
        ?int $familiaId = null
    ): void {
        $this->executar(
            'INSERT INTO emails_enviados (destinatario, assunto, tipo, referencia_id, familia_id, status, erro)
             VALUES (:destinatario, :assunto, :tipo, :referencia, :familia, :status, :erro)',
            [
                'destinatario' => mb_substr($destinatario, 0, 190),
                'assunto' => mb_substr($assunto, 0, 190),
                'tipo' => mb_substr($tipo, 0, 40),
                'referencia' => $referenciaId,
                'familia' => $familiaId,
                'status' => $enviado ? 'enviado' : 'falhou',
                'erro' => $erro !== null ? mb_substr($erro, 0, 255) : null,
            ]
        );
    }

    /** @return array<int,array<string,mixed>> */
    public function listar(int $limite = 50): array
    {
        return $this->executar(
            'SELECT * FROM emails_enviados ORDER BY criado_em DESC, id DESC LIMIT ' . max(1, min(500, $limite))
        )->fetchAll();
    }

    // ── Travas anti-spam do engajamento (Rodada 3) ────────────

    /** Houve envio deste tipo para a família nos últimos N dias? */
    public function enviadoRecentemente(int $familiaId, string $tipo, int $dias): bool
    {
        return $this->buscarUm(
            "SELECT id FROM emails_enviados
              WHERE familia_id = :familia AND tipo = :tipo AND status = 'enviado'
                AND criado_em >= DATE_SUB(NOW(), INTERVAL :dias DAY) LIMIT 1",
            ['familia' => $familiaId, 'tipo' => $tipo, 'dias' => $dias]
        ) !== null;
    }

    /** Quantos envios deste tipo a família recebeu desde uma data (ou desde sempre)? */
    public function contarDesde(int $familiaId, string $tipo, ?string $desde = null): int
    {
        $filtro = $desde !== null ? 'AND criado_em >= :desde' : '';
        $parametros = ['familia' => $familiaId, 'tipo' => $tipo]
            + ($desde !== null ? ['desde' => $desde] : []);
        $linha = $this->buscarUm(
            "SELECT COUNT(DISTINCT DATE(criado_em)) AS total FROM emails_enviados
              WHERE familia_id = :familia AND tipo = :tipo AND status = 'enviado' {$filtro}",
            $parametros
        );
        return (int)($linha['total'] ?? 0);
    }

    /** Já houve envio deste tipo com esta referência (ex.: consulta, criança+mês)? */
    public function existePorReferencia(string $tipo, int $referenciaId, ?string $desde = null): bool
    {
        $filtro = $desde !== null ? 'AND criado_em >= :desde' : '';
        $parametros = ['tipo' => $tipo, 'referencia' => $referenciaId]
            + ($desde !== null ? ['desde' => $desde] : []);
        return $this->buscarUm(
            "SELECT id FROM emails_enviados
              WHERE tipo = :tipo AND referencia_id = :referencia AND status = 'enviado' {$filtro} LIMIT 1",
            $parametros
        ) !== null;
    }

    /** Últimos envios de engajamento por família, para o dashboard. */
    public function ultimosEngajamentosPorFamilia(): array
    {
        $linhas = $this->executar(
            "SELECT familia_id, tipo, MAX(criado_em) AS ultimo,
                    COUNT(DISTINCT DATE(criado_em)) AS vezes
               FROM emails_enviados
              WHERE status = 'enviado' AND familia_id IS NOT NULL
                AND tipo IN ('resgate', 'reengajamento', 'resumo_mensal', 'mesversario', 'pre_consulta')
              GROUP BY familia_id, tipo"
        )->fetchAll();
        $porFamilia = [];
        foreach ($linhas as $linha) {
            $porFamilia[(int)$linha['familia_id']][] = $linha;
        }
        return $porFamilia;
    }
}
