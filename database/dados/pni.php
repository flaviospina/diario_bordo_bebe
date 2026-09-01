<?php

declare(strict_types=1);

/**
 * Calendário Nacional de Vacinação (PNI/Ministério da Saúde) — primeira infância.
 * Informativo: o sistema mostra o esperado para a idade e o que já foi aplicado;
 * quem orienta é sempre o profissional de saúde.
 * Formato: [imunizante, dose, idade recomendada em meses (0 = ao nascer)].
 */
return [
    ['BCG', 'Dose única', 0],
    ['Hepatite B', '1ª dose', 0],
    ['Pentavalente (DTP+Hib+HepB)', '1ª dose', 2],
    ['VIP (Poliomielite inativada)', '1ª dose', 2],
    ['Pneumocócica 10-valente', '1ª dose', 2],
    ['Rotavírus humano', '1ª dose', 2],
    ['Meningocócica C', '1ª dose', 3],
    ['Pentavalente (DTP+Hib+HepB)', '2ª dose', 4],
    ['VIP (Poliomielite inativada)', '2ª dose', 4],
    ['Pneumocócica 10-valente', '2ª dose', 4],
    ['Rotavírus humano', '2ª dose', 4],
    ['Meningocócica C', '2ª dose', 5],
    ['Pentavalente (DTP+Hib+HepB)', '3ª dose', 6],
    ['VIP (Poliomielite inativada)', '3ª dose', 6],
    ['Covid-19 (calendário infantil)', '1ª dose', 6],
    ['Influenza (anual)', 'Dose anual', 6],
    ['Febre amarela', '1ª dose', 9],
    ['Pneumocócica 10-valente', 'Reforço', 12],
    ['Meningocócica C', 'Reforço', 12],
    ['Tríplice viral (SCR)', '1ª dose', 12],
    ['DTP (tríplice bacteriana)', '1º reforço', 15],
    ['VOP (Poliomielite oral)', '1º reforço', 15],
    ['Hepatite A', 'Dose única', 15],
    ['Tetraviral (SCR + varicela)', 'Dose única', 15],
    ['DTP (tríplice bacteriana)', '2º reforço', 48],
    ['VOP (Poliomielite oral)', '2º reforço', 48],
    ['Varicela', '2ª dose', 48],
    ['Febre amarela', 'Reforço', 48],
];
