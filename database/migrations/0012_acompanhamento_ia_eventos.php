<?php

declare(strict_types=1);

/**
 * 0012 — Rodada 2: acompanhamento com IA e eventos importantes.
 *
 * analises_ia        — cada rodada de análise (manual ou semanal) sobre um
 *                      período do diário; guarda o resumo estruturado enviado
 *                      à IA (dados_base, SEM nome da criança) para auditoria.
 * observacoes_ia     — as observações devolvidas pela análise, tipadas:
 *                      padrão detectado, aviso preventivo, fase/salto ou
 *                      celebração. Nunca diagnóstico — sempre apontam o
 *                      pediatra como quem decide.
 * eventos_importantes— linha do tempo de marcos, intercorrências, medições
 *                      confirmadas e vacinas: alimenta o e-mail imediato aos
 *                      responsáveis e a seção "novidades desde a última
 *                      consulta" da ficha do pediatra.
 */

return [
    'descricao' => 'Rodada 2: analises_ia, observacoes_ia e eventos_importantes',

    'sql' => [
        "CREATE TABLE IF NOT EXISTS analises_ia (
            id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            familia_id  BIGINT UNSIGNED NOT NULL,
            crianca_id  BIGINT UNSIGNED NOT NULL,
            periodo_de  DATE            NOT NULL,
            periodo_ate DATE            NOT NULL,
            origem      ENUM('manual','automatica') NOT NULL DEFAULT 'manual',
            modelo      VARCHAR(80)     NULL,
            dados_base  JSON            NULL,
            gerado_em   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY ix_analises_crianca (familia_id, crianca_id, gerado_em)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS observacoes_ia (
            id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            analise_id BIGINT UNSIGNED NOT NULL,
            familia_id BIGINT UNSIGNED NOT NULL,
            crianca_id BIGINT UNSIGNED NOT NULL,
            tipo       ENUM('padrao','preventivo','fase','celebracao') NOT NULL DEFAULT 'padrao',
            titulo     VARCHAR(160)    NOT NULL,
            texto      TEXT            NOT NULL,
            ordem      TINYINT UNSIGNED NOT NULL DEFAULT 1,
            criado_em  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY ix_observacoes_analise (analise_id),
            KEY ix_observacoes_crianca (familia_id, crianca_id, criado_em)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS eventos_importantes (
            id                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            familia_id        BIGINT UNSIGNED NOT NULL,
            crianca_id        BIGINT UNSIGNED NOT NULL,
            tipo              ENUM('marco','intercorrencia','medicao','vacina') NOT NULL,
            titulo            VARCHAR(160)    NOT NULL,
            descricao         TEXT            NULL,
            referencia_tabela VARCHAR(40)     NULL,
            referencia_id     BIGINT UNSIGNED NULL,
            ocorrido_em       DATETIME        NOT NULL,
            criado_em         DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            email_enviado_em  DATETIME        NULL,
            PRIMARY KEY (id),
            KEY ix_eventos_crianca (familia_id, crianca_id, ocorrido_em),
            KEY ix_eventos_referencia (referencia_tabela, referencia_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    ],
];
