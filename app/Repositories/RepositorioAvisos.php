<?php

declare(strict_types=1);

namespace App\Repositories;

/**
 * Central de avisos (sino no topo): um aviso por usuário destinatário.
 */
final class RepositorioAvisos extends RepositorioBase
{
    public function criar(int $usuarioId, string $tipo, string $titulo, ?string $mensagem, ?string $url): int
    {
        $this->executar(
            'INSERT INTO avisos (familia_id, usuario_id, tipo, titulo, mensagem, url)
             VALUES (:familia_id, :usuario, :tipo, :titulo, :mensagem, :url)',
            [
                'usuario' => $usuarioId,
                'tipo' => mb_substr($tipo, 0, 40),
                'titulo' => mb_substr($titulo, 0, 160),
                'mensagem' => $mensagem !== null ? mb_substr($mensagem, 0, 2000) : null,
                'url' => $url !== null ? mb_substr($url, 0, 255) : null,
            ]
        );
        return $this->ultimoId();
    }

    public function contarNaoLidos(int $usuarioId): int
    {
        $linha = $this->buscarUm(
            'SELECT COUNT(*) AS total FROM avisos
              WHERE familia_id = :familia_id AND usuario_id = :usuario AND lido_em IS NULL',
            ['usuario' => $usuarioId]
        );
        return (int)($linha['total'] ?? 0);
    }

    /** @return array<int,array<string,mixed>> mais recentes primeiro */
    public function listar(int $usuarioId, int $limite = 60): array
    {
        return $this->buscarTodos(
            'SELECT * FROM avisos
              WHERE familia_id = :familia_id AND usuario_id = :usuario
              ORDER BY criado_em DESC, id DESC
              LIMIT ' . max(1, min(200, $limite)),
            ['usuario' => $usuarioId]
        );
    }

    public function marcarTodosLidos(int $usuarioId): void
    {
        $this->executar(
            'UPDATE avisos SET lido_em = NOW()
              WHERE familia_id = :familia_id AND usuario_id = :usuario AND lido_em IS NULL',
            ['usuario' => $usuarioId]
        );
    }
}
