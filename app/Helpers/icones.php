<?php

declare(strict_types=1);

/**
 * Ícones SVG do design system (traço arredondado, grade 24px) — substituem
 * os emojis do catálogo. Cores pastéis por grupo; slugs sem desenho próprio
 * herdam o ícone do grupo. Tudo inline: nada de fontes de ícone nem CDN.
 */

/** @return array{fundo:string, traco:string} cores pastel do grupo */
function cores_grupo(string $grupo): array
{
    return match ($grupo) {
        'alimentacao'    => ['fundo' => '#DEEDE9', 'traco' => '#3E6A64'],
        'sono'           => ['fundo' => '#E9E7F7', 'traco' => '#5F58A0'],
        'higiene'        => ['fundo' => '#DFF0E8', 'traco' => '#37795B'],
        'saude'          => ['fundo' => '#FADFE3', 'traco' => '#B0495C'],
        'desenvolvimento'=> ['fundo' => '#FBE8DE', 'traco' => '#B05E3C'],
        'rotina'         => ['fundo' => '#E3EDF8', 'traco' => '#3D6CA3'],
        'comportamento'  => ['fundo' => '#FAEDCB', 'traco' => '#8A6A15'],
        'apoio'          => ['fundo' => '#EFEAE0', 'traco' => '#6E6759'],
        'turno'          => ['fundo' => '#EFEAE0', 'traco' => '#6E6759'],
        'intercorrencia' => ['fundo' => '#F9E3DF', 'traco' => '#A5473A'],
        default          => ['fundo' => '#EFEAE0', 'traco' => '#6E6759'],
    };
}

