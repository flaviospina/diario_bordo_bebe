<?php

declare(strict_types=1);

/**
 * Migração 0006 — resumos diários, fila de notificações, LGPD e log de acessos.
 */
return [
    'descricao' => 'Resumos diários, fila_notificacoes, consentimentos_lgpd, log_acessos',
    'sql' => [
        "CREATE TABLE IF NOT EXISTS resumos_diarios (
            id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            codigo_publico  CHAR(12)        NOT NULL,
            familia_id      BIGINT UNSIGNED NOT NULL,
            crianca_id      BIGINT UNSIGNED NOT NULL,
            data            DATE            NOT NULL,
            texto_gerado    MEDIUMTEXT      NULL,
            pdf_path        VARCHAR(255)    NULL,
            enviado_em      DATETIME        NULL,
            canais          JSON            NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uq_resumos_codigo (codigo_publico),
            UNIQUE KEY uq_resumos_dia (crianca_id, data),
            KEY ix_resumos_familia (familia_id, data),
            CONSTRAINT fk_resumos_familia FOREIGN KEY (familia_id) REFERENCES familias(id),
            CONSTRAINT fk_resumos_crianca FOREIGN KEY (crianca_id) REFERENCES criancas(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS fila_notificacoes (
            id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            familia_id      BIGINT UNSIGNED NOT NULL,
            evento          VARCHAR(40)     NOT NULL,
            canal           ENUM('whatsapp','email','push') NOT NULL,
            destinatario    VARCHAR(190)    NOT NULL,
            payload         JSON            NOT NULL,
            status          ENUM('pendente','enviando','enviada','falha','cancelada') NOT NULL DEFAULT 'pendente',
            tentativas      TINYINT UNSIGNED NOT NULL DEFAULT 0,
            ultimo_erro     TEXT            NULL,
            agendado_para   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            enviado_em      DATETIME        NULL,
            criado_em       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY ix_fila_pendentes (status, agendado_para),
            KEY ix_fila_familia (familia_id, criado_em),
            CONSTRAINT fk_fila_familia FOREIGN KEY (familia_id) REFERENCES familias(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS consentimentos_lgpd (
            id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            usuario_id      BIGINT UNSIGNED NOT NULL,
            tipo            ENUM('responsavel','cuidador','leitor') NOT NULL,
            versao_termo    VARCHAR(20)     NOT NULL,
            aceito_em       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            ip              VARCHAR(45)     NULL,
            PRIMARY KEY (id),
            KEY ix_lgpd_usuario (usuario_id, tipo, versao_termo),
            CONSTRAINT fk_lgpd_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        /* Sem FKs de propósito: o log não pode impedir operação nem sumir em cascata. */
        "CREATE TABLE IF NOT EXISTS log_acessos (
            id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            familia_id      BIGINT UNSIGNED NULL,
            usuario_id      BIGINT UNSIGNED NULL,
            acao            VARCHAR(60)     NOT NULL,
            entidade        VARCHAR(60)     NULL,
            entidade_id     BIGINT UNSIGNED NULL,
            ip              VARCHAR(45)     NULL,
            user_agent      VARCHAR(255)    NULL,
            criado_em       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY ix_log_familia (familia_id, criado_em),
            KEY ix_log_usuario (usuario_id, criado_em)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    ],
];
