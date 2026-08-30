<?php

declare(strict_types=1);

namespace App\Repositories;

/**
 * Auditoria imutável dos registros: só INSERT e SELECT — nenhum caminho de
 * código atualiza ou apaga versões (regra 8.1).
 */
final class RepositorioVersoes extends RepositorioSistema
{
    /** @param array<string,mixed> $anterior @param array<string,mixed> $novo */
    public function gravar(int $registroId, int $usuarioId, array $anterior, array $novo, ?string $motivo, ?string $ip): void
    {
        $this->executar(
            'INSERT INTO registro_versoes (registro_id, usuario_id, dados_anteriores, dados_novos, motivo, ip)
             VALUES (:registro, :usuario, :anterior, :novo, :motivo, :ip)',
            [
                'registro' => $registroId,
                'usuario'  => $usuarioId,
                'anterior' => json_encode($anterior, JSON_UNESCAPED_UNICODE),
                'novo'     => json_encode($novo, JSON_UNESCAPED_UNICODE),
                'motivo'   => $motivo !== null ? mb_substr($motivo, 0, 255) : null,
                'ip'       => $ip,
            ]
        );
    }

    /** @return array<int,array<string,mixed>> */
    public function listarDoRegistro(int $registroId): array
    {
        return $this->executar(
            'SELECT v.*, u.nome AS usuario_nome
               FROM registro_versoes v JOIN usuarios u ON u.id = v.usuario_id
              WHERE v.registro_id = :registro
              ORDER BY v.criado_em DESC, v.id DESC',
            ['registro' => $registroId]
        )->fetchAll();
    }

    /** @return array<int,array<string,mixed>> últimas versões da família (auditoria) */
    public function listarDaFamilia(int $familiaId, int $limite = 100): array
    {
        return $this->executar(
            'SELECT v.*, u.nome AS usuario_nome, r.codigo_publico AS registro_codigo,
                    c.nome AS categoria_nome, cr.nome AS crianca_nome
               FROM registro_versoes v
               JOIN usuarios u ON u.id = v.usuario_id
               JOIN registros r ON r.id = v.registro_id
               JOIN categorias c ON c.id = r.categoria_id
               JOIN criancas cr ON cr.id = r.crianca_id
              WHERE r.familia_id = :familia
              ORDER BY v.criado_em DESC, v.id DESC
              LIMIT ' . max(1, min(500, $limite)),
            ['familia' => $familiaId]
        )->fetchAll();
    }
}
