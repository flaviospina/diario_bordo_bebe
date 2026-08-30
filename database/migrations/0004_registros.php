<?php

declare(strict_types=1);

/**
 * Migração 0004 — registros efetivos, versões (auditoria imutável),
 * fotos e solicitações de edição.
 */
return [
    'descricao' => 'Registros, registro_versoes, registro_fotos, solicitacoes_edicao',
    'sql' => [
        "CREATE TABLE IF NOT EXISTS registros (
            id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            codigo_publico  CHAR(12)        NOT NULL,
            uuid_cliente    CHAR(36)        NOT NULL,
            familia_id      BIGINT UNSIGNED NOT NULL,
            crianca_id      BIGINT UNSIGNED NOT NULL,
            categoria_id    BIGINT UNSIGNED NOT NULL,
            roteiro_bloco_id BIGINT UNSIGNED NULL,
            usuario_id      BIGINT UNSIGNED NOT NULL,
            inicio          DATETIME        NOT NULL,
            fim             DATETIME        NULL,
            dados           JSON            NULL,
            observacao      TEXT            NULL,
            status          ENUM('feito','nao_feito','parcial') NOT NULL DEFAULT 'feito',
            justificativa   TEXT            NULL,
            origem          ENUM('online','offline') NOT NULL DEFAULT 'online',
            excluido_em     DATETIME        NULL,
            excluido_por    BIGINT UNSIGNED NULL,
            motivo_exclusao TEXT            NULL,
            criado_em       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            atualizado_em   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_registros_uuid (uuid_cliente),
            UNIQUE KEY uq_registros_codigo (codigo_publico),
            KEY ix_registros_dia (familia_id, crianca_id, inicio),
            KEY ix_registros_categoria (familia_id, categoria_id, inicio),
            KEY ix_registros_omissao (familia_id, criado_em),
            CONSTRAINT fk_registros_familia   FOREIGN KEY (familia_id)   REFERENCES familias(id),
            CONSTRAINT fk_registros_crianca   FOREIGN KEY (crianca_id)   REFERENCES criancas(id),
            CONSTRAINT fk_registros_categoria FOREIGN KEY (categoria_id) REFERENCES categorias(id),
            CONSTRAINT fk_registros_bloco     FOREIGN KEY (roteiro_bloco_id) REFERENCES roteiro_blocos(id),
            CONSTRAINT fk_registros_usuario   FOREIGN KEY (usuario_id)   REFERENCES usuarios(id),
            CONSTRAINT fk_registros_excluidor FOREIGN KEY (excluido_por) REFERENCES usuarios(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS registro_fotos (
            id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            codigo_publico  CHAR(12)        NOT NULL,
            registro_id     BIGINT UNSIGNED NOT NULL,
            caminho         VARCHAR(255)    NOT NULL,
            thumb           VARCHAR(255)    NULL,
            criado_em       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_fotos_codigo (codigo_publico),
            KEY ix_fotos_registro (registro_id),
            CONSTRAINT fk_fotos_registro FOREIGN KEY (registro_id) REFERENCES registros(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS registro_versoes (
            id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            registro_id     BIGINT UNSIGNED NOT NULL,
            usuario_id      BIGINT UNSIGNED NOT NULL,
            dados_anteriores JSON           NOT NULL,
            dados_novos     JSON            NOT NULL,
            motivo          VARCHAR(255)    NULL,
            ip              VARCHAR(45)     NULL,
            criado_em       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY ix_versoes_registro (registro_id, criado_em),
            CONSTRAINT fk_versoes_registro FOREIGN KEY (registro_id) REFERENCES registros(id),
            CONSTRAINT fk_versoes_usuario  FOREIGN KEY (usuario_id)  REFERENCES usuarios(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS solicitacoes_edicao (
            id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            codigo_publico  CHAR(12)        NOT NULL,
            familia_id      BIGINT UNSIGNED NOT NULL,
            registro_id     BIGINT UNSIGNED NOT NULL,
            solicitante_id  BIGINT UNSIGNED NOT NULL,
            tipo            ENUM('edicao','exclusao','conflito_sync') NOT NULL DEFAULT 'edicao',
            motivo          TEXT            NOT NULL,
            payload_proposto JSON           NOT NULL,
            status          ENUM('pendente','aprovada','recusada') NOT NULL DEFAULT 'pendente',
            decidido_por    BIGINT UNSIGNED NULL,
            decidido_em     DATETIME        NULL,
            resposta        TEXT            NULL,
            criado_em       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_solicitacoes_codigo (codigo_publico),
            KEY ix_solicitacoes_pendentes (familia_id, status, criado_em),
            CONSTRAINT fk_solic_familia     FOREIGN KEY (familia_id)     REFERENCES familias(id),
            CONSTRAINT fk_solic_registro    FOREIGN KEY (registro_id)    REFERENCES registros(id),
            CONSTRAINT fk_solic_solicitante FOREIGN KEY (solicitante_id) REFERENCES usuarios(id),
            CONSTRAINT fk_solic_decisor     FOREIGN KEY (decidido_por)   REFERENCES usuarios(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    ],
];
