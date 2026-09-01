<?php

declare(strict_types=1);

/**
 * Migração 0008 — Ficha essencial da criança e atualização pelo pediatra
 * (Alteração 01). Aditiva: nenhuma tabela existente é derrubada e nenhum
 * dado é apagado. Colunas novas em `criancas` via bloco 'colunas'
 * (aplicadas só se ainda não existirem).
 */
return [
    'descricao' => 'Ficha essencial: colunas em criancas, medicoes, vacinas, profissionais, consultas, convites_consulta',

    'colunas' => [
        'criancas' => [
            'restricoes_alimentares' => 'TEXT NULL AFTER alergias',
            'semanas_gestacao' => 'TINYINT UNSIGNED NULL',
            'peso_nascimento_g' => 'INT UNSIGNED NULL',
            'comprimento_nascimento_mm' => 'INT UNSIGNED NULL',
            'perimetro_cefalico_nascimento_mm' => 'INT UNSIGNED NULL',
            'tipo_parto' => 'VARCHAR(30) NULL',
            'convenio_nome' => 'VARCHAR(120) NULL',
            'convenio_carteirinha' => 'VARCHAR(60) NULL',
            'hospital_referencia' => 'VARCHAR(160) NULL',
            'profissional_id' => 'BIGINT UNSIGNED NULL',
            'foto_thumb' => 'VARCHAR(255) NULL AFTER foto_path',
            'foto_codigo' => 'CHAR(12) NULL AFTER foto_thumb',
        ],
    ],

    'sql' => [
        /* profissionais é GLOBAL (sem familia_id): o mesmo pediatra atende
           crianças de famílias diferentes. Nunca vê nada por aqui — só é
           referenciado pelas linhas que ele registrou. */
        "CREATE TABLE IF NOT EXISTS profissionais (
            id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            nome            VARCHAR(160)    NOT NULL,
            tipo            VARCHAR(30)     NOT NULL DEFAULT 'pediatra',
            conselho_sigla  VARCHAR(10)     NULL,
            conselho_numero VARCHAR(20)     NULL,
            conselho_uf     CHAR(2)         NULL,
            email           VARCHAR(190)    NULL,
            telefone        VARCHAR(20)     NULL,
            verificado      TINYINT(1)      NOT NULL DEFAULT 0,
            criado_em       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY ix_profissionais_conselho (conselho_sigla, conselho_numero, conselho_uf)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        /* Regra de ouro: 'último peso' é sempre a linha mais recente daqui,
           nunca um campo sobrescrito em criancas — sobrescrever destruiria a
           curva de crescimento. Percentis/escores-z ficam CONGELADOS na linha. */
        "CREATE TABLE IF NOT EXISTS medicoes (
            id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            familia_id      BIGINT UNSIGNED NOT NULL,
            crianca_id      BIGINT UNSIGNED NOT NULL,
            medido_em       DATE            NOT NULL,
            peso_g          INT UNSIGNED    NULL,
            altura_mm       INT UNSIGNED    NULL,
            perimetro_cefalico_mm INT UNSIGNED NULL,
            origem          ENUM('pais','cuidador','pediatra') NOT NULL DEFAULT 'pais',
            registrado_por_usuario_id BIGINT UNSIGNED NULL,
            profissional_id BIGINT UNSIGNED NULL,
            profissional_nome_livre VARCHAR(160) NULL,
            percentil_peso  DECIMAL(4,1)    NULL,
            percentil_altura DECIMAL(4,1)   NULL,
            percentil_pc    DECIMAL(4,1)    NULL,
            escore_z_peso   DECIMAL(5,2)    NULL,
            escore_z_altura DECIMAL(5,2)    NULL,
            escore_z_pc     DECIMAL(5,2)    NULL,
            observacao      TEXT            NULL,
            status          ENUM('confirmada','pendente') NOT NULL DEFAULT 'confirmada',
            confirmado_por  BIGINT UNSIGNED NULL,
            confirmado_em   DATETIME        NULL,
            criado_em       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY ix_medicoes_crianca (familia_id, crianca_id, medido_em),
            KEY ix_medicoes_pendentes (familia_id, status),
            CONSTRAINT fk_medicoes_familia FOREIGN KEY (familia_id) REFERENCES familias(id),
            CONSTRAINT fk_medicoes_crianca FOREIGN KEY (crianca_id) REFERENCES criancas(id),
            CONSTRAINT fk_medicoes_profissional FOREIGN KEY (profissional_id) REFERENCES profissionais(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS vacinas (
            id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            familia_id      BIGINT UNSIGNED NOT NULL,
            crianca_id      BIGINT UNSIGNED NOT NULL,
            imunizante      VARCHAR(120)    NOT NULL,
            dose            VARCHAR(40)     NOT NULL,
            aplicada_em     DATE            NULL,
            lote            VARCHAR(40)     NULL,
            local_aplicacao VARCHAR(120)    NULL,
            origem          ENUM('pais','cuidador','pediatra') NOT NULL DEFAULT 'pais',
            profissional_id BIGINT UNSIGNED NULL,
            status          ENUM('aplicada','pendente','atrasada') NOT NULL DEFAULT 'aplicada',
            observacao      TEXT            NULL,
            criado_em       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY ix_vacinas_crianca (familia_id, crianca_id, aplicada_em),
            CONSTRAINT fk_vacinas_familia FOREIGN KEY (familia_id) REFERENCES familias(id),
            CONSTRAINT fk_vacinas_crianca FOREIGN KEY (crianca_id) REFERENCES criancas(id),
            CONSTRAINT fk_vacinas_profissional FOREIGN KEY (profissional_id) REFERENCES profissionais(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS consultas (
            id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            familia_id      BIGINT UNSIGNED NOT NULL,
            crianca_id      BIGINT UNSIGNED NOT NULL,
            profissional_id BIGINT UNSIGNED NULL,
            realizada_em    DATE            NOT NULL,
            motivo          VARCHAR(160)    NULL,
            conduta         TEXT            NULL,
            retorno_em      DATE            NULL,
            origem          ENUM('pais','pediatra') NOT NULL DEFAULT 'pais',
            criado_em       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY ix_consultas_crianca (familia_id, crianca_id, realizada_em),
            CONSTRAINT fk_consultas_familia FOREIGN KEY (familia_id) REFERENCES familias(id),
            CONSTRAINT fk_consultas_crianca FOREIGN KEY (crianca_id) REFERENCES criancas(id),
            CONSTRAINT fk_consultas_profissional FOREIGN KEY (profissional_id) REFERENCES profissionais(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        /* Link de uso único da consulta: abre sem login, queima ao enviar. */
        "CREATE TABLE IF NOT EXISTS convites_consulta (
            id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            familia_id      BIGINT UNSIGNED NOT NULL,
            crianca_id      BIGINT UNSIGNED NOT NULL,
            codigo_publico  CHAR(12)        NOT NULL,
            criado_por_usuario_id BIGINT UNSIGNED NOT NULL,
            criado_em       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            expira_em       DATETIME        NOT NULL,
            aberto_em       DATETIME        NULL,
            usado_em        DATETIME        NULL,
            profissional_id BIGINT UNSIGNED NULL,
            ip_uso          VARCHAR(45)     NULL,
            user_agent_uso  VARCHAR(255)    NULL,
            status          ENUM('aberto','usado','expirado','revogado') NOT NULL DEFAULT 'aberto',
            PRIMARY KEY (id),
            UNIQUE KEY uq_convites_consulta_codigo (codigo_publico),
            KEY ix_convites_consulta_crianca (familia_id, crianca_id, status),
            CONSTRAINT fk_convcons_familia FOREIGN KEY (familia_id) REFERENCES familias(id),
            CONSTRAINT fk_convcons_crianca FOREIGN KEY (crianca_id) REFERENCES criancas(id),
            CONSTRAINT fk_convcons_profissional FOREIGN KEY (profissional_id) REFERENCES profissionais(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    ],

    /* Migração de dados: pediatra_nome/pediatra_telefone existentes viram
       registro em profissionais e preenchem profissional_id. As colunas
       antigas NÃO são removidas (obsoletas; leitura ainda possível) —
       remoção fica para migração futura, quando ninguém mais as ler. */
    'executar' => static function (PDO $bd): void {
        $criancas = $bd->query(
            "SELECT id, pediatra_nome, pediatra_telefone FROM criancas
              WHERE pediatra_nome IS NOT NULL AND pediatra_nome <> ''
                AND (profissional_id IS NULL)"
        )->fetchAll();

        $buscar = $bd->prepare('SELECT id FROM profissionais WHERE nome = ? LIMIT 1');
        $criar = $bd->prepare(
            "INSERT INTO profissionais (nome, tipo, telefone, verificado) VALUES (?, 'pediatra', ?, 0)"
        );
        $vincular = $bd->prepare('UPDATE criancas SET profissional_id = ? WHERE id = ?');

        foreach ($criancas as $crianca) {
            $buscar->execute([$crianca['pediatra_nome']]);
            $profissionalId = $buscar->fetchColumn();
            if ($profissionalId === false) {
                $criar->execute([$crianca['pediatra_nome'], $crianca['pediatra_telefone']]);
                $profissionalId = (int)$bd->lastInsertId();
            }
            $vincular->execute([(int)$profissionalId, (int)$crianca['id']]);
        }
    },
];
