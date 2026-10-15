<?php

declare(strict_types=1);

/**
 * 0014 — Rodada 4: central de avisos e notificações push.
 *
 * avisos          — um aviso por usuário destinatário (sino no topo do app):
 *                   o cuidador fica sabendo das ações dos pais que afetam o
 *                   trabalho dele (registro ajustado, roteiro alterado,
 *                   solicitação decidida…) e os pais recebem os mesmos
 *                   eventos que já saem por e-mail/WhatsApp.
 * push_inscricoes — aparelhos que aceitaram notificações (Web Push, VAPID):
 *                   um usuário pode ter vários (celular, computador).
 */

return [
    'descricao' => 'Rodada 4: avisos (central de notificações) e push_inscricoes (Web Push)',

    'sql' => [
        "CREATE TABLE IF NOT EXISTS avisos (
            id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            familia_id  BIGINT UNSIGNED NOT NULL,
            usuario_id  BIGINT UNSIGNED NOT NULL,
            tipo        VARCHAR(40)     NOT NULL,
            titulo      VARCHAR(160)    NOT NULL,
            mensagem    TEXT            NULL,
            url         VARCHAR(255)    NULL,
            lido_em     DATETIME        NULL,
            criado_em   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY ix_avisos_usuario (usuario_id, lido_em, criado_em),
            KEY ix_avisos_familia (familia_id, criado_em)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS push_inscricoes (
            id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            familia_id      BIGINT UNSIGNED NOT NULL,
            usuario_id      BIGINT UNSIGNED NOT NULL,
            endpoint        TEXT            NOT NULL,
            endpoint_hash   CHAR(64)        NOT NULL,
            chave_p256dh    VARCHAR(120)    NOT NULL,
            chave_auth      VARCHAR(60)     NOT NULL,
            user_agent      VARCHAR(255)    NULL,
            falhas          TINYINT UNSIGNED NOT NULL DEFAULT 0,
            ultimo_envio_em DATETIME        NULL,
            criado_em       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_push_endpoint (endpoint_hash),
            KEY ix_push_usuario (usuario_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    ],
];
