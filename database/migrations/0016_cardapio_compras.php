<?php

declare(strict_types=1);

/**
 * 0016 — Rodada 6: cardápio semanal (montado pelos pais) + lista de compras
 * da semana (checklist "colocado no carrinho", compartilhado pela família).
 *
 * cardapios_semanas — uma semana por criança e data de início (sem duplicar);
 * cardapio_dias     — fruta da manhã, fruta da tarde, almoço e jantar de cada
 *                     dia (texto livre dos pais + slugs do catálogo derivados,
 *                     usados para pré-preencher o registro do almoço);
 * compras_itens     — itens da lista com identificador estável (herdado do
 *                     HTML original), categoria, quantidade de COMPRA, nota,
 *                     ordem e o estado "comprado" com quem/quando marcou.
 */

return [
    'descricao' => 'Rodada 6: cardapios_semanas, cardapio_dias e compras_itens',

    'sql' => [
        "CREATE TABLE IF NOT EXISTS cardapios_semanas (
            id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            familia_id  BIGINT UNSIGNED NOT NULL,
            crianca_id  BIGINT UNSIGNED NOT NULL,
            titulo      VARCHAR(120)    NOT NULL,
            inicio      DATE            NOT NULL,
            fim         DATE            NOT NULL,
            origem      ENUM('modelo','manual') NOT NULL DEFAULT 'manual',
            descricao   VARCHAR(255)    NULL,
            criado_por  BIGINT UNSIGNED NULL,
            criado_em   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            atualizado_em DATETIME      NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uq_semana_crianca_inicio (familia_id, crianca_id, inicio),
            KEY ix_semana_familia (familia_id, inicio)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS cardapio_dias (
            id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            semana_id     BIGINT UNSIGNED NOT NULL,
            familia_id    BIGINT UNSIGNED NOT NULL,
            data          DATE            NOT NULL,
            fruta_manha   VARCHAR(160)    NULL,
            fruta_tarde   VARCHAR(160)    NULL,
            almoco        VARCHAR(255)    NULL,
            jantar        VARCHAR(255)    NULL,
            almoco_slugs  JSON            NULL,
            jantar_slugs  JSON            NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uq_dia_semana (semana_id, data),
            KEY ix_dia_familia_data (familia_id, data),
            CONSTRAINT fk_cardapio_dias_semana FOREIGN KEY (semana_id)
                REFERENCES cardapios_semanas (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS compras_itens (
            id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            semana_id     BIGINT UNSIGNED NOT NULL,
            familia_id    BIGINT UNSIGNED NOT NULL,
            stable_id     VARCHAR(40)     NOT NULL,
            categoria     VARCHAR(60)     NOT NULL,
            nome          VARCHAR(120)    NOT NULL,
            quantidade    VARCHAR(120)    NULL,
            nota          VARCHAR(255)    NULL,
            ordem         SMALLINT UNSIGNED NOT NULL DEFAULT 1,
            comprado      TINYINT(1)      NOT NULL DEFAULT 0,
            comprado_em   DATETIME        NULL,
            comprado_por  BIGINT UNSIGNED NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uq_item_semana (semana_id, stable_id),
            KEY ix_item_familia (familia_id, semana_id),
            CONSTRAINT fk_compras_itens_semana FOREIGN KEY (semana_id)
                REFERENCES cardapios_semanas (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    ],
];