/** Conteúdo (paths) de cada desenho, na grade 24×24, sem cor fixa. */
function _caminhos_icone(string $nome): string
{
    return match ($nome) {
        'mamadeira' => '<path d="M10 3 h4 v3 h-4 Z M9 6 h6 l1.5 3 v10 a2 2 0 0 1 -2 2 h-5 a2 2 0 0 1 -2 -2 V9 Z"/><path d="M8.5 13 h7"/>',
        'peito' => '<path d="M12 20 C 7 16, 3.5 13, 3.5 9 a 4.8 4.8 0 0 1 8.5 -3 a 4.8 4.8 0 0 1 8.5 3 c 0 4 -3.5 7 -8.5 11 Z"/>',
        'papinha' => '<path d="M4 12 h16 M4 12 a 8 8 0 0 1 16 0 M8 12 v2 a 4 4 0 0 0 8 0 v-2"/><path d="M15 5 l4 -2"/>',
        'gota' => '<path d="M12 4 c 3.5 4.5 5.5 7.5 5.5 10.5 a 5.5 5.5 0 0 1 -11 0 C 6.5 11.5 8.5 8.5 12 4 Z"/>',
        'arroto' => '<path d="M8 20 v-3 M12 20 v-4 M16 20 v-3"/><circle cx="12" cy="8" r="5"/>',
        'lua' => '<path d="M6 6 a 8 8 0 1 0 12 10 a 9.5 9.5 0 0 1 -12 -10 Z"/>',
        'sol-nuvem' => '<circle cx="12" cy="12" r="4.5"/><path d="M12 4 v2 M12 18 v2 M4 12 h2 M18 12 h2 M6.2 6.2 l1.5 1.5 M16.3 16.3 l1.5 1.5 M17.8 6.2 l-1.5 1.5 M7.7 16.3 l-1.5 1.5"/>',
        'fralda' => '<path d="M4 8 h16 v3 a 8 8 0 0 1 -16 0 Z"/><path d="M4 8 c 0 -2.5 3 -2.5 4 -1 M20 8 c 0 -2.5 -3 -2.5 -4 -1"/>',
        'camiseta' => '<path d="M8 4 l-4.5 3 2 3.5 2 -1 V 20 h9 V 9.5 l2 1 2 -3.5 L 16 4 a 4 4 0 0 1 -8 0 Z"/>',
        'nariz' => '<path d="M12 4 v8 c 0 3 -2 3.5 -2 5.5 a 2.5 2.5 0 0 0 5 0 c 0 -2 -1 -2.5 -1 -3.5"/>',
        'tesoura' => '<circle cx="6.5" cy="7" r="2.5"/><circle cx="6.5" cy="17" r="2.5"/><path d="M8.7 8.3 L 19 17 M8.7 15.7 L 19 7"/>',
        'escova' => '<path d="M5 4 h4 v9 h-4 Z"/><path d="M5 7 h4 M9 6 c 5 0 5 3 9 3 v4 c -5 0 -6 -3 -9 -3"/>',
        'pilula' => '<rect x="4" y="9" width="16" height="7" rx="3.5" transform="rotate(-30 12 12.5)"/><path d="M10.2 9.4 l3.6 6.2"/>',
        'termometro' => '<path d="M10 4 a 2 2 0 0 1 4 0 v9.5 a 4 4 0 1 1 -4 0 Z"/><circle cx="12" cy="17" r="1.6"/>',
        'coracao-pulso' => '<path d="M12 20 C 7 16, 3.5 13, 3.5 9 a 4.8 4.8 0 0 1 8.5 -3 a 4.8 4.8 0 0 1 8.5 3 c 0 4 -3.5 7 -8.5 11 Z"/><path d="M7 12 h3 l1.5 -3 2 5 1.5 -2 h3"/>',
        'seringa' => '<path d="M18 3 l3 3 M17 4 l3 3 M6 21 l3 -3 M4 20 l1 -1"/><path d="M8 17 L 17 8 l-1 -1 -9 9 Z M17 8 l-1 -1"/><rect x="7" y="7" width="10" height="10" rx="1" transform="rotate(45 12 12)"/>',
        'estetoscopio' => '<path d="M7 3 v5 a5 5 0 0 0 10 0 V3"/><path d="M12 13 v3 a4 4 0 0 0 8 0 v-1"/><circle cx="20" cy="13" r="2"/>',
        'tartaruga' => '<path d="M5 14 a 7 5.5 0 0 1 14 0 Z"/><path d="M3 14 h18 M7 14 l-1.5 3 M17 14 l1.5 3 M12 8.5 V 14"/><circle cx="19.5" cy="10.5" r="1.6"/>',
        'brinquedo' => '<circle cx="12" cy="12" r="8.5"/><path d="M12 3.5 V 12 l 6 6"/><circle cx="12" cy="12" r="2"/>',
        'livro' => '<path d="M12 6 c -2 -1.5 -4.5 -2 -8 -2 v14 c 3.5 0 6 0.5 8 2 c 2 -1.5 4.5 -2 8 -2 V 4 c -3.5 0 -6 0.5 -8 2 Z"/><path d="M12 6 v14"/>',
        'nota-musical' => '<path d="M9 17 V 5 l 10 -2 v 12"/><circle cx="6.5" cy="17.5" r="2.5"/><circle cx="16.5" cy="15.5" r="2.5"/>',
        'estrela' => '<path d="M12 4 l2 4.6 5 0.6 -3.7 3.4 1 5 -4.3 -2.6 -4.3 2.6 1 -5 L 5 9.2 l5 -0.6 Z"/>',
        'porta' => '<path d="M4 20 h16 M6 20 V 5 a1 1 0 0 1 1 -1 h10 a1 1 0 0 1 1 1 v15"/><circle cx="15" cy="12" r="1"/>',
        'carrinho' => '<path d="M4 5 c 0 6 4 9 9 9 h4"/><path d="M17 5 v9 M4 5 h4"/><circle cx="8" cy="18.5" r="2"/><circle cx="16" cy="18.5" r="2"/>',
        'escola' => '<path d="M4 20 V 10 l 8 -5 8 5 v10 M4 20 h16"/><path d="M10 20 v-5 h4 v5"/>',
        'pessoas' => '<circle cx="9" cy="8.5" r="3"/><path d="M3.5 19 c 0.8 -3.5 3 -5 5.5 -5 s 4.7 1.5 5.5 5"/><path d="M15.5 6.5 a 3 3 0 0 1 0 5.5 M17 14.5 c 2 0.6 3.3 2 3.9 4.5"/>',
        'sorriso' => '<circle cx="12" cy="12" r="8.5"/><path d="M9 10.5 h0.01 M15 10.5 h0.01"/><path d="M8.5 14 q 3.5 3 7 0"/>',
        'choro' => '<circle cx="12" cy="12" r="8.5"/><path d="M9 10 h0.01 M15 10 h0.01"/><path d="M8.5 16 q 3.5 -2.5 7 0"/><path d="M6.5 12.5 c 0 1.2 -1 1.4 -1 2.4 a 1 1 0 0 0 2 0 c 0 -1 -1 -1.2 -1 -2.4 Z"/>',
        'bolhas' => '<circle cx="9" cy="14" r="5.5"/><circle cx="17" cy="7.5" r="3"/><circle cx="18.5" cy="14.5" r="1.6"/>',
        'cesto' => '<path d="M5 7 h14 l-1.5 12 a2 2 0 0 1 -2 1.8 h-7 a2 2 0 0 1 -2 -1.8 Z"/><path d="M9 7 a3 3 0 0 1 6 0"/>',
        'cama' => '<path d="M3 18 V 8 M3 15 h18 v3 M3 12 h18 v3"/><path d="M6 12 a 1.8 1.8 0 0 1 3.6 0"/>',
        'carrinho-compras' => '<circle cx="9" cy="19" r="1.6"/><circle cx="17" cy="19" r="1.6"/><path d="M3.5 4.5 h2.5 l2 10.5 h9.5 l2 -7.5 H 7"/>',
        'relogio' => '<circle cx="12" cy="12" r="8.5"/><path d="M12 7.5 v4.5 l3.5 2"/>',
        'pausa' => '<circle cx="12" cy="12" r="8.5"/><path d="M10 9 v6 M14 9 v6"/>',
        'alerta' => '<path d="M12 4 L21 19 H3 Z"/><path d="M12 10 v4 M12 16.5 h0.01"/>',
        'casa' => '<path d="M4 11 l8 -7 8 7 v9 a1.5 1.5 0 0 1 -1.5 1.5 h-13 A1.5 1.5 0 0 1 4 20 Z"/>',
        'olho' => '<path d="M2.5 12 C 5 7, 8 4.8, 12 4.8 s 7 2.2 9.5 7.2 C 19 17, 16 19.2, 12 19.2 s -7 -2.2 -9.5 -7.2 Z"/><circle cx="12" cy="12" r="3"/>',
        'grafico' => '<path d="M5 19 V 12 M 12 19 V 6 M 19 19 v -9"/>',
        'balao' => '<path d="M4 6 h16 v10 a2 2 0 0 1 -2 2 h-8 l-4 3 v-3 a2 2 0 0 1 -2 -2 Z"/>',
        'engrenagem' => '<circle cx="12" cy="12" r="3.2"/><path d="M12 3.5 v2.3 M12 18.2 v2.3 M3.5 12 h2.3 M18.2 12 h2.3 M6 6 l1.7 1.7 M16.3 16.3 L18 18 M18 6 l-1.7 1.7 M7.7 16.3 L6 18"/>',
        'pessoa' => '<circle cx="12" cy="8.5" r="3.8"/><path d="M5 20 c 1 -4 4 -6 7 -6 s 6 2 7 6"/>',
        'lupa' => '<circle cx="11" cy="11" r="6.5"/><path d="M16 16 l4.5 4.5"/>',
        'documento' => '<path d="M7 3 h7 l4 4 v14 H7 Z"/><path d="M14 3 v4 h4 M10 12 h5 M10 15.5 h5"/>',
        'download' => '<path d="M12 4 v10 M8 10 l4 4 4 -4 M5 19 h14"/>',
        'mais' => '<path d="M12 5 v14 M5 12 h14"/>',
        'x' => '<path d="M6 6 l12 12 M18 6 L6 18"/>',
        'check' => '<path d="M5 13 l4.5 4.5 L19 8"/>',
        'seta-dir' => '<path d="M9 6 l6 6 -6 6"/>',
        'seta-esq' => '<path d="M15 6 l-6 6 6 6"/>',
        default => '<circle cx="12" cy="12" r="8"/>',
    };
}

