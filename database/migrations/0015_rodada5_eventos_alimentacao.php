<?php

declare(strict_types=1);

/**
 * 0015 — Rodada 5: mais tipos de evento importante (saúde, suprimento e
 * reação alimentar), todos com e-mail imediato aos responsáveis.
 * A categoria global "Introdução alimentar" entra pelo seed de categorias
 * (install/migrate.php insere os slugs que faltam).
 */

return [
    'descricao' => 'Rodada 5: tipos saude/suprimento/alimentacao em eventos_importantes',

    'sql' => [
        "ALTER TABLE eventos_importantes
            MODIFY tipo ENUM('marco','intercorrencia','medicao','vacina','saude','suprimento','alimentacao') NOT NULL",
    ],
];
