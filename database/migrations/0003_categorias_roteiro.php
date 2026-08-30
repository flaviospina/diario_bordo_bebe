<?php

declare(strict_types=1);

/**
 * Migração 0003 — catálogo de categorias e roteiro prescrito pelos pais.
 */
return [
    'descricao' => 'Categorias (globais e por família) e roteiro_blocos',
    'sql' => [
        "CREATE TABLE IF NOT EXISTS categorias (
            id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            familia_id      BIGINT UNSIGNED NULL,
            grupo           VARCHAR(40)     NOT NULL,
            nome            VARCHAR(80)     NOT NULL,
            slug            VARCHAR(80)     NOT NULL,
            icone           VARCHAR(40)     NULL,
            cor             CHAR(7)         NULL,
            schema_campos   JSON            NOT NULL,
            ordem           SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            ativo           TINYINT(1)      NOT NULL DEFAULT 1,
            PRIMARY KEY (id),
            UNIQUE KEY uq_categorias_slug (familia_id, slug),
            KEY ix_categorias_grupo (grupo, ordem),
            CONSTRAINT fk_categorias_familia FOREIGN KEY (familia_id) REFERENCES familias(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS roteiro_blocos (
            id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            familia_id      BIGINT UNSIGNED NOT NULL,
            crianca_id      BIGINT UNSIGNED NOT NULL,
            dias_semana     SET('dom','seg','ter','qua','qui','sex','sab') NOT NULL,
            hora_inicio     TIME            NOT NULL,
            hora_fim        TIME            NOT NULL,
            titulo          VARCHAR(120)    NOT NULL,
            categoria_id    BIGINT UNSIGNED NULL,
            instrucao       TEXT            NULL,
            obrigatorio     TINYINT(1)      NOT NULL DEFAULT 0,
            ativo           TINYINT(1)      NOT NULL DEFAULT 1,
            PRIMARY KEY (id),
            KEY ix_roteiro_crianca (familia_id, crianca_id, ativo, hora_inicio),
            CONSTRAINT fk_roteiro_familia  FOREIGN KEY (familia_id)  REFERENCES familias(id),
            CONSTRAINT fk_roteiro_crianca  FOREIGN KEY (crianca_id)  REFERENCES criancas(id),
            CONSTRAINT fk_roteiro_categoria FOREIGN KEY (categoria_id) REFERENCES categorias(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    ],
];
