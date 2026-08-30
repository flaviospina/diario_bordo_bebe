<?php

declare(strict_types=1);

/**
 * Migração 0002 — configurações da família, versionadas.
 */
return [
    'descricao' => 'Configurações da família com histórico de alterações',
    'sql' => [
        "CREATE TABLE IF NOT EXISTS configuracoes_familia (
            id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            familia_id      BIGINT UNSIGNED NOT NULL,
            chave           VARCHAR(60)     NOT NULL,
            valor           JSON            NOT NULL,
            atualizado_por  BIGINT UNSIGNED NULL,
            atualizado_em   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_config_familia_chave (familia_id, chave),
            CONSTRAINT fk_config_familia FOREIGN KEY (familia_id) REFERENCES familias(id),
            CONSTRAINT fk_config_usuario FOREIGN KEY (atualizado_por) REFERENCES usuarios(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS configuracoes_historico (
            id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            familia_id      BIGINT UNSIGNED NOT NULL,
            chave           VARCHAR(60)     NOT NULL,
            valor_anterior  JSON            NULL,
            valor_novo      JSON            NOT NULL,
            usuario_id      BIGINT UNSIGNED NOT NULL,
            criado_em       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY ix_confhist_familia (familia_id, chave, criado_em),
            CONSTRAINT fk_confhist_familia FOREIGN KEY (familia_id) REFERENCES familias(id),
            CONSTRAINT fk_confhist_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    ],
];
