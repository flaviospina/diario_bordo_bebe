<?php

declare(strict_types=1);

namespace App\Repositories;

/**
 * Configurações da família (chave => valor JSON), sempre versionadas:
 * toda gravação registra o valor anterior em configuracoes_historico.
 */
final class RepositorioConfiguracoes extends RepositorioBase
{
    /** @return array<string,mixed> chave => valor decodificado */
    public function todas(): array
    {
        $linhas = $this->buscarTodos(
            'SELECT chave, valor FROM configuracoes_familia WHERE familia_id = :familia_id'
        );
        $config = [];
        foreach ($linhas as $linha) {
            $config[$linha['chave']] = json_decode((string)$linha['valor'], true);
        }
        return $config;
    }

    public function obter(string $chave): mixed
    {
        $linha = $this->buscarUm(
            'SELECT valor FROM configuracoes_familia WHERE familia_id = :familia_id AND chave = :chave',
            ['chave' => $chave]
        );
        return $linha === null ? null : json_decode((string)$linha['valor'], true);
    }

    public function salvar(string $chave, mixed $valor, int $usuarioId): void
    {
        $anterior = $this->obter($chave);
        $novoJson = json_encode($valor, JSON_UNESCAPED_UNICODE);
        if ($anterior !== null && json_encode($anterior, JSON_UNESCAPED_UNICODE) === $novoJson) {
            return; // nada mudou; não polui o histórico
        }

        $this->executar(
            'INSERT INTO configuracoes_familia (familia_id, chave, valor, atualizado_por)
             VALUES (:familia_id, :chave, :valor, :usuario)
             ON DUPLICATE KEY UPDATE valor = VALUES(valor), atualizado_por = VALUES(atualizado_por)',
            ['chave' => $chave, 'valor' => $novoJson, 'usuario' => $usuarioId]
        );
        $this->executar(
            'INSERT INTO configuracoes_historico (familia_id, chave, valor_anterior, valor_novo, usuario_id)
             VALUES (:familia_id, :chave, :anterior, :novo, :usuario)',
            [
                'chave'    => $chave,
                'anterior' => $anterior === null ? null : json_encode($anterior, JSON_UNESCAPED_UNICODE),
                'novo'     => $novoJson,
                'usuario'  => $usuarioId,
            ]
        );
    }

    /** @return array<int,array<string,mixed>> */
    public function historico(int $limite = 100): array
    {
        return $this->buscarTodos(
            'SELECT h.*, u.nome AS usuario_nome
               FROM configuracoes_historico h
               JOIN usuarios u ON u.id = h.usuario_id
              WHERE h.familia_id = :familia_id
              ORDER BY h.criado_em DESC, h.id DESC
              LIMIT ' . max(1, $limite)
        );
    }
}
