<?php

declare(strict_types=1);

namespace App\Repositories;

/**
 * Perfis complementares dos responsáveis — TODOS os campos opcionais.
 * Escopo por usuário (o dono do perfil), validado contra a família na service.
 */
final class RepositorioPerfis extends RepositorioSistema
{
    public function buscarPorUsuario(int $usuarioId): ?array
    {
        return $this->buscarUm(
            'SELECT * FROM perfis_responsaveis WHERE usuario_id = :usuario LIMIT 1',
            ['usuario' => $usuarioId]
        );
    }

    /** @param array<string,mixed> $dados */
    public function salvar(int $usuarioId, array $dados): void
    {
        $this->executar(
            'INSERT INTO perfis_responsaveis
                (usuario_id, cpf, data_nascimento, profissao, telefone_alternativo, endereco,
                 contato_emergencia_nome, contato_emergencia_telefone, observacoes)
             VALUES (:usuario, :cpf, :nascimento, :profissao, :telefone, :endereco,
                     :emergencia_nome, :emergencia_telefone, :observacoes)
             ON DUPLICATE KEY UPDATE
                cpf = VALUES(cpf), data_nascimento = VALUES(data_nascimento),
                profissao = VALUES(profissao), telefone_alternativo = VALUES(telefone_alternativo),
                endereco = VALUES(endereco), contato_emergencia_nome = VALUES(contato_emergencia_nome),
                contato_emergencia_telefone = VALUES(contato_emergencia_telefone),
                observacoes = VALUES(observacoes)',
            [
                'usuario'             => $usuarioId,
                'cpf'                 => $dados['cpf'] ?? null,
                'nascimento'          => $dados['data_nascimento'] ?? null,
                'profissao'           => $dados['profissao'] ?? null,
                'telefone'            => $dados['telefone_alternativo'] ?? null,
                'endereco'            => $dados['endereco'] ?? null,
                'emergencia_nome'     => $dados['contato_emergencia_nome'] ?? null,
                'emergencia_telefone' => $dados['contato_emergencia_telefone'] ?? null,
                'observacoes'         => $dados['observacoes'] ?? null,
            ]
        );
    }
}