/** Slug de categoria → nome do desenho. */
function _desenho_categoria(string $slug, string $grupo): string
{
    $mapa = [
        'amamentacao' => 'peito', 'mamadeira' => 'mamadeira', 'formula-preparada' => 'mamadeira',
        'papinha' => 'papinha', 'agua' => 'gota', 'arroto' => 'arroto',
        'regurgitacao' => 'gota', 'vomito' => 'gota',
        'soneca' => 'lua', 'sono-noturno' => 'lua', 'despertar' => 'sol-nuvem', 'dificuldade-dormir' => 'lua',
        'fralda' => 'fralda', 'banho' => 'gota', 'troca-roupa' => 'camiseta',
        'higiene-nasal' => 'nariz', 'corte-unha' => 'tesoura', 'escovacao' => 'escova',
        'medicacao' => 'pilula', 'temperatura' => 'termometro', 'sintoma' => 'coracao-pulso',
        'vacina' => 'seringa', 'consulta-medica' => 'estetoscopio',
        'tummy-time' => 'tartaruga', 'estimulo' => 'brinquedo', 'leitura' => 'livro',
        'musica' => 'nota-musical', 'banho-de-sol' => 'sol-nuvem', 'marco-desenvolvimento' => 'estrela',
        'saida-casa' => 'porta', 'passeio' => 'carrinho', 'escola-creche' => 'escola',
        'visita' => 'pessoas', 'terceiros' => 'pessoas',
        'humor' => 'sorriso', 'choro-prolongado' => 'choro', 'colica' => 'choro',
        'esterilizacao' => 'bolhas', 'lavagem-roupa' => 'cesto', 'organizacao-quarto' => 'cama',
        'pedido-suprimento' => 'carrinho-compras',
        'turno-entrada' => 'relogio', 'turno-pausa' => 'pausa', 'turno-saida' => 'relogio',
        'queda' => 'alerta', 'engasgo' => 'alerta', 'acidente' => 'alerta', 'reacao-alergica' => 'alerta',
    ];
    return $mapa[$slug] ?? match ($grupo) {
        'alimentacao' => 'mamadeira', 'sono' => 'lua', 'higiene' => 'gota', 'saude' => 'coracao-pulso',
        'desenvolvimento' => 'estrela', 'rotina' => 'porta', 'comportamento' => 'sorriso',
        'apoio' => 'cesto', 'turno' => 'relogio', 'intercorrencia' => 'alerta',
        default => 'estrela',
    };
}

