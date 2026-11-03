<?php

declare(strict_types=1);

/**
 * Modelo padrão de cardápio + lista de compras da introdução alimentar —
 * "Semana 1" (02 a 08/11/2026), extraído de Lista_Compras_Zuno_Semana_1_Novembro_2026.html
 * / Diario_do_Bebe_Compras_Semana1_Dados.json (cardápio pediátrico revisado).
 *
 * Os pais podem montar o cardápio que quiserem: este arquivo é apenas o
 * ponto de partida ("Carregar a semana-modelo"). As quantidades são de
 * COMPRA para a semana, não metas de ingestão do bebê.
 */

return [
    'titulo' => 'Semana 1 — introdução alimentar',
    'inicio' => '2026-11-02',
    'fim' => '2026-11-08',
    'descricao' => '2 frutas e 1 almoço por dia; ainda sem jantar nesta semana.',
    'categorias' => ['Frutas', 'Proteínas', 'Cereais e tubérculos', 'Leguminosas', 'Legumes e verduras', 'Temperos e preparo'],
    'itens' => [
        ['stable_id' => 'banana', 'categoria' => 'Frutas', 'nome' => 'Banana', 'quantidade' => '3 unidades pequenas', 'nota' => '3 ofertas: 2, 4 e 7/11', 'ordem' => 1],
        ['stable_id' => 'mamao', 'categoria' => 'Frutas', 'nome' => 'Mamão', 'quantidade' => '1 papaia médio', 'nota' => '3 ofertas: 2, 4 e 7/11', 'ordem' => 2],
        ['stable_id' => 'pera', 'categoria' => 'Frutas', 'nome' => 'Pera', 'quantidade' => '2 unidades pequenas', 'nota' => '2 ofertas: 3 e 6/11', 'ordem' => 3],
        ['stable_id' => 'manga', 'categoria' => 'Frutas', 'nome' => 'Manga', 'quantidade' => '2 pequenas ou 1 grande', 'nota' => '3 ofertas: 3, 6 e 8/11', 'ordem' => 4],
        ['stable_id' => 'maca', 'categoria' => 'Frutas', 'nome' => 'Maçã', 'quantidade' => '1 unidade pequena', 'nota' => 'Para cozinhar e amassar em 5/11', 'ordem' => 5],
        ['stable_id' => 'abacate', 'categoria' => 'Frutas', 'nome' => 'Abacate', 'quantidade' => '1 unidade pequena', 'nota' => '2 ofertas: 5 e 8/11', 'ordem' => 6],
        ['stable_id' => 'frango', 'categoria' => 'Proteínas', 'nome' => 'Peito de frango sem osso', 'quantidade' => '200 g', 'nota' => '4 almoços: 2, 4, 6 e 8/11', 'ordem' => 1],
        ['stable_id' => 'carne', 'categoria' => 'Proteínas', 'nome' => 'Carne bovina magra moída', 'quantidade' => '150 g', 'nota' => '3 almoços: 3, 5 e 7/11', 'ordem' => 2],
        ['stable_id' => 'arroz', 'categoria' => 'Cereais e tubérculos', 'nome' => 'Arroz', 'quantidade' => '100 g cru', 'nota' => '3 almoços: 2, 5 e 8/11', 'ordem' => 1],
        ['stable_id' => 'batatadoce', 'categoria' => 'Cereais e tubérculos', 'nome' => 'Batata-doce', 'quantidade' => '1 unidade pequena (≈150 g)', 'nota' => '3/11', 'ordem' => 2],
        ['stable_id' => 'mandioca', 'categoria' => 'Cereais e tubérculos', 'nome' => 'Mandioca', 'quantidade' => '1 pedaço (≈150 g)', 'nota' => '4/11', 'ordem' => 3],
        ['stable_id' => 'mandioquinha', 'categoria' => 'Cereais e tubérculos', 'nome' => 'Mandioquinha', 'quantidade' => '1 unidade pequena (≈120 g)', 'nota' => '6/11', 'ordem' => 4],
        ['stable_id' => 'batata', 'categoria' => 'Cereais e tubérculos', 'nome' => 'Batata', 'quantidade' => '1 unidade pequena (≈120 g)', 'nota' => '7/11', 'ordem' => 5],
        ['stable_id' => 'feijao', 'categoria' => 'Leguminosas', 'nome' => 'Feijão-carioca', 'quantidade' => '100 g seco', 'nota' => '3 almoços: 2, 6 e 8/11', 'ordem' => 1],
        ['stable_id' => 'feijaopreto', 'categoria' => 'Leguminosas', 'nome' => 'Feijão-preto', 'quantidade' => '50 g seco', 'nota' => '1 almoço: 4/11', 'ordem' => 2],
        ['stable_id' => 'lentilha', 'categoria' => 'Leguminosas', 'nome' => 'Lentilha', 'quantidade' => '100 g seca', 'nota' => '3 almoços: 3, 5 e 7/11', 'ordem' => 3],
        ['stable_id' => 'abobora', 'categoria' => 'Legumes e verduras', 'nome' => 'Abóbora', 'quantidade' => '200 g', 'nota' => '2 almoços: 2 e 5/11', 'ordem' => 1],
        ['stable_id' => 'couve', 'categoria' => 'Legumes e verduras', 'nome' => 'Couve', 'quantidade' => '1 maço pequeno', 'nota' => '3 almoços: 2, 4 e 7/11', 'ordem' => 2],
        ['stable_id' => 'cenoura', 'categoria' => 'Legumes e verduras', 'nome' => 'Cenoura', 'quantidade' => '1 unidade média', 'nota' => '2 almoços: 3 e 8/11', 'ordem' => 3],
        ['stable_id' => 'espinafre', 'categoria' => 'Legumes e verduras', 'nome' => 'Espinafre', 'quantidade' => '1 maço pequeno', 'nota' => '2 almoços: 3 e 6/11', 'ordem' => 4],
        ['stable_id' => 'abobrinha', 'categoria' => 'Legumes e verduras', 'nome' => 'Abobrinha', 'quantidade' => '1 unidade pequena', 'nota' => '1 almoço: 4/11', 'ordem' => 5],
        ['stable_id' => 'brocolis', 'categoria' => 'Legumes e verduras', 'nome' => 'Brócolis', 'quantidade' => '1 cabeça pequena', 'nota' => '2 almoços: 5 e 8/11', 'ordem' => 6],
        ['stable_id' => 'chuchu', 'categoria' => 'Legumes e verduras', 'nome' => 'Chuchu', 'quantidade' => '1 unidade pequena', 'nota' => '1 almoço: 6/11', 'ordem' => 7],
        ['stable_id' => 'beterraba', 'categoria' => 'Legumes e verduras', 'nome' => 'Beterraba', 'quantidade' => '1 unidade pequena', 'nota' => '1 almoço: 7/11', 'ordem' => 8],
        ['stable_id' => 'cebola', 'categoria' => 'Temperos e preparo', 'nome' => 'Cebola', 'quantidade' => '1 unidade pequena', 'nota' => 'Temperar as preparações', 'ordem' => 1],
        ['stable_id' => 'alho', 'categoria' => 'Temperos e preparo', 'nome' => 'Alho', 'quantidade' => '1 cabeça', 'nota' => 'Usar pequenas quantidades', 'ordem' => 2],
        ['stable_id' => 'salsinha', 'categoria' => 'Temperos e preparo', 'nome' => 'Salsinha ou cebolinha', 'quantidade' => '1 maço pequeno (opcional)', 'nota' => 'Temperos naturais, se desejar', 'ordem' => 3],
        ['stable_id' => 'azeite', 'categoria' => 'Temperos e preparo', 'nome' => 'Azeite de oliva', 'quantidade' => '1 frasco pequeno (se faltar)', 'nota' => 'Utilizar pouco no preparo', 'ordem' => 4],
    ],
    // Cardápio dia a dia (jantar nulo nesta semana — etapa seguinte do planejamento)
    'dias' => [
        ['data' => '2026-11-02', 'fruta_manha' => 'Banana', 'fruta_tarde' => 'Mamão', 'almoco' => 'Arroz, feijão-carioca, frango, abóbora e couve', 'jantar' => null],
        ['data' => '2026-11-03', 'fruta_manha' => 'Pera', 'fruta_tarde' => 'Manga', 'almoco' => 'Batata-doce, lentilha, carne moída, cenoura e espinafre', 'jantar' => null],
        ['data' => '2026-11-04', 'fruta_manha' => 'Mamão', 'fruta_tarde' => 'Banana', 'almoco' => 'Mandioca, feijão-preto, frango, abobrinha e couve', 'jantar' => null],
        ['data' => '2026-11-05', 'fruta_manha' => 'Maçã cozida', 'fruta_tarde' => 'Abacate', 'almoco' => 'Arroz, lentilha, carne moída, brócolis e abóbora', 'jantar' => null],
        ['data' => '2026-11-06', 'fruta_manha' => 'Manga', 'fruta_tarde' => 'Pera', 'almoco' => 'Mandioquinha, feijão-carioca, frango, chuchu e espinafre', 'jantar' => null],
        ['data' => '2026-11-07', 'fruta_manha' => 'Banana', 'fruta_tarde' => 'Mamão', 'almoco' => 'Batata, lentilha, carne moída, beterraba e couve', 'jantar' => null],
        ['data' => '2026-11-08', 'fruta_manha' => 'Abacate', 'fruta_tarde' => 'Manga', 'almoco' => 'Arroz, feijão-carioca, frango, cenoura e brócolis', 'jantar' => null],
    ],
    'orientacoes' => [
        'Essas quantidades são para comprar, não metas para o bebê comer. Ajuste a oferta à aceitação e à saciedade.',
        'Sirva os alimentos cozidos e macios, amassados com garfo e preferencialmente separados no prato. Não bata no liquidificador nem use peneira.',
        'Evite adicionar sal e açúcar; não ofereça mel antes de 1 ano. Priorize alimentos in natura e minimamente processados.',
        'Feijão e lentilha rendem várias porções depois de cozidos: refrigere prontamente e congele excedentes em porções pequenas, identificadas pela data.',
        'Compre folhas, frutas maduras e hortaliças mais perecíveis perto do uso. Higienize corretamente frutas e verduras.',
        'Mantenha o leite materno ou a fórmula habitual. Para iniciar alimentos com potencial alergênico, siga a orientação individual do pediatra.',
    ],
];
