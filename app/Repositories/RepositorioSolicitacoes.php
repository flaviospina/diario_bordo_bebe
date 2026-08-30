<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Identificadores;

final class RepositorioSolicitacoes extends RepositorioBase
{
    public function criar(int $registroId, int $solicitanteId, string $tipo, string $motivo, array $payload): string
    {
        $codigo = Identificadores::codigoPublico();
        $this->executar(
            'INSERT INTO solicitacoes_edicao (codigo_publico, familia_id, registro_id, solicitante_id,
                                              tipo, motivo, payload_proposto)
             VALUES (:codigo, :familia_id, :registro, :solicitante, :tipo, :motivo, :payload)',
            [
                'codigo'      => $codigo,
                'registro'    => $registroId,
                'solicitante' => $solicitanteId,
                'tipo'        => $tipo,
                'motivo'      => $motivo,
                'payload'     => json_encode($payload, JSON_UNESCAPED_UNICODE),
            ]
        );
        return $codigo;
    }

    public function buscarPorCodigo(string $codigo): ?array
    {
        return $this->buscarUm(
            'SELECT s.*, u.nome AS solicitante_nome, r.codigo_publico AS registro_codigo
               FROM solicitacoes_edicao s
               JOIN usuarios u ON u.id = s.solicitante_id
               JOIN registros r ON r.id = s.registro_id
              WHERE s.familia_id = :familia_id AND s.codigo_publico = :codigo
              LIMIT 1',
            ['codigo' => $codigo]
        );
    }

    /** @return array<int,array<string,mixed>> */
    public function listar(?string $status = null, int $limite = 100): array
    {
        $filtro = $status !== null ? 'AND s.status = :status' : '';
        $parametros = $status !== null ? ['status' => $status] : [];
        return $this->buscarTodos(
            "SELECT s.*, u.nome AS solicitante_nome, r.codigo_publico AS registro_codigo,
                    c.nome AS categoria_nome, cr.nome AS crianca_nome
               FROM solicitacoes_edicao s
               JOIN usuarios u ON u.id = s.solicitante_id
               JOIN registros r ON r.id = s.registro_id
               JOIN categorias c ON c.id = r.categoria_id
               JOIN criancas cr ON cr.id = r.crianca_id
              WHERE s.familia_id = :familia_id {$filtro}
              ORDER BY s.criado_em DESC
              LIMIT " . max(1, min(500, $limite)),
            $parametros
        );
    }

    public function contarPendentes(): int
    {
        $linha = $this->buscarUm(
            'SELECT COUNT(*) AS total FROM solicitacoes_edicao
              WHERE familia_id = :familia_id AND status = \'pendente\''
        );
        return (int)($linha['total'] ?? 0);
    }

    public function decidir(int $id, string $status, int $decididoPor, ?string $resposta): void
    {
        $this->executar(
            'UPDATE solicitacoes_edicao
                SET status = :status, decidido_por = :decisor, decidido_em = NOW(), resposta = :resposta
              WHERE familia_id = :familia_id AND id = :id AND status = \'pendente\'',
            ['id' => $id, 'status' => $status, 'decisor' => $decididoPor, 'resposta' => $resposta]
        );
    }
}
