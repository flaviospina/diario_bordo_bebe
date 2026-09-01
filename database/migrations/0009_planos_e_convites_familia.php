<?php

declare(strict_types=1);

/**
 * 0009 — Estrutura de escala (fase fundadores):
 *   - catálogo de planos com limites CONFIGURÁVEIS (nada de limite no código);
 *   - convites de família (link único de auto-cadastro p/ casais fundadores);
 *   - lista de espera pública da landing;
 *   - validade de plano em familias (estrutura p/ cobrança futura).
 *
 * Aditiva: nenhuma tabela/coluna existente é alterada ou removida; famílias
 * já existentes no plano legado "familiar" viram "fundador" (gratuito pleno).
 */

return [
    'descricao' => 'Escala: planos configuráveis, convites de família, lista de espera, validade de plano',

    'sql' => [
        "CREATE TABLE IF NOT EXISTS planos (
            id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
            chave           VARCHAR(30)     NOT NULL,
            nome            VARCHAR(60)     NOT NULL,
            descricao       VARCHAR(255)    NULL,
            preco_centavos  INT UNSIGNED    NOT NULL DEFAULT 0,
            limites         JSON            NULL,
            publico         TINYINT(1)      NOT NULL DEFAULT 0,
            ativo           TINYINT(1)      NOT NULL DEFAULT 1,
            ordem           TINYINT UNSIGNED NOT NULL DEFAULT 0,
            criado_em       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_planos_chave (chave)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS convites_familia (
            id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            codigo_publico  CHAR(12)        NOT NULL,
            plano           VARCHAR(30)     NOT NULL DEFAULT 'fundador',
            observacao      VARCHAR(160)    NULL,
            criado_por      BIGINT UNSIGNED NOT NULL,
            criado_em       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            expira_em       DATETIME        NOT NULL,
            usado_em        DATETIME        NULL,
            familia_id      BIGINT UNSIGNED NULL,
            status          ENUM('aberto','usado','revogado') NOT NULL DEFAULT 'aberto',
            PRIMARY KEY (id),
            UNIQUE KEY uq_convites_familia_codigo (codigo_publico),
            KEY ix_convites_familia_status (status, expira_em)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS lista_espera (
            id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            nome        VARCHAR(120)    NOT NULL,
            email       VARCHAR(190)    NOT NULL,
            whatsapp    VARCHAR(30)     NULL,
            mensagem    VARCHAR(500)    NULL,
            ip          VARCHAR(45)     NULL,
            status      ENUM('novo','convidado','descartado') NOT NULL DEFAULT 'novo',
            criado_em   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY ix_lista_espera_status (status, criado_em)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    ],

    'colunas' => [
        'familias' => [
            'valida_ate' => "DATE NULL AFTER status",
        ],
    ],

    'executar' => static function (PDO $bd): void {
        // Catálogo inicial de planos — valores/limites são AJUSTÁVEIS direto na
        // tabela (ou pelo painel, no futuro); null em um limite = ilimitado.
        $planos = [
            ['fundador', 'Família fundadora', 'Acesso completo e gratuito — famílias convidadas do início', 0,
                ['max_criancas' => null, 'max_usuarios' => null, 'fotos' => true, 'relatorios_pdf' => true, 'ficha_pediatra' => true],
                0, 0],
            ['gratuito', 'Gratuito', '1 criança, 3 pessoas, o essencial do diário', 0,
                ['max_criancas' => 1, 'max_usuarios' => 3, 'fotos' => false, 'relatorios_pdf' => false, 'ficha_pediatra' => true],
                1, 1],
            ['essencial', 'Essencial', 'Até 2 crianças, 6 pessoas, fotos e relatórios', 1990,
                ['max_criancas' => 2, 'max_usuarios' => 6, 'fotos' => true, 'relatorios_pdf' => true, 'ficha_pediatra' => true],
                1, 2],
            ['completo', 'Completo', 'Tudo ilimitado, para famílias grandes e gêmeos', 3490,
                ['max_criancas' => null, 'max_usuarios' => null, 'fotos' => true, 'relatorios_pdf' => true, 'ficha_pediatra' => true],
                1, 3],
        ];
        $inserir = $bd->prepare(
            'INSERT INTO planos (chave, nome, descricao, preco_centavos, limites, publico, ordem)
             SELECT ?, ?, ?, ?, ?, ?, ?
              WHERE NOT EXISTS (SELECT 1 FROM planos WHERE chave = ?)'
        );
        foreach ($planos as [$chave, $nome, $descricao, $preco, $limites, $publico, $ordem]) {
            $inserir->execute([$chave, $nome, $descricao, $preco,
                json_encode($limites, JSON_UNESCAPED_UNICODE), $publico, $ordem, $chave]);
        }

        // Planos legados da v1 → catálogo novo (sem tocar em 'plataforma')
        $bd->exec("UPDATE familias SET plano = 'fundador' WHERE plano = 'familiar'");
        $bd->exec("UPDATE familias SET plano = 'completo' WHERE plano = 'premium'");
    },
];
