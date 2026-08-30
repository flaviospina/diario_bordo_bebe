<?php

declare(strict_types=1);

namespace App\Repositories;

/**
 * Fila de notificações. Base "sistema" de propósito: o despacho roda por
 * cron/tarefa fora de sessão e atravessa famílias; o familia_id é sempre
 * gravado explicitamente em cada linha.
 */
final class RepositorioFilaNotificacoes extends RepositorioSistema
{
    public function enfileirar(int $familiaId, string $evento, string $canal, string $destinatario, array $payload, ?string $agendadoPara = null): int
    {
        $this->executar(
            'INSERT INTO fila_notificacoes (familia_id, evento, canal, destinatario, payload, agendado_para)
             VALUES (:familia, :evento, :canal, :destinatario, :payload, COALESCE(:agendado, NOW()))',
            [
                'familia' => $familiaId, 'evento' => $evento, 'canal' => $canal,
                'destinatario' => $destinatario,
                'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
                'agendado' => $agendadoPara,
            ]
        );
        return (int)$this->bd->lastInsertId();
    }

    /** @return array<int,array<string,mixed>> */
    public function pendentesParaEnvio(int $limite = 50): array
    {
        return $this->executar(
            'SELECT * FROM fila_notificacoes
              WHERE status = \'pendente\' AND agendado_para <= NOW()
              ORDER BY agendado_para
              LIMIT ' . max(1, min(200, $limite))
        )->fetchAll();
    }

    public function marcarEnviando(int $id): void
    {
        $this->executar('UPDATE fila_notificacoes SET status = \'enviando\' WHERE id = :id', ['id' => $id]);
    }

    public function marcarEnviada(int $id): void
    {
        $this->executar(
            'UPDATE fila_notificacoes SET status = \'enviada\', enviado_em = NOW() WHERE id = :id',
            ['id' => $id]
        );
    }

    /** Falha com backoff exponencial; após 3 tentativas a linha morre como \'falha\'. */
    public function registrarFalha(int $id, string $erro): void
    {
        $this->executar(
            'UPDATE fila_notificacoes
                SET tentativas = tentativas + 1,
                    ultimo_erro = :erro,
                    status = IF(tentativas + 1 >= 3, \'falha\', \'pendente\'),
                    agendado_para = DATE_ADD(NOW(), INTERVAL (5 * POW(2, tentativas)) MINUTE)
              WHERE id = :id',
            ['id' => $id, 'erro' => mb_substr($erro, 0, 500)]
        );
    }

    /** Já existe alerta deste evento criado recentemente? (anti-spam da omissão) */
    public function eventoRecente(int $familiaId, string $evento, int $minutos): bool
    {
        return $this->buscarUm(
            'SELECT id FROM fila_notificacoes
              WHERE familia_id = :familia AND evento = :evento
                AND criado_em > DATE_SUB(NOW(), INTERVAL :minutos MINUTE)
              LIMIT 1',
            ['familia' => $familiaId, 'evento' => $evento, 'minutos' => $minutos]
        ) !== null;
    }

    public function buscar(int $id): ?array
    {
        return $this->buscarUm('SELECT * FROM fila_notificacoes WHERE id = :id LIMIT 1', ['id' => $id]);
    }
}
