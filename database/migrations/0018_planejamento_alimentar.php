<?php

declare(strict_types=1);

/**
 * 0018 — Rodada 8: planejamento alimentar importado (09–30/11/2026).
 *
 * Só ADICIONA duas colunas em cardapio_dias (aplicadas apenas se faltarem):
 *  - planejado_json: o texto PLANEJADO de cada refeição na última importação,
 *    para distinguir edição manual dos pais (preservada) de valor importado
 *    (atualizável) nas importações seguintes — idempotência sem sobrescrever.
 *  - alertas_json: alertas por refeição (ex.: ovo/peixe — alergênico a
 *    confirmar com a pediatra), exibidos só no dia correspondente.
 * Nenhum dado existente é alterado ou apagado.
 */

return [
    'descricao' => 'Rodada 8: colunas planejado_json e alertas_json em cardapio_dias',

    'colunas' => [
        'cardapio_dias' => [
            'planejado_json' => 'JSON NULL',
            'alertas_json' => 'JSON NULL',
        ],
    ],
];
