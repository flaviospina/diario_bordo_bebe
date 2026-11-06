<?php

declare(strict_types=1);

/**
 * 0017 — Rodada 7: Portal da Colaboradora (contracheques, pagamentos,
 * benefícios e documentos trabalhistas da babá) com auditoria automática.
 *
 * Só CRIA tabelas; nada do diário do bebê é alterado.
 *
 * colaboradoras          — vínculo trabalhista: usuário (cuidador) + família.
 * folha_permissoes       — responsáveis explicitamente autorizados a gerir a
 *                          folha (o admin da família sempre pode).
 * folha_documentos       — documento lógico (tipo + competência) e seu status.
 * folha_versoes          — cada PDF enviado (v1, v2…), com hash e quem enviou.
 * folha_resumos          — valores conferidos por versão (NULL = não informado;
 *                          nunca interpretado como zero).
 * folha_vales_transporte — vale-transporte antecipado por mês de referência.
 * folha_eventos          — trilha de auditoria (apenas INSERT): publicado,
 *                          aberto, baixado, substituido, revogado, negado.
 *                          criado_em em UTC; a tela converte para São Paulo.
 */

return [
    'descricao' => 'Rodada 7: Portal da Colaboradora (folha, documentos, VT e auditoria)',

    'sql' => [
        "CREATE TABLE IF NOT EXISTS colaboradoras (
            id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            familia_id    BIGINT UNSIGNED NOT NULL,
            usuario_id    BIGINT UNSIGNED NOT NULL,
            codigo_publico CHAR(12)       NOT NULL,
            admissao      DATE            NULL,
            situacao      ENUM('ativa','encerrada') NOT NULL DEFAULT 'ativa',
            observacoes   VARCHAR(500)    NULL,
            criado_por    BIGINT UNSIGNED NULL,
            criado_em     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            atualizado_em DATETIME        NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uq_colab_usuario (usuario_id),
            UNIQUE KEY uq_colab_codigo (codigo_publico),
            KEY ix_colab_familia (familia_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS folha_permissoes (
            id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            familia_id    BIGINT UNSIGNED NOT NULL,
            usuario_id    BIGINT UNSIGNED NOT NULL,
            concedido_por BIGINT UNSIGNED NULL,
            criado_em     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_folha_perm (familia_id, usuario_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS folha_documentos (
            id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            familia_id      BIGINT UNSIGNED NOT NULL,
            colaboradora_id BIGINT UNSIGNED NOT NULL,
            codigo_publico  CHAR(12)        NOT NULL,
            tipo            VARCHAR(40)     NOT NULL,
            competencia     CHAR(7)         NOT NULL,
            descricao       VARCHAR(200)    NULL,
            status          ENUM('rascunho','publicado','revogado') NOT NULL DEFAULT 'rascunho',
            versao_atual_id BIGINT UNSIGNED NULL,
            data_pagamento  DATE            NULL,
            mes_referencia  CHAR(7)         NULL,
            publicado_em    DATETIME        NULL,
            publicado_por   BIGINT UNSIGNED NULL,
            revogado_em     DATETIME        NULL,
            revogado_por    BIGINT UNSIGNED NULL,
            motivo_revogacao VARCHAR(300)   NULL,
            criado_por      BIGINT UNSIGNED NULL,
            criado_em       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            atualizado_em   DATETIME        NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uq_folha_doc_codigo (codigo_publico),
            KEY ix_folha_doc_colab (familia_id, colaboradora_id, competencia),
            KEY ix_folha_doc_status (familia_id, status),
            CONSTRAINT fk_folha_doc_colab FOREIGN KEY (colaboradora_id)
                REFERENCES colaboradoras (id) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS folha_versoes (
            id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            documento_id   BIGINT UNSIGNED NOT NULL,
            familia_id     BIGINT UNSIGNED NOT NULL,
            numero         SMALLINT UNSIGNED NOT NULL DEFAULT 1,
            nome_original  VARCHAR(255)    NOT NULL,
            caminho        VARCHAR(255)    NOT NULL,
            mime           VARCHAR(80)     NOT NULL DEFAULT 'application/pdf',
            tamanho        INT UNSIGNED    NOT NULL,
            sha256         CHAR(64)        NOT NULL,
            situacao       ENUM('vigente','substituida','revogada') NOT NULL DEFAULT 'vigente',
            enviado_por    BIGINT UNSIGNED NULL,
            criado_em      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            substituida_em DATETIME        NULL,
            substituida_por BIGINT UNSIGNED NULL,
            motivo         VARCHAR(300)    NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uq_folha_versao (documento_id, numero),
            KEY ix_folha_versao_familia (familia_id, sha256),
            CONSTRAINT fk_folha_versao_doc FOREIGN KEY (documento_id)
                REFERENCES folha_documentos (id) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS folha_resumos (
            id                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            documento_id       BIGINT UNSIGNED NOT NULL,
            versao_id          BIGINT UNSIGNED NOT NULL,
            familia_id         BIGINT UNSIGNED NOT NULL,
            competencia        CHAR(7)         NOT NULL,
            salario_base       DECIMAL(12,2)   NULL,
            salario_bruto      DECIMAL(12,2)   NULL,
            horas_extras_qtd   DECIMAL(7,2)    NULL,
            horas_extras_valor DECIMAL(12,2)   NULL,
            dsr                DECIMAL(12,2)   NULL,
            adicionais         DECIMAL(12,2)   NULL,
            outros_vencimentos DECIMAL(12,2)   NULL,
            inss               DECIMAL(12,2)   NULL,
            irrf               DECIMAL(12,2)   NULL,
            desconto_vt        DECIMAL(12,2)   NULL,
            outros_descontos   DECIMAL(12,2)   NULL,
            salario_liquido    DECIMAL(12,2)   NULL,
            vt_antecipado      DECIMAL(12,2)   NULL,
            total_pago         DECIMAL(12,2)   NULL,
            data_pagamento     DATE            NULL,
            origem             ENUM('manual','extraido','misto') NOT NULL DEFAULT 'manual',
            conferido          TINYINT(1)      NOT NULL DEFAULT 0,
            conferido_por      BIGINT UNSIGNED NULL,
            conferido_em       DATETIME        NULL,
            observacoes        VARCHAR(500)    NULL,
            atualizado_em      DATETIME        NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uq_folha_resumo_versao (versao_id),
            KEY ix_folha_resumo_doc (familia_id, documento_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS folha_vales_transporte (
            id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            familia_id      BIGINT UNSIGNED NOT NULL,
            colaboradora_id BIGINT UNSIGNED NOT NULL,
            mes_referencia  CHAR(7)         NOT NULL,
            valor_diario    DECIMAL(10,2)   NULL,
            dias            SMALLINT UNSIGNED NULL,
            valor_total     DECIMAL(12,2)   NULL,
            data_pagamento  DATE            NULL,
            competencia     CHAR(7)         NULL,
            desconto_folha  DECIMAL(12,2)   NULL,
            documento_id    BIGINT UNSIGNED NULL,
            origem          ENUM('manual','documento') NOT NULL DEFAULT 'manual',
            observacoes     VARCHAR(500)    NULL,
            criado_por      BIGINT UNSIGNED NULL,
            criado_em       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            atualizado_em   DATETIME        NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uq_folha_vt_mes (colaboradora_id, mes_referencia),
            KEY ix_folha_vt_familia (familia_id, colaboradora_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS folha_eventos (
            id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            familia_id      BIGINT UNSIGNED NOT NULL,
            colaboradora_id BIGINT UNSIGNED NULL,
            documento_id    BIGINT UNSIGNED NULL,
            versao_id       BIGINT UNSIGNED NULL,
            usuario_id      BIGINT UNSIGNED NULL,
            tipo            ENUM('publicado','aberto','baixado','substituido','revogado','negado') NOT NULL,
            categoria       ENUM('administrativo','consulta','seguranca') NOT NULL DEFAULT 'consulta',
            resultado       ENUM('ok','negado','erro') NOT NULL DEFAULT 'ok',
            operacao        CHAR(16)        NULL,
            competencia     CHAR(7)         NULL,
            ip              VARCHAR(45)     NULL,
            detalhe         VARCHAR(200)    NULL,
            criado_em       DATETIME        NOT NULL,
            PRIMARY KEY (id),
            KEY ix_folha_ev_doc (familia_id, documento_id, criado_em),
            KEY ix_folha_ev_op (versao_id, tipo, operacao),
            KEY ix_folha_ev_colab (familia_id, colaboradora_id, criado_em)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    ],
];
