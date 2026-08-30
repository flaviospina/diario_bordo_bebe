<?php

declare(strict_types=1);

/**
 * Seed do catálogo GLOBAL de categorias (familia_id NULL) — seção 6 do escopo.
 * Idempotente: o executor insere apenas slugs globais ainda inexistentes.
 *
 * schema_campos dirige a renderização do formulário (nada de formulário
 * hardcoded). Tipos previstos pelo renderizador (Fase 3):
 *   opcoes           — botões de seleção única  {opcoes: [{valor, rotulo}]}
 *   numero           — campo numérico           {unidade?, minimo?, maximo?, passo?}
 *   texto            — linha única
 *   texto_longo      — textarea
 *   duracao_minutos  — minutos (teclado numérico)
 *   escala           — escala visual 1..N       {maximo: 5}
 * Campos comuns a TODO registro (inicio, fim, observacao, fotos) não entram
 * no schema — pertencem ao registro em si.
 */

$opcao = static fn(string $valor, string $rotulo): array => ['valor' => $valor, 'rotulo' => $rotulo];

$campo = static function (string $nome, string $rotulo, string $tipo, array $extras = []): array {
    return array_merge(['nome' => $nome, 'rotulo' => $rotulo, 'tipo' => $tipo], $extras);
};

$quantidadeSimples = [
    $campo('quantidade', 'Quantidade', 'opcoes', ['opcoes' => [
        $opcao('pouca', 'Pouca'), $opcao('media', 'Média'), $opcao('muita', 'Muita'),
    ]]),
];

