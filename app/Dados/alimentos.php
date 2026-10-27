<?php

declare(strict_types=1);

/**
 * Catálogo de introdução alimentar por idade (em meses).
 *
 * Fontes: Guia Alimentar para Crianças Brasileiras Menores de 2 Anos
 * (Ministério da Saúde, 2019) e sua versão de bolso (2021); Sociedade
 * Brasileira de Pediatria — Alimentação Complementar para o Lactente
 * Saudável (Dep. de Nutrologia) e atualização 2018/2024; OMS — Infant and
 * young child feeding (complementary feeding); ESPGHAN — Complementary
 * Feeding Position Paper (2017); AAP — introdução precoce de alergênicos
 * (ovo e amendoim) e prevenção de engasgo.
 *
 * Convenções:
 *   idade     — idade mínima em meses para OFERECER (6 = junto com o início
 *               da introdução alimentar, sempre com a liberação do pediatra);
 *   alergenico — alimento alergênico comum: oferecer cedo (a partir dos 6
 *               meses), em pequena quantidade e um por vez, observando reações;
 *   engasgo   — exige preparo específico (cortar, amassar, cozinhar) pelo
 *               risco de engasgo;
 *   evitar    — grupo "evitar": a idade indica a partir de quando deixa de
 *               ser contraindicado (24 = só após os 2 anos; 48 = após 4 anos).
 *
 * O catálogo é orientação geral: quem libera cada alimento é o pediatra.
 */

