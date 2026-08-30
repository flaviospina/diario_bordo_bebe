<?php

declare(strict_types=1);

namespace App\Repositories;

/**
 * Log de acessos e ações (base da tela de auditoria).
 * Sem tenant obrigatório: eventos pré-login (falha de senha) não têm família.
 * Tabela é só-INSERT — nenhum caminho de código atualiza ou apaga linhas.
 */
final class RepositorioLogAcessos extends RepositorioSistema
{
    public function registrar(
        ?int $familiaId,
        ?int $usuarioId,
        string $acao,
        ?string $entidade = null,
        ?int $entidadeId = null,
        ?string $ip = null,
        ?string $userAgent = null
    ): void {
        $this->executar(
            'INSERT INTO log_acessos (familia_id, usuario_id, acao, entidade, entidade_id, ip, user_agent)
             VALUES (:familia_id, :usuario_id, :acao, :entidade, :entidade_id, :ip, :user_agent)',
            [
                'familia_id'  => $familiaId,
                'usuario_id'  => $usuarioId,
                'acao'        => $acao,
                'entidade'    => $entidade,
                'entidade_id' => $entidadeId,
                'ip'          => $ip,
                'user_agent'  => $userAgent,
            ]
        );
    }
}
