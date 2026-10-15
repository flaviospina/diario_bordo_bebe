<?php

declare(strict_types=1);

namespace App\Repositories;

/**
 * Inscrições de Web Push (um usuário pode ter vários aparelhos).
 * Sem filtro de família na busca por usuário: a inscrição pertence ao
 * usuário, e o usuário pertence a uma única família.
 */
final class RepositorioPushInscricoes extends RepositorioSistema
{
    public function salvar(int $familiaId, int $usuarioId, string $endpoint, string $p256dh, string $auth, ?string $userAgent): void
    {
        $this->executar(
            'INSERT INTO push_inscricoes (familia_id, usuario_id, endpoint, endpoint_hash, chave_p256dh, chave_auth, user_agent)
             VALUES (:familia, :usuario, :endpoint, :hash, :p256dh, :auth, :ua)
             ON DUPLICATE KEY UPDATE usuario_id = VALUES(usuario_id), familia_id = VALUES(familia_id),
                 chave_p256dh = VALUES(chave_p256dh), chave_auth = VALUES(chave_auth),
                 user_agent = VALUES(user_agent), falhas = 0',
            [
                'familia' => $familiaId,
                'usuario' => $usuarioId,
                'endpoint' => $endpoint,
                'hash' => hash('sha256', $endpoint),
                'p256dh' => mb_substr($p256dh, 0, 120),
                'auth' => mb_substr($auth, 0, 60),
                'ua' => $userAgent !== null ? mb_substr($userAgent, 0, 255) : null,
            ]
        );
    }

    public function removerPorEndpoint(string $endpoint): void
    {
        $this->executar('DELETE FROM push_inscricoes WHERE endpoint_hash = :hash', ['hash' => hash('sha256', $endpoint)]);
    }

    public function remover(int $id): void
    {
        $this->executar('DELETE FROM push_inscricoes WHERE id = :id', ['id' => $id]);
    }

    /** @return array<int,array<string,mixed>> */
    public function doUsuario(int $usuarioId): array
    {
        return $this->executar(
            'SELECT * FROM push_inscricoes WHERE usuario_id = :usuario ORDER BY id',
            ['usuario' => $usuarioId]
        )->fetchAll();
    }

    /** @param int[] $usuarioIds @return array<int,array<string,mixed>> */
    public function dosUsuarios(array $usuarioIds): array
    {
        $usuarioIds = array_values(array_unique(array_map('intval', $usuarioIds)));
        if ($usuarioIds === []) {
            return [];
        }
        $marcadores = implode(',', array_fill(0, count($usuarioIds), '?'));
        $declaracao = $this->bd->prepare("SELECT * FROM push_inscricoes WHERE usuario_id IN ({$marcadores}) ORDER BY id");
        $declaracao->execute($usuarioIds);
        return $declaracao->fetchAll();
    }

    public function registrarResultado(int $id, bool $sucesso): void
    {
        $this->executar(
            $sucesso
                ? 'UPDATE push_inscricoes SET falhas = 0, ultimo_envio_em = NOW() WHERE id = :id'
                : 'UPDATE push_inscricoes SET falhas = falhas + 1 WHERE id = :id',
            ['id' => $id]
        );
    }

    public function temInscricao(int $usuarioId): bool
    {
        return $this->buscarUm('SELECT id FROM push_inscricoes WHERE usuario_id = :usuario LIMIT 1', ['usuario' => $usuarioId]) !== null;
    }
}
