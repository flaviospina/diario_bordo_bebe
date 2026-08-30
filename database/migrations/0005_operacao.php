<?php

declare(strict_types=1);

/**
 * Migração 0005 — intercorrências, turnos e suprimentos.
 */
return [
    'descricao' => 'Intercorrências (com ciência dos pais), turnos e suprimentos',
    'sql' => [
        "CREATE TABLE IF NOT EXISTS intercorrencias (
            id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            codigo_publico  CHAR(12)        NOT NULL,
            familia_id      BIGINT UNSIGNED NOT NULL,
            crianca_id      BIGINT UNSIGNED NOT NULL,
            usuario_id      BIGINT UNSIGNED NOT NULL,
            ocorrido_em     DATETIME        NOT NULL,
            gravidade       ENUM('leve','moderada','grave') NOT NULL,
            descricao       TEXT            NOT NULL,
            acao_tomada     TEXT            NULL,
            ciencia_usuario_id BIGINT UNSIGNED NULL,
            ciencia_em      DATETIME        NULL,
            criado_em       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_intercorrencias_codigo (codigo_publico),
            KEY ix_intercorrencias_familia (familia_id, ocorrido_em),
            KEY ix_intercorrencias_sem_ciencia (familia_id, ciencia_em),
            CONSTRAINT fk_interc_familia FOREIGN KEY (familia_id) REFERENCES familias(id),
            CONSTRAINT fk_interc_crianca FOREIGN KEY (crianca_id) REFERENCES criancas(id),
            CONSTRAINT fk_interc_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
            CONSTRAINT fk_interc_ciencia FOREIGN KEY (ciencia_usuario_id) REFERENCES usuarios(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS turnos (
            id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            familia_id      BIGINT UNSIGNED NOT NULL,
            usuario_id      BIGINT UNSIGNED NOT NULL,
            entrada         DATETIME        NOT NULL,
            saida           DATETIME        NULL,
            entrada_manual  TINYINT(1)      NOT NULL DEFAULT 0,
            observacao      TEXT            NULL,
            PRIMARY KEY (id),
            KEY ix_turnos_familia (familia_id, entrada),
            KEY ix_turnos_usuario (usuario_id, entrada),
            CONSTRAINT fk_turnos_familia FOREIGN KEY (familia_id) REFERENCES familias(id),
            CONSTRAINT fk_turnos_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS suprimentos (
            id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            familia_id      BIGINT UNSIGNED NOT NULL,
            item            VARCHAR(120)    NOT NULL,
            nivel           ENUM('ok','baixo','acabou') NOT NULL DEFAULT 'baixo',
            solicitado_por  BIGINT UNSIGNED NOT NULL,
            solicitado_em   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            resolvido_em    DATETIME        NULL,
            PRIMARY KEY (id),
            KEY ix_suprimentos_abertos (familia_id, resolvido_em),
            CONSTRAINT fk_supr_familia FOREIGN KEY (familia_id) REFERENCES familias(id),
            CONSTRAINT fk_supr_usuario FOREIGN KEY (solicitado_por) REFERENCES usuarios(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    ],
];
