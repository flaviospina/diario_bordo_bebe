<?php

declare(strict_types=1);

/**
 * 0013 — Rodada 3: configurações da PLATAFORMA (chave/valor), editáveis pelo
 * super_admin no Painel — começa pelos contatos de suporte que aparecem nos
 * e-mails automáticos (WhatsApp e e-mail). Trocar o número não exige mexer
 * em arquivo nenhum: é um formulário no Painel.
 */

return [
    'descricao' => 'Rodada 3: tabela configuracoes_plataforma (contatos de suporte)',

    'sql' => [
        "CREATE TABLE IF NOT EXISTS configuracoes_plataforma (
            chave         VARCHAR(60)  NOT NULL,
            valor         TEXT         NULL,
            atualizado_em DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
                          ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (chave)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "INSERT IGNORE INTO configuracoes_plataforma (chave, valor) VALUES
            ('whatsapp_suporte', '11993358259'),
            ('email_suporte', '')",
    ],
];