return [
    'frutas' => ['rotulo' => 'Frutas', 'icone' => '🍌', 'itens' => [
        ['slug' => 'banana', 'nome' => 'Banana', 'idade' => 6],
        ['slug' => 'mamao', 'nome' => 'Mamão', 'idade' => 6],
        ['slug' => 'maca', 'nome' => 'Maçã', 'idade' => 6, 'obs' => 'raspada ou cozida no início; crua em pedaços só quando mastigar bem'],
        ['slug' => 'pera', 'nome' => 'Pera', 'idade' => 6, 'obs' => 'raspada ou cozida no início'],
        ['slug' => 'manga', 'nome' => 'Manga', 'idade' => 6],
        ['slug' => 'melancia', 'nome' => 'Melancia', 'idade' => 6, 'obs' => 'sem sementes'],
        ['slug' => 'melao', 'nome' => 'Melão', 'idade' => 6],
        ['slug' => 'abacate', 'nome' => 'Abacate', 'idade' => 6],
        ['slug' => 'laranja', 'nome' => 'Laranja (gomos sem pele)', 'idade' => 6, 'obs' => 'a fruta, não o suco'],
        ['slug' => 'tangerina', 'nome' => 'Tangerina / mexerica', 'idade' => 6, 'obs' => 'sem sementes e sem a pele do gomo'],
        ['slug' => 'goiaba', 'nome' => 'Goiaba', 'idade' => 6, 'obs' => 'sem sementes'],
        ['slug' => 'caqui', 'nome' => 'Caqui', 'idade' => 6],
        ['slug' => 'ameixa', 'nome' => 'Ameixa', 'idade' => 6, 'obs' => 'sem caroço'],
        ['slug' => 'pessego', 'nome' => 'Pêssego / nectarina', 'idade' => 6, 'obs' => 'sem caroço'],
        ['slug' => 'morango', 'nome' => 'Morango', 'idade' => 6, 'obs' => 'bem lavado; cortado em 4'],
        ['slug' => 'kiwi', 'nome' => 'Kiwi', 'idade' => 6],
        ['slug' => 'abacaxi', 'nome' => 'Abacaxi', 'idade' => 6, 'obs' => 'bem maduro'],
        ['slug' => 'uva', 'nome' => 'Uva', 'idade' => 6, 'engasgo' => true, 'obs' => 'SEMPRE cortada em 4 no sentido do comprimento e sem sementes'],
        ['slug' => 'cereja', 'nome' => 'Cereja', 'idade' => 6, 'engasgo' => true, 'obs' => 'sem caroço e cortada em 4'],
        ['slug' => 'mirtilo', 'nome' => 'Mirtilo', 'idade' => 6, 'engasgo' => true, 'obs' => 'amassado ou cortado ao meio'],
        ['slug' => 'figo', 'nome' => 'Figo', 'idade' => 6],
        ['slug' => 'jabuticaba', 'nome' => 'Jabuticaba', 'idade' => 9, 'engasgo' => true, 'obs' => 'só a polpa, sem casca e sem semente'],
        ['slug' => 'acerola', 'nome' => 'Acerola', 'idade' => 6, 'obs' => 'sem sementes'],
        ['slug' => 'maracuja', 'nome' => 'Maracujá (polpa peneirada)', 'idade' => 9],
        ['slug' => 'coco', 'nome' => 'Coco (polpa ralada fina)', 'idade' => 9],
        ['slug' => 'fruta-seca', 'nome' => 'Frutas secas (damasco, ameixa seca, uva-passa)', 'idade' => 12, 'engasgo' => true, 'obs' => 'hidratadas e picadas; sem adição de açúcar'],
    ]],

    'legumes_verduras' => ['rotulo' => 'Legumes e verduras', 'icone' => '🥕', 'itens' => [
        ['slug' => 'abobora', 'nome' => 'Abóbora / moranga', 'idade' => 6],
        ['slug' => 'cenoura', 'nome' => 'Cenoura', 'idade' => 6, 'engasgo' => true, 'obs' => 'sempre cozida e macia (crua e dura é risco de engasgo)'],
        ['slug' => 'abobrinha', 'nome' => 'Abobrinha', 'idade' => 6],
        ['slug' => 'chuchu', 'nome' => 'Chuchu', 'idade' => 6],
        ['slug' => 'beterraba', 'nome' => 'Beterraba', 'idade' => 6],
        ['slug' => 'batata-baroa', 'nome' => 'Batata-baroa / mandioquinha', 'idade' => 6],
        ['slug' => 'couve-flor', 'nome' => 'Couve-flor', 'idade' => 6],
        ['slug' => 'brocolis', 'nome' => 'Brócolis', 'idade' => 6],
        ['slug' => 'vagem', 'nome' => 'Vagem', 'idade' => 6],
        ['slug' => 'quiabo', 'nome' => 'Quiabo', 'idade' => 6],
        ['slug' => 'tomate', 'nome' => 'Tomate (sem pele e sementes)', 'idade' => 6],
        ['slug' => 'couve', 'nome' => 'Couve', 'idade' => 6],
        ['slug' => 'espinafre', 'nome' => 'Espinafre', 'idade' => 6],
        ['slug' => 'acelga', 'nome' => 'Acelga', 'idade' => 6],
        ['slug' => 'escarola', 'nome' => 'Escarola / almeirão', 'idade' => 6],
        ['slug' => 'alface', 'nome' => 'Alface', 'idade' => 9, 'obs' => 'picadinha'],
        ['slug' => 'repolho', 'nome' => 'Repolho', 'idade' => 6, 'obs' => 'bem cozido'],
        ['slug' => 'berinjela', 'nome' => 'Berinjela', 'idade' => 6],
        ['slug' => 'pepino', 'nome' => 'Pepino', 'idade' => 9, 'obs' => 'sem casca e sem sementes'],
        ['slug' => 'pimentao', 'nome' => 'Pimentão', 'idade' => 9, 'obs' => 'cozido, sem pele'],
        ['slug' => 'ervilha-torta', 'nome' => 'Ervilha-torta', 'idade' => 6],
        ['slug' => 'cebola-alho', 'nome' => 'Cebola e alho (tempero)', 'idade' => 6, 'obs' => 'refogados, no lugar do sal'],
        ['slug' => 'ervas', 'nome' => 'Ervas frescas (salsinha, cebolinha, manjericão)', 'idade' => 6],
        ['slug' => 'cogumelo', 'nome' => 'Cogumelos', 'idade' => 9, 'obs' => 'bem cozidos e picados'],
    ]],

    'cereais_tuberculos' => ['rotulo' => 'Cereais, tubérculos e raízes (carboidratos)', 'icone' => '🍚', 'itens' => [
        ['slug' => 'arroz', 'nome' => 'Arroz', 'idade' => 6],
        ['slug' => 'batata', 'nome' => 'Batata', 'idade' => 6],
        ['slug' => 'batata-doce', 'nome' => 'Batata-doce', 'idade' => 6],
        ['slug' => 'mandioca', 'nome' => 'Mandioca / aipim / macaxeira', 'idade' => 6],
        ['slug' => 'inhame', 'nome' => 'Inhame', 'idade' => 6],
        ['slug' => 'cara', 'nome' => 'Cará', 'idade' => 6],
        ['slug' => 'milho', 'nome' => 'Milho (fubá, canjiquinha, polenta)', 'idade' => 6, 'obs' => 'grão inteiro só após os 12 meses, pela casca'],
        ['slug' => 'aveia', 'nome' => 'Aveia', 'idade' => 6, 'obs' => 'contém traços de glúten'],
        ['slug' => 'macarrao', 'nome' => 'Macarrão (trigo)', 'idade' => 6, 'alergenico' => true, 'obs' => 'glúten pode ser introduzido a partir dos 6 meses'],
        ['slug' => 'pao', 'nome' => 'Pão (sem açúcar, preferir integral)', 'idade' => 9, 'alergenico' => true, 'obs' => 'sem miolo macio em bolo na boca; cortado em tiras'],
        ['slug' => 'quinoa', 'nome' => 'Quinoa', 'idade' => 6],
        ['slug' => 'cuscuz', 'nome' => 'Cuscuz de milho', 'idade' => 6],
        ['slug' => 'tapioca', 'nome' => 'Tapioca', 'idade' => 9, 'obs' => 'macia; pouco valor nutritivo, use com recheio'],
        ['slug' => 'farinha-mandioca', 'nome' => 'Farinha de mandioca', 'idade' => 9, 'obs' => 'em pequena quantidade na comida'],
        ['slug' => 'cevada', 'nome' => 'Cevadinha', 'idade' => 6, 'alergenico' => true, 'obs' => 'contém glúten'],
    ]],

    'leguminosas' => ['rotulo' => 'Leguminosas', 'icone' => '🫘', 'itens' => [
        ['slug' => 'feijao', 'nome' => 'Feijão (carioca, preto, fradinho)', 'idade' => 6, 'obs' => 'grão amassado ou o caldo com grãos amassados'],
        ['slug' => 'lentilha', 'nome' => 'Lentilha', 'idade' => 6],
        ['slug' => 'grao-de-bico', 'nome' => 'Grão-de-bico', 'idade' => 6, 'obs' => 'bem cozido e amassado'],
        ['slug' => 'ervilha', 'nome' => 'Ervilha', 'idade' => 6, 'obs' => 'amassada'],
        ['slug' => 'soja', 'nome' => 'Soja (grão cozido, tofu)', 'idade' => 6, 'alergenico' => true],
        ['slug' => 'fava', 'nome' => 'Fava', 'idade' => 9],
    ]],

    'proteinas' => ['rotulo' => 'Carnes, ovos e peixes (proteínas)', 'icone' => '🍗', 'itens' => [
        ['slug' => 'carne-bovina', 'nome' => 'Carne bovina (músculo, patinho, acém)', 'idade' => 6, 'obs' => 'bem cozida e desfiada/moída; fonte de ferro'],
        ['slug' => 'frango', 'nome' => 'Frango', 'idade' => 6, 'obs' => 'desfiado, sem pele'],
        ['slug' => 'ovo', 'nome' => 'Ovo inteiro (gema e clara)', 'idade' => 6, 'alergenico' => true, 'obs' => 'bem cozido; introduzir cedo reduz risco de alergia (AAP/ESPGHAN)'],
        ['slug' => 'peixe', 'nome' => 'Peixe (tilápia, pescada, sardinha, salmão)', 'idade' => 6, 'alergenico' => true, 'obs' => 'sem espinhas; evitar peixes grandes ricos em mercúrio (cação, peixe-espada, atum fresco)'],
        ['slug' => 'figado', 'nome' => 'Fígado bovino ou de frango', 'idade' => 6, 'obs' => '1 vez por semana — muito rico em ferro e vitamina A'],
        ['slug' => 'carne-suina', 'nome' => 'Carne suína (lombo, pernil)', 'idade' => 6, 'obs' => 'bem cozida'],
        ['slug' => 'peru', 'nome' => 'Peru', 'idade' => 6],
        ['slug' => 'carneiro', 'nome' => 'Carneiro / cordeiro', 'idade' => 6],
        ['slug' => 'miudos', 'nome' => 'Coração e moela', 'idade' => 9, 'obs' => 'bem cozidos e picados'],
        ['slug' => 'camarao', 'nome' => 'Camarão / frutos do mar', 'idade' => 9, 'alergenico' => true, 'engasgo' => true, 'obs' => 'picado; alergênico'],
    ]],

    'laticinios' => ['rotulo' => 'Leite e derivados', 'icone' => '🧀', 'itens' => [
        ['slug' => 'iogurte-natural', 'nome' => 'Iogurte natural integral (sem açúcar)', 'idade' => 9, 'alergenico' => true, 'obs' => 'em pequena quantidade; leite de vaca em preparações'],
        ['slug' => 'queijo', 'nome' => 'Queijos (minas, muçarela, ricota)', 'idade' => 9, 'alergenico' => true, 'obs' => 'pouco sal; ralado ou em pedaços macios'],
        ['slug' => 'manteiga', 'nome' => 'Manteiga (pouca, sem sal)', 'idade' => 9],
        ['slug' => 'leite-vaca-preparo', 'nome' => 'Leite de vaca em preparações (mingau, purê)', 'idade' => 9, 'alergenico' => true, 'obs' => 'só como ingrediente — como bebida, apenas após 12 meses'],
    ]],

    'oleaginosas' => ['rotulo' => 'Oleaginosas e sementes (sempre em pasta)', 'icone' => '🥜', 'itens' => [
        ['slug' => 'pasta-amendoim', 'nome' => 'Pasta de amendoim (diluída, sem açúcar)', 'idade' => 6, 'alergenico' => true, 'engasgo' => true, 'obs' => 'introduzir cedo e manter; NUNCA amendoim inteiro ou pasta crocante antes dos 4 anos'],
        ['slug' => 'pasta-castanhas', 'nome' => 'Pasta de castanhas (caju, amêndoa)', 'idade' => 6, 'alergenico' => true, 'engasgo' => true, 'obs' => 'diluída; castanha inteira só após 4 anos'],
        ['slug' => 'tahine', 'nome' => 'Tahine (pasta de gergelim)', 'idade' => 6, 'alergenico' => true],
        ['slug' => 'chia-linhaca', 'nome' => 'Chia e linhaça (moídas)', 'idade' => 9],
        ['slug' => 'azeite', 'nome' => 'Azeite de oliva (fio na comida)', 'idade' => 6],
    ]],

    'bebidas' => ['rotulo' => 'Bebidas', 'icone' => '💧', 'itens' => [
        ['slug' => 'agua', 'nome' => 'Água (filtrada ou fervida)', 'idade' => 6, 'obs' => 'oferecer nos intervalos a partir do início da introdução alimentar'],
        ['slug' => 'agua-de-coco', 'nome' => 'Água de coco', 'idade' => 12, 'obs' => 'ocasional — não substitui a água'],
        ['slug' => 'leite-vaca-bebida', 'nome' => 'Leite de vaca integral como bebida', 'idade' => 12, 'alergenico' => true, 'obs' => 'antes de 12 meses: só leite materno ou fórmula'],
    ]],

    'evitar' => ['rotulo' => 'Evitar (ou só mais tarde)', 'icone' => '🚫', 'evitar' => true, 'itens' => [
        ['slug' => 'mel', 'nome' => 'Mel', 'idade' => 24, 'obs' => 'risco de botulismo antes de 12 meses; e é açúcar — o Guia brasileiro recomenda nada de açúcar antes dos 2 anos'],
        ['slug' => 'acucar', 'nome' => 'Açúcar, doces, achocolatado, biscoito recheado', 'idade' => 24, 'obs' => 'nenhum açúcar adicionado antes dos 2 anos (MS/OMS)'],
        ['slug' => 'sal', 'nome' => 'Sal adicionado e temperos prontos', 'idade' => 24, 'obs' => 'tempere com ervas, cebola e alho'],
        ['slug' => 'ultraprocessados', 'nome' => 'Ultraprocessados (salgadinho, embutido, salsicha, refrigerante, suco de caixinha)', 'idade' => 24, 'obs' => 'não recomendados em nenhuma idade; nunca antes dos 2 anos'],
        ['slug' => 'suco', 'nome' => 'Sucos (mesmo naturais)', 'idade' => 12, 'obs' => 'o Guia brasileiro orienta a fruta inteira; suco não antes de 12 meses e sempre limitado'],
        ['slug' => 'cha-cafe', 'nome' => 'Chás, café e bebidas com cafeína', 'idade' => 24],
        ['slug' => 'nozes-inteiras', 'nome' => 'Amendoim, castanhas e nozes INTEIROS', 'idade' => 48, 'engasgo' => true, 'obs' => 'em pasta diluída desde os 6 meses; inteiros só após 4 anos'],
        ['slug' => 'pipoca', 'nome' => 'Pipoca', 'idade' => 48, 'engasgo' => true],
        ['slug' => 'uva-inteira', 'nome' => 'Uva inteira, cereja inteira, tomate-cereja inteiro', 'idade' => 48, 'engasgo' => true, 'obs' => 'sempre cortados em 4 até os 4 anos'],
        ['slug' => 'salsicha-rodela', 'nome' => 'Salsicha em rodelas, bala, chiclete, marshmallow', 'idade' => 48, 'engasgo' => true],
        ['slug' => 'cru-duro', 'nome' => 'Cenoura e maçã cruas em pedaços duros', 'idade' => 48, 'engasgo' => true, 'obs' => 'raladas ou cozidas até mastigar muito bem'],
        ['slug' => 'peixe-mercurio', 'nome' => 'Peixes grandes (cação, peixe-espada, atum fresco, marlim)', 'idade' => 24, 'obs' => 'alto teor de mercúrio — raramente, mesmo depois'],
        ['slug' => 'leite-vegetal', 'nome' => 'Bebidas vegetais (arroz, aveia, amêndoa) como substituto do leite', 'idade' => 24, 'obs' => 'não substituem leite materno/fórmula; só com orientação'],
    ]],
];
