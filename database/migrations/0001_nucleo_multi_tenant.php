<?php

declare(strict_types=1);

/**
 * Migração 0001 — núcleo multi-tenant: famílias, usuários, perfis e crianças.
 * Idempotente: CREATE TABLE IF NOT EXISTS + controle na tabela `migracoes`.
 */
return [
    'descricao' => 'Núcleo multi-tenant: familias, usuarios, perfis_responsaveis, criancas',
    'sql' => [
        "CREATE TABLE IF NOT EXISTS familias (
            id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            codigo_publico  CHAR(12)        NOT NULL,
            slug            VARCHAR(80)     NOT NULL,
            nome            VARCHAR(120)    NOT NULL,
            plano           VARCHAR(30)     NOT NULL DEFAULT 'familiar',
            status          ENUM('ativa','suspensa','encerrada') NOT NULL DEFAULT 'ativa',
            criado_em       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_familias_codigo (codigo_publico),
            UNIQUE KEY uq_familias_slug (slug)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS usuarios (
            id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            familia_id      BIGINT UNSIGNED NOT NULL,
            codigo_publico  CHAR(12)        NOT NULL,
            nome            VARCHAR(120)    NOT NULL,
            email           VARCHAR(190)    NOT NULL,
            senha_hash      VARCHAR(255)    NOT NULL,
            papel           ENUM('admin_familia','responsavel','cuidador','leitor','super_admin') NOT NULL,
            telefone_whatsapp VARCHAR(20)   NULL,
            ativo           TINYINT(1)      NOT NULL DEFAULT 1,
            ultimo_login    DATETIME        NULL,
            criado_em       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_usuarios_email (email),
            UNIQUE KEY uq_usuarios_codigo (codigo_publico),
            KEY ix_usuarios_familia (familia_id, papel, ativo),
            CONSTRAINT fk_usuarios_familia FOREIGN KEY (familia_id) REFERENCES familias(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS perfis_responsaveis (
            id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            usuario_id      BIGINT UNSIGNED NOT NULL,
            cpf             VARCHAR(14)     NULL,
            data_nascimento DATE            NULL,
            profissao       VARCHAR(120)    NULL,
            telefone_alternativo VARCHAR(20) NULL,
            endereco        VARCHAR(255)    NULL,
            contato_emergencia_nome     VARCHAR(120) NULL,
            contato_emergencia_telefone VARCHAR(20)  NULL,
            observacoes     TEXT            NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uq_perfis_usuario (usuario_id),
            CONSTRAINT fk_perfis_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS criancas (
            id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            familia_id      BIGINT UNSIGNED NOT NULL,
            codigo_publico  CHAR(12)        NOT NULL,
            slug            VARCHAR(80)     NOT NULL,
            nome            VARCHAR(120)    NOT NULL,
            apelido         VARCHAR(60)     NULL,
            data_nascimento DATE            NULL,
            sexo            ENUM('feminino','masculino','nao_informado') NULL,
            foto_path       VARCHAR(255)    NULL,
            tipo_sanguineo  VARCHAR(3)      NULL,
            alergias        TEXT            NULL,
            condicoes_saude TEXT            NULL,
            medicacoes_continuas TEXT       NULL,
            pediatra_nome   VARCHAR(120)    NULL,
            pediatra_telefone VARCHAR(20)   NULL,
            ativo           TINYINT(1)      NOT NULL DEFAULT 1,
            criado_em       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_criancas_codigo (codigo_publico),
            UNIQUE KEY uq_criancas_slug (familia_id, slug),
            KEY ix_criancas_familia (familia_id, ativo),
            CONSTRAINT fk_criancas_familia FOREIGN KEY (familia_id) REFERENCES familias(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    ],
];
