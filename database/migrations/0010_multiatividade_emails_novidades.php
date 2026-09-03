<?php

declare(strict_types=1);

/**
 * 0010 — Rodada 1 de evolução:
 *   - registros.grupo_registro: agrupa atividades registradas juntas no mesmo
 *     horário (multi-atividade) sem mudar o modelo 1 registro = 1 categoria;
 *   - emails_enviados: relatório/auditoria de todo e-mail transacional;
 *   - novidades: comunicados de novas funcionalidades (página pública +
 *     e-mail macro aos responsáveis).
 * Aditiva: nada existente é alterado ou removido.
 */

return [
    'descricao' => 'Multi-atividade (grupo_registro), relatório de e-mails e novidades',

    'colunas' => [
        'registros' => [
            'grupo_registro' => 'CHAR(12) NULL AFTER uuid_cliente',
        ],
    ],

    'sql' => [
        "CREATE TABLE IF NOT EXISTS emails_enviados (
            id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            destinatario    VARCHAR(190)    NOT NULL,
            assunto         VARCHAR(190)    NOT NULL,
            tipo            VARCHAR(40)     NOT NULL,
            referencia_id   BIGINT UNSIGNED NULL,
            familia_id      BIGINT UNSIGNED NULL,
            status          ENUM('enviado','falhou') NOT NULL,
            erro            VARCHAR(255)    NULL,
            criado_em       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY ix_emails_tipo (tipo, criado_em),
            KEY ix_emails_destinatario (destinatario)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS novidades (
            id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            slug            VARCHAR(80)     NOT NULL,
            titulo          VARCHAR(120)    NOT NULL,
            resumo          VARCHAR(300)    NOT NULL,
            detalhes        MEDIUMTEXT      NOT NULL,
            publicado       TINYINT(1)      NOT NULL DEFAULT 1,
            email_enviado_em DATETIME       NULL,
            criado_por      BIGINT UNSIGNED NOT NULL,
            criado_em       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_novidades_slug (slug)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    ],
];
