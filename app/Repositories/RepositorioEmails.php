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
}