return [
    // ── Alimentação ────────────────────────────────────────────
    ['grupo' => 'alimentacao', 'nome' => 'Amamentação', 'slug' => 'amamentacao', 'icone' => '🤱', 'cor' => '#e8871e', 'ordem' => 10, 'schema_campos' => ['campos' => [
        $campo('lado', 'Seio', 'opcoes', ['obrigatorio' => true, 'opcoes' => [
            $opcao('esquerdo', 'Esquerdo'), $opcao('direito', 'Direito'), $opcao('ambos', 'Ambos'),
        ]]),
        $campo('duracao_esquerdo_min', 'Duração seio esquerdo (min)', 'duracao_minutos'),
        $campo('duracao_direito_min', 'Duração seio direito (min)', 'duracao_minutos'),
    ]]],
    ['grupo' => 'alimentacao', 'nome' => 'Mamadeira', 'slug' => 'mamadeira', 'icone' => '🍼', 'cor' => '#e8871e', 'ordem' => 20, 'schema_campos' => ['campos' => [
        $campo('volume_ml', 'Volume (ml)', 'numero', ['obrigatorio' => true, 'unidade' => 'ml', 'minimo' => 0, 'maximo' => 500, 'passo' => 10]),
        $campo('tipo', 'Conteúdo', 'opcoes', ['obrigatorio' => true, 'opcoes' => [
            $opcao('leite_materno', 'Leite materno'), $opcao('formula', 'Fórmula'),
        ]]),
        $campo('volume_restante_ml', 'Sobrou (ml)', 'numero', ['unidade' => 'ml', 'minimo' => 0, 'maximo' => 500, 'passo' => 10]),
    ]]],
    ['grupo' => 'alimentacao', 'nome' => 'Fórmula preparada', 'slug' => 'formula-preparada', 'icone' => '🥛', 'cor' => '#e8871e', 'ordem' => 30, 'schema_campos' => ['campos' => [
        $campo('volume_ml', 'Volume preparado (ml)', 'numero', ['obrigatorio' => true, 'unidade' => 'ml', 'minimo' => 0, 'maximo' => 500, 'passo' => 10]),
    ]]],
    ['grupo' => 'alimentacao', 'nome' => 'Papinha / introdução alimentar', 'slug' => 'papinha', 'icone' => '🥣', 'cor' => '#e8871e', 'ordem' => 40, 'schema_campos' => ['campos' => [
        $campo('alimento', 'Alimento', 'texto', ['obrigatorio' => true]),
        $campo('quantidade', 'Quantidade', 'texto'),
        $campo('aceitacao', 'Aceitação', 'opcoes', ['opcoes' => [
            $opcao('otima', 'Ótima'), $opcao('boa', 'Boa'), $opcao('parcial', 'Parcial'), $opcao('recusou', 'Recusou'),
        ]]),
    ]]],
    ['grupo' => 'alimentacao', 'nome' => 'Água', 'slug' => 'agua', 'icone' => '💧', 'cor' => '#e8871e', 'ordem' => 50, 'schema_campos' => ['campos' => [
        $campo('volume_ml', 'Volume (ml)', 'numero', ['unidade' => 'ml', 'minimo' => 0, 'maximo' => 500, 'passo' => 10]),
    ]]],
    ['grupo' => 'alimentacao', 'nome' => 'Arroto', 'slug' => 'arroto', 'icone' => '😮‍💨', 'cor' => '#e8871e', 'ordem' => 60, 'schema_campos' => ['campos' => []]],
    ['grupo' => 'alimentacao', 'nome' => 'Regurgitação', 'slug' => 'regurgitacao', 'icone' => '💦', 'cor' => '#e8871e', 'ordem' => 70, 'schema_campos' => ['campos' => $quantidadeSimples]],
    ['grupo' => 'alimentacao', 'nome' => 'Vômito', 'slug' => 'vomito', 'icone' => '🤮', 'cor' => '#e8871e', 'ordem' => 80, 'schema_campos' => ['campos' => $quantidadeSimples]],

    // ── Sono ───────────────────────────────────────────────────
    ['grupo' => 'sono', 'nome' => 'Soneca', 'slug' => 'soneca', 'icone' => '😴', 'cor' => '#5b6abf', 'ordem' => 10, 'schema_campos' => ['campos' => [
        $campo('onde_dormiu', 'Onde dormiu', 'opcoes', ['opcoes' => [
            $opcao('berco', 'Berço'), $opcao('carrinho', 'Carrinho'), $opcao('colo', 'Colo'), $opcao('cama', 'Cama'), $opcao('outro', 'Outro'),
        ]]),
        $campo('como_adormeceu', 'Como adormeceu', 'opcoes', ['opcoes' => [
            $opcao('sozinho', 'Sozinho(a)'), $opcao('colo', 'No colo'), $opcao('mamando', 'Mamando'), $opcao('embalado', 'Embalado(a)'),
        ]]),
    ]]],
    ['grupo' => 'sono', 'nome' => 'Sono noturno', 'slug' => 'sono-noturno', 'icone' => '🌙', 'cor' => '#5b6abf', 'ordem' => 20, 'schema_campos' => ['campos' => [
        $campo('onde_dormiu', 'Onde dormiu', 'opcoes', ['opcoes' => [
            $opcao('berco', 'Berço'), $opcao('cama_pais', 'Cama dos pais'), $opcao('outro', 'Outro'),
        ]]),
    ]]],
    ['grupo' => 'sono', 'nome' => 'Despertar', 'slug' => 'despertar', 'icone' => '👀', 'cor' => '#5b6abf', 'ordem' => 30, 'schema_campos' => ['campos' => []]],
    ['grupo' => 'sono', 'nome' => 'Dificuldade para dormir', 'slug' => 'dificuldade-dormir', 'icone' => '😵‍💫', 'cor' => '#5b6abf', 'ordem' => 40, 'schema_campos' => ['campos' => [
        $campo('o_que_tentou', 'O que foi tentado', 'texto_longo'),
    ]]],

    // ── Higiene ────────────────────────────────────────────────
    ['grupo' => 'higiene', 'nome' => 'Fralda', 'slug' => 'fralda', 'icone' => '🧷', 'cor' => '#2f8f5b', 'ordem' => 10, 'schema_campos' => ['campos' => [
        $campo('conteudo', 'Conteúdo', 'opcoes', ['obrigatorio' => true, 'opcoes' => [
            $opcao('xixi', 'Xixi'), $opcao('coco', 'Cocô'), $opcao('ambos', 'Ambos'),
        ]]),
        $campo('cor', 'Cor (cocô)', 'opcoes', ['opcoes' => [
            $opcao('amarela', 'Amarela'), $opcao('marrom', 'Marrom'), $opcao('verde', 'Verde'), $opcao('outra', 'Outra'),
        ]]),
        $campo('consistencia', 'Consistência (cocô)', 'opcoes', ['opcoes' => [
            $opcao('liquida', 'Líquida'), $opcao('pastosa', 'Pastosa'), $opcao('firme', 'Firme'),
        ]]),
        $campo('quantidade', 'Quantidade', 'opcoes', ['opcoes' => [
            $opcao('pouca', 'Pouca'), $opcao('media', 'Média'), $opcao('muita', 'Muita'),
        ]]),
    ]]],
    ['grupo' => 'higiene', 'nome' => 'Banho', 'slug' => 'banho', 'icone' => '🛁', 'cor' => '#2f8f5b', 'ordem' => 20, 'schema_campos' => ['campos' => []]],
    ['grupo' => 'higiene', 'nome' => 'Troca de roupa', 'slug' => 'troca-roupa', 'icone' => '👕', 'cor' => '#2f8f5b', 'ordem' => 30, 'schema_campos' => ['campos' => []]],
    ['grupo' => 'higiene', 'nome' => 'Higiene nasal', 'slug' => 'higiene-nasal', 'icone' => '👃', 'cor' => '#2f8f5b', 'ordem' => 40, 'schema_campos' => ['campos' => []]],
    ['grupo' => 'higiene', 'nome' => 'Corte de unha', 'slug' => 'corte-unha', 'icone' => '💅', 'cor' => '#2f8f5b', 'ordem' => 50, 'schema_campos' => ['campos' => []]],
    ['grupo' => 'higiene', 'nome' => 'Escovação / limpeza de gengiva', 'slug' => 'escovacao', 'icone' => '🪥', 'cor' => '#2f8f5b', 'ordem' => 60, 'schema_campos' => ['campos' => []]],

    // ── Saúde ──────────────────────────────────────────────────
    ['grupo' => 'saude', 'nome' => 'Medicação', 'slug' => 'medicacao', 'icone' => '💊', 'cor' => '#c2413b', 'ordem' => 10, 'schema_campos' => ['campos' => [
        $campo('nome', 'Medicamento', 'texto', ['obrigatorio' => true]),
        $campo('dose', 'Dose', 'texto', ['obrigatorio' => true]),
        $campo('via', 'Via', 'opcoes', ['opcoes' => [
            $opcao('oral', 'Oral'), $opcao('nasal', 'Nasal'), $opcao('topica', 'Tópica'), $opcao('retal', 'Retal'), $opcao('inalatoria', 'Inalatória'),
        ]]),
    ]]],
    ['grupo' => 'saude', 'nome' => 'Temperatura', 'slug' => 'temperatura', 'icone' => '🌡️', 'cor' => '#c2413b', 'ordem' => 20, 'schema_campos' => ['campos' => [
        $campo('valor_c', 'Temperatura (°C)', 'numero', ['obrigatorio' => true, 'unidade' => '°C', 'minimo' => 34, 'maximo' => 42, 'passo' => 0.1]),
        $campo('local', 'Onde mediu', 'opcoes', ['opcoes' => [
            $opcao('axila', 'Axila'), $opcao('testa', 'Testa'), $opcao('ouvido', 'Ouvido'),
        ]]),
    ]]],
    ['grupo' => 'saude', 'nome' => 'Sintoma', 'slug' => 'sintoma', 'icone' => '🤒', 'cor' => '#c2413b', 'ordem' => 30, 'schema_campos' => ['campos' => [
        $campo('tipo', 'Sintoma', 'opcoes', ['obrigatorio' => true, 'opcoes' => [
            $opcao('febre', 'Febre'), $opcao('tosse', 'Tosse'), $opcao('coriza', 'Coriza'),
            $opcao('colica', 'Cólica'), $opcao('assadura', 'Assadura'), $opcao('alergia', 'Alergia'), $opcao('outro', 'Outro'),
        ]]),
        $campo('descricao', 'Descrição', 'texto_longo'),
    ]]],
    ['grupo' => 'saude', 'nome' => 'Vacina', 'slug' => 'vacina', 'icone' => '💉', 'cor' => '#c2413b', 'ordem' => 40, 'schema_campos' => ['campos' => [
        $campo('nome', 'Vacina', 'texto', ['obrigatorio' => true]),
        $campo('dose', 'Dose', 'texto'),
    ]]],
    ['grupo' => 'saude', 'nome' => 'Consulta médica', 'slug' => 'consulta-medica', 'icone' => '🩺', 'cor' => '#c2413b', 'ordem' => 50, 'schema_campos' => ['campos' => [
        $campo('especialidade', 'Especialidade', 'texto'),
        $campo('local', 'Local', 'texto'),
    ]]],

    // ── Desenvolvimento ────────────────────────────────────────
    ['grupo' => 'desenvolvimento', 'nome' => 'Tummy time', 'slug' => 'tummy-time', 'icone' => '🐢', 'cor' => '#7b52a8', 'ordem' => 10, 'schema_campos' => ['campos' => [
        $campo('duracao_min', 'Duração (min)', 'duracao_minutos'),
    ]]],
    ['grupo' => 'desenvolvimento', 'nome' => 'Estímulo / brincadeira', 'slug' => 'estimulo', 'icone' => '🧸', 'cor' => '#7b52a8', 'ordem' => 20, 'schema_campos' => ['campos' => [
        $campo('atividade', 'Atividade', 'texto'),
    ]]],
    ['grupo' => 'desenvolvimento', 'nome' => 'Leitura', 'slug' => 'leitura', 'icone' => '📖', 'cor' => '#7b52a8', 'ordem' => 30, 'schema_campos' => ['campos' => [
        $campo('livro', 'Livro / história', 'texto'),
    ]]],
    ['grupo' => 'desenvolvimento', 'nome' => 'Música', 'slug' => 'musica', 'icone' => '🎵', 'cor' => '#7b52a8', 'ordem' => 40, 'schema_campos' => ['campos' => []]],
    ['grupo' => 'desenvolvimento', 'nome' => 'Banho de sol', 'slug' => 'banho-de-sol', 'icone' => '☀️', 'cor' => '#7b52a8', 'ordem' => 50, 'schema_campos' => ['campos' => [
        $campo('duracao_min', 'Duração (min)', 'duracao_minutos'),
    ]]],
    ['grupo' => 'desenvolvimento', 'nome' => 'Marco de desenvolvimento', 'slug' => 'marco-desenvolvimento', 'icone' => '⭐', 'cor' => '#7b52a8', 'ordem' => 60, 'schema_campos' => ['campos' => [
        $campo('descricao', 'O que aconteceu', 'texto_longo', ['obrigatorio' => true]),
    ]]],

    // ── Rotina e deslocamento ──────────────────────────────────
    ['grupo' => 'rotina', 'nome' => 'Saída de casa', 'slug' => 'saida-casa', 'icone' => '🚪', 'cor' => '#3c7ea6', 'ordem' => 10, 'schema_campos' => ['campos' => [
        $campo('destino', 'Destino', 'texto'),
    ]]],
    ['grupo' => 'rotina', 'nome' => 'Passeio', 'slug' => 'passeio', 'icone' => '🚶', 'cor' => '#3c7ea6', 'ordem' => 20, 'schema_campos' => ['campos' => [
        $campo('local', 'Local', 'texto'),
    ]]],
    ['grupo' => 'rotina', 'nome' => 'Escola / creche', 'slug' => 'escola-creche', 'icone' => '🏫', 'cor' => '#3c7ea6', 'ordem' => 30, 'schema_campos' => ['campos' => [
        $campo('evento', 'Evento', 'opcoes', ['obrigatorio' => true, 'opcoes' => [
            $opcao('entrada', 'Entrada'), $opcao('saida', 'Saída'),
        ]]),
    ]]],
    ['grupo' => 'rotina', 'nome' => 'Visita', 'slug' => 'visita', 'icone' => '🏠', 'cor' => '#3c7ea6', 'ordem' => 40, 'schema_campos' => ['campos' => [
        $campo('quem', 'Quem', 'texto'),
    ]]],
    ['grupo' => 'rotina', 'nome' => 'Chegada / saída de terceiros', 'slug' => 'terceiros', 'icone' => '🔔', 'cor' => '#3c7ea6', 'ordem' => 50, 'schema_campos' => ['campos' => [
        $campo('pessoa', 'Pessoa', 'texto', ['obrigatorio' => true]),
        $campo('evento', 'Evento', 'opcoes', ['obrigatorio' => true, 'opcoes' => [
            $opcao('chegada', 'Chegada'), $opcao('saida', 'Saída'),
        ]]),
    ]]],

    // ── Comportamento ──────────────────────────────────────────
    ['grupo' => 'comportamento', 'nome' => 'Humor', 'slug' => 'humor', 'icone' => '🙂', 'cor' => '#b0731f', 'ordem' => 10, 'schema_campos' => ['campos' => [
        $campo('nivel', 'Como está o humor?', 'escala', ['obrigatorio' => true, 'maximo' => 5]),
    ]]],
    ['grupo' => 'comportamento', 'nome' => 'Choro prolongado', 'slug' => 'choro-prolongado', 'icone' => '😭', 'cor' => '#b0731f', 'ordem' => 20, 'schema_campos' => ['campos' => [
        $campo('duracao_min', 'Duração (min)', 'duracao_minutos'),
        $campo('motivo_suspeito', 'Possível motivo', 'texto'),
    ]]],
    ['grupo' => 'comportamento', 'nome' => 'Cólica', 'slug' => 'colica', 'icone' => '😖', 'cor' => '#b0731f', 'ordem' => 30, 'schema_campos' => ['campos' => [
        $campo('duracao_min', 'Duração (min)', 'duracao_minutos'),
        $campo('o_que_aliviou', 'O que aliviou', 'texto'),
    ]]],

    // ── Apoio doméstico ────────────────────────────────────────
    ['grupo' => 'apoio', 'nome' => 'Esterilização de mamadeiras', 'slug' => 'esterilizacao', 'icone' => '🫧', 'cor' => '#5f7470', 'ordem' => 10, 'schema_campos' => ['campos' => []]],
    ['grupo' => 'apoio', 'nome' => 'Lavagem de roupa da criança', 'slug' => 'lavagem-roupa', 'icone' => '🧺', 'cor' => '#5f7470', 'ordem' => 20, 'schema_campos' => ['campos' => []]],
    ['grupo' => 'apoio', 'nome' => 'Organização do quarto', 'slug' => 'organizacao-quarto', 'icone' => '🛏️', 'cor' => '#5f7470', 'ordem' => 30, 'schema_campos' => ['campos' => []]],
    ['grupo' => 'apoio', 'nome' => 'Pedido de suprimento', 'slug' => 'pedido-suprimento', 'icone' => '🛒', 'cor' => '#5f7470', 'ordem' => 40, 'schema_campos' => ['campos' => [
        $campo('item', 'Item', 'texto', ['obrigatorio' => true]),
        $campo('nivel', 'Situação', 'opcoes', ['obrigatorio' => true, 'opcoes' => [
            $opcao('baixo', 'Está acabando'), $opcao('acabou', 'Acabou'),
        ]]),
    ]]],

    // ── Turno ──────────────────────────────────────────────────
    ['grupo' => 'turno', 'nome' => 'Entrada do cuidador', 'slug' => 'turno-entrada', 'icone' => '🟢', 'cor' => '#41525d', 'ordem' => 10, 'schema_campos' => ['campos' => []]],
    ['grupo' => 'turno', 'nome' => 'Pausa', 'slug' => 'turno-pausa', 'icone' => '⏸️', 'cor' => '#41525d', 'ordem' => 20, 'schema_campos' => ['campos' => []]],
    ['grupo' => 'turno', 'nome' => 'Saída do cuidador', 'slug' => 'turno-saida', 'icone' => '🔴', 'cor' => '#41525d', 'ordem' => 30, 'schema_campos' => ['campos' => []]],

    // ── Intercorrência (sempre com gravidade e fluxo de ciência) ──
    ['grupo' => 'intercorrencia', 'nome' => 'Queda', 'slug' => 'queda', 'icone' => '⚠️', 'cor' => '#a2251d', 'ordem' => 10, 'schema_campos' => ['campos' => [
        $campo('gravidade', 'Gravidade', 'opcoes', ['obrigatorio' => true, 'opcoes' => [
            $opcao('leve', 'Leve'), $opcao('moderada', 'Moderada'), $opcao('grave', 'Grave'),
        ]]),
        $campo('acao_tomada', 'O que foi feito', 'texto_longo', ['obrigatorio' => true]),
    ]]],
    ['grupo' => 'intercorrencia', 'nome' => 'Engasgo', 'slug' => 'engasgo', 'icone' => '⚠️', 'cor' => '#a2251d', 'ordem' => 20, 'schema_campos' => ['campos' => [
        $campo('gravidade', 'Gravidade', 'opcoes', ['obrigatorio' => true, 'opcoes' => [
            $opcao('leve', 'Leve'), $opcao('moderada', 'Moderada'), $opcao('grave', 'Grave'),
        ]]),
        $campo('acao_tomada', 'O que foi feito', 'texto_longo', ['obrigatorio' => true]),
    ]]],
    ['grupo' => 'intercorrencia', 'nome' => 'Acidente', 'slug' => 'acidente', 'icone' => '⚠️', 'cor' => '#a2251d', 'ordem' => 30, 'schema_campos' => ['campos' => [
        $campo('gravidade', 'Gravidade', 'opcoes', ['obrigatorio' => true, 'opcoes' => [
            $opcao('leve', 'Leve'), $opcao('moderada', 'Moderada'), $opcao('grave', 'Grave'),
        ]]),
        $campo('acao_tomada', 'O que foi feito', 'texto_longo', ['obrigatorio' => true]),
    ]]],
    ['grupo' => 'intercorrencia', 'nome' => 'Reação alérgica', 'slug' => 'reacao-alergica', 'icone' => '⚠️', 'cor' => '#a2251d', 'ordem' => 40, 'schema_campos' => ['campos' => [
        $campo('gravidade', 'Gravidade', 'opcoes', ['obrigatorio' => true, 'opcoes' => [
            $opcao('leve', 'Leve'), $opcao('moderada', 'Moderada'), $opcao('grave', 'Grave'),
        ]]),
        $campo('possivel_causa', 'Possível causa', 'texto'),
        $campo('acao_tomada', 'O que foi feito', 'texto_longo', ['obrigatorio' => true]),
    ]]],
];
