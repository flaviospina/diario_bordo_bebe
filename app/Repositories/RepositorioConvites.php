<?php

declare(strict_types=1);

namespace App\Repositories;

/**
 * Convites de entrada no sistema (não há cadastro público na v1).
 * Busca por hash do token — o token puro só existe no link enviado.
 */
final class RepositorioConvites extends RepositorioSistema
{
    public function buscarValidoPorTokenHash(string $tokenHash): ?array
    {
        return $this->buscarUm(
            'SELECT c.*, f.nome AS familia_nome
               FROM convites c
               JOIN familias f ON f.id = c.familia_id
              WHERE c.token_hash = :hash
                AND c.aceito_em IS NULL
                AND c.expira_em > NOW()
              LIMIT 1',
            ['hash' => $tokenHash]
        );
    }

    public function marcarAceito(int $conviteId, int $usuarioCriadoId): void
    {
        $this->executar(
            'UPDATE convites SET aceito_em = NOW(), usuario_criado_id = :usuario
              WHERE id = :id AND aceito_em IS NULL',
            ['usuario' => $usuarioCriadoId, 'id' => $conviteId]
        );
    }
}
