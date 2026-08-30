<?php

declare(strict_types=1);

/**
 * Migração 0007 — tabelas de suporte ao acesso: convites, tokens de senha,
 * tentativas de login (rate limit) e arquivos gerados (downloads).
 */
return [
    'descricao' => 'Convites, tokens_senha, tentativas_login, arquivos_gerados',
    'sql' => [
        "CREATE TABLE IF NOT EXISTS convites (
            id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            familia_id      BIGINT UNSIGNED NOT NULL,
            email           VARCHAR(190)    NOT NULL,
            papel           ENUM('admin_familia','responsavel','cuidador','leitor') NOT NULL,
            token_hash      CHAR(64)        NOT NULL,
            convidado_por   BIGINT UNSIGNED NOT NULL,
            expira_em       DATETIME        NOT NULL,
            aceito_em       DATETIME        NULL,
            usuario_criado_id BIGINT UNSIGNED NULL,
            criado_em       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_convites_token (token_hash),
            KEY ix_convites_familia (familia_id, aceito_em),
            CONSTRAINT fk_convites_familia FOREIGN KEY (familia_id) REFERENCES familias(id),
            CONSTRAINT fk_convites_autor   FOREIGN KEY (convidado_por) REFERENCES usuarios(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS tokens_senha (
            id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            usuario_id      BIGINT UNSIGNED NOT NULL,
            token_hash      CHAR(64)        NOT NULL,
            expira_em       DATETIME        NOT NULL,
            usado_em        DATETIME        NULL,
            criado_em       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_tokens_senha (token_hash),
            KEY ix_tokens_usuario (usuario_id),
            CONSTRAINT fk_tokens_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS tentativas_login (
            id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            email           VARCHAR(190)    NOT NULL,
            ip              VARCHAR(45)     NOT NULL,
            sucesso         TINYINT(1)      NOT NULL DEFAULT 0,
            criado_em       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY ix_tentativas_email (email, criado_em),
            KEY ix_tentativas_ip (ip, criado_em)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS arquivos_gerados (
            id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            codigo_publico  CHAR(12)        NOT NULL,
            familia_id      BIGINT UNSIGNED NOT NULL,
            tipo            ENUM('pdf','csv') NOT NULL,
            descricao       VARCHAR(160)    NOT NULL,
            caminho         VARCHAR(255)    NOT NULL,
            gerado_por      BIGINT UNSIGNED NOT NULL,
            expira_em       DATETIME        NULL,
            criado_em       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_arquivos_codigo (codigo_publico),
            KEY ix_arquivos_familia (familia_id, criado_em),
            CONSTRAINT fk_arquivos_familia FOREIGN KEY (familia_id) REFERENCES familias(id),
            CONSTRAINT fk_arquivos_usuario FOREIGN KEY (gerado_por) REFERENCES usuarios(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    ],
];
