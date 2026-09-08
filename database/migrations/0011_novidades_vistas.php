<?php

declare(strict_types=1);

/**
 * 0011 — Controle rigoroso do aviso de novidades:
 * uma linha por (novidade, usuário) registrando quando e de onde o usuário
 * confirmou o aviso. O aviso aparece no primeiro login após a publicação e
 * some definitivamente após o "Entendi" — nunca antes, nunca de novo.
 */

return [
    'descricao' => 'Aviso de novidades no login: tabela novidades_vistas (novidade × usuário)',

    'sql' => [
        "CREATE TABLE IF NOT EXISTS novidades_vistas (
            id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            novidade_id BIGINT UNSIGNED NOT NULL,
            usuario_id  BIGINT UNSIGNED NOT NULL,
            visto_em    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            ip          VARCHAR(45)     NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uq_novidade_usuario (novidade_id, usuario_id),
            KEY ix_vistas_usuario (usuario_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    ],
];
