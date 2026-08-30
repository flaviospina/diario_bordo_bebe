<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Identificadores;

final class RepositorioIntercorrencias extends RepositorioBase
{
    public function criar(int $criancaId, int $usuarioId, string $ocorridoEm, string $gravidade, string $descricao, ?string $acaoTomada): string
    {
        $codigo = Identificadores::codigoPublico();
        $this->executar(
            'INSERT INTO intercorrencias (codigo_publico, familia_id, crianca_id, usuario_id,
                                          ocorrido_em, gravidade, descricao, acao_tomada)
             VALUES (:codigo, :familia_id, :crianca, :usuario, :ocorrido, :gravidade, :descricao, :acao)',
            [
                'codigo' => $codigo, 'crianca' => $criancaId, 'usuario' => $usuarioId,
                'ocorrido' => $ocorridoEm, 'gravidade' => $gravidade,
                'descricao' => $descricao, 'acao' => $acaoTomada,
            ]
        );
        return $codigo;
    }

    public function buscarPorCodigo(string $codigo): ?array
    {
        return $this->buscarUm(
            'SELECT i.*, cr.nome AS crianca_nome, u.nome AS usuario_nome, uc.nome AS ciencia_nome
               FROM intercorrencias i
               JOIN criancas cr ON cr.id = i.crianca_id
               JOIN usuarios u ON u.id = i.usuario_id
               LEFT JOIN usuarios uc ON uc.id = i.ciencia_usuario_id
              WHERE i.familia_id = :familia_id AND i.codigo_publico = :codigo
              LIMIT 1',
            ['codigo' => $codigo]
        );
    }

    /** @return array<int,array<string,mixed>> */
    public function listar(int $limite = 50): array
    {
        return $this->buscarTodos(
            'SELECT i.*, cr.nome AS crianca_nome, u.nome AS usuario_nome
               FROM intercorrencias i
               JOIN criancas cr ON cr.id = i.crianca_id
               JOIN usuarios u ON u.id = i.usuario_id
              WHERE i.familia_id = :familia_id
              ORDER BY i.ocorrido_em DESC
              LIMIT ' . max(1, min(500, $limite))
        );
    }

    public function contarSemCiencia(): int
    {
        $linha = $this->buscarUm(
            'SELECT COUNT(*) AS total FROM intercorrencias
              WHERE familia_id = :familia_id AND ciencia_em IS NULL'
        );
        return (int)($linha['total'] ?? 0);
    }

    public function registrarCiencia(int $id, int $usuarioId): void
    {
        $this->executar(
            'UPDATE intercorrencias SET ciencia_usuario_id = :usuario, ciencia_em = NOW()
              WHERE familia_id = :familia_id AND id = :id AND ciencia_em IS NULL',
            ['id' => $id, 'usuario' => $usuarioId]
        );
    }
}