/** SVG do ícone de uma categoria, na cor do grupo (ou em $cor, se dada). */
function icone_categoria(string $slug, string $grupo, int $tamanho = 22, ?string $cor = null): string
{
    $cores = cores_grupo($grupo);
    return icone_ui(_desenho_categoria($slug, $grupo), $tamanho, $cor ?? $cores['traco']);
}

/** SVG de um ícone de interface pelo nome do desenho. */
function icone_ui(string $nome, int $tamanho = 22, string $cor = 'currentColor', float $traco = 1.8): string
{
    return '<svg width="' . $tamanho . '" height="' . $tamanho . '" viewBox="0 0 24 24" fill="none"'
        . ' stroke="' . e($cor) . '" stroke-width="' . $traco . '" stroke-linecap="round" stroke-linejoin="round"'
        . ' aria-hidden="true">' . _caminhos_icone($nome) . '</svg>';
}

/** Selo pastel (quadradinho arredondado) com o ícone da categoria dentro. */
function selo_categoria(string $slug, string $grupo, int $lado = 44, int $icone = 22): string
{
    $cores = cores_grupo($grupo);
    return '<span class="selo-categoria" style="width:' . $lado . 'px;height:' . $lado . 'px;background:'
        . e($cores['fundo']) . '">' . icone_categoria($slug, $grupo, $icone) . '</span>';
}

/** A marca (logo A — “Cachinho”): rosto de bebê dormindo com um cacho. */
function logo_marca(int $tamanho = 30): string
{
    return '<svg width="' . $tamanho . '" height="' . $tamanho . '" viewBox="0 0 96 96" fill="none" aria-hidden="true">'
        . '<circle cx="48" cy="54" r="28" stroke="#4F837C" stroke-width="8" fill="#FFFFFF"></circle>'
        . '<path d="M48 26 C 48 15, 62 13, 64 21 C 65.5 27.5, 57 30, 53 25" stroke="#EFA98C" stroke-width="8" stroke-linecap="round" fill="none"></path>'
        . '<path d="M37 54 q 4.5 4.5 9 0" stroke="#33302B" stroke-width="5" stroke-linecap="round" fill="none"></path>'
        . '<path d="M52 54 q 4.5 4.5 9 0" stroke="#33302B" stroke-width="5" stroke-linecap="round" fill="none"></path>'
        . '</svg>';
}
