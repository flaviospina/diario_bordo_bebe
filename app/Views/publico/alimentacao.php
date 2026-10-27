<?php
/** @var ?float $idadeMeses */
/** @var array $grupos  porIdade() */
/** @var array $fase */

$fases = [
    ['faixa' => 'até 6 m', 'de' => 0, 'ate' => 6, 'titulo' => 'Só leite', 'texto' => 'Leite materno exclusivo (ou fórmula). Nada de água, chá, suco ou papinha antes dos 6 meses.'],
    ['faixa' => '6 m', 'de' => 6, 'ate' => 7, 'titulo' => 'Começando', 'texto' => 'Fruta amassada/raspada + 1 refeição principal com um alimento de cada grupo: cereal ou tubérculo, leguminosa, carne/ovo e legumes. Amassado com garfo — nada de liquidificador. Água nos intervalos. Ovo e peixe desde já, um por vez.'],
    ['faixa' => '7–8 m', 'de' => 7, 'ate' => 9, 'titulo' => '2 refeições principais', 'texto' => '2 frutas + almoço e jantar com os 4 grupos. Textura evoluindo para pedaços macios. Ofereça o novo alimento 2–3 dias antes de concluir que não gostou.'],
    ['faixa' => '9–11 m', 'de' => 9, 'ate' => 12, 'titulo' => 'Rumo à comida da família', 'texto' => 'Pedaços pequenos e macios; pode comer com as mãos. Comida da família sem sal, açúcar ou ultraprocessados. 3 refeições + 2 lanches.'],
    ['faixa' => '12–23 m', 'de' => 12, 'ate' => 24, 'titulo' => 'Comida da família', 'texto' => 'Café, almoço, jantar + 2 lanches, com pouco sal e sem açúcar. Leite de vaca integral pode virar bebida. Mel, açúcar, sucos e ultraprocessados seguem fora até os 2 anos.'],
    ['faixa' => '2 anos +', 'de' => 24, 'ate' => 999, 'titulo' => 'Alimentação da família', 'texto' => 'Sem ultraprocessados; mel e açúcar com moderação. Nozes inteiras, pipoca e uva inteira só após 4 anos (engasgo).'],
];
$m = $idadeMeses !== null ? (int)floor($idadeMeses) : null;
?>
<section class="landing-hero" style="padding-bottom:.4rem">
    <h2>Introdução alimentar por idade</h2>
    <p class="texto-apoio">O que oferecer ao bebê em cada fase, segundo o Guia Alimentar brasileiro (Ministério da Saúde),
        a Sociedade Brasileira de Pediatria, a OMS e as sociedades europeia e americana de pediatria.
        <strong>Quem libera cada alimento é sempre o pediatra.</strong></p>
    <?php if ($m !== null): ?>
        <p class="alerta alerta-sucesso" style="display:inline-block">Idade do bebê: <strong><?= $m ?> meses</strong> —
            fase <strong><?= e($fase['titulo']) ?></strong></p>
    <?php endif; ?>
</section>

<section class="cartao tutorial-secao" id="fases">
    <h3><?= icone_ui('relogio', 18, '#3E6A64') ?> As fases</h3>
    <div class="tabela-rolavel"><table class="tabela tabela-compacta tabela-alimentos">
        <thead><tr><th>Idade</th><th>Fase</th><th>Como é</th></tr></thead>
        <tbody>
        <?php foreach ($fases as $f): ?>
            <?php $atual = $m !== null && $m >= $f['de'] && $m < $f['ate']; ?>
            <tr <?= $atual ? 'style="background:var(--salvia-suave)"' : '' ?>>
                <td><span class="selo-idade"><?= e($f['faixa']) ?></span></td>
                <td><strong><?= e($f['titulo']) ?></strong><?= $atual ? ' <small>(agora)</small>' : '' ?></td>
                <td><?= e($f['texto']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <p class="texto-apoio" style="margin:.6rem 0 0">Regras que valem para todas as fases: sem sal e sem açúcar adicionados; comida de verdade
        (in natura ou minimamente processada); um alimento novo por vez, observando reações por 2–3 dias; a criança decide quanto come —
        nunca force. Ferro e vitamina D: siga a suplementação prescrita pelo pediatra.</p>
</section>

<?php foreach ($grupos as $chave => $grupo): ?>
    <section class="cartao tutorial-secao" id="<?= e($chave) ?>">
        <h3><?= e($grupo['icone']) ?> <?= e($grupo['rotulo']) ?></h3>
        <?php $todos = array_merge($grupo['agora'], $grupo['depois']); ?>
        <div class="tabela-rolavel"><table class="tabela tabela-compacta tabela-alimentos">
            <thead><tr><th><?= $grupo['evitar'] ? 'Só após' : 'A partir de' ?></th><th>Alimento</th><th>Observação</th></tr></thead>
            <tbody>
            <?php foreach ($todos as $item): ?>
                <?php
                $classe = $grupo['evitar'] ? 'selo-idade-evitar' : (($m !== null && (int)$item['idade'] > $m) ? 'selo-idade-depois' : '');
                $rotuloIdade = (int)$item['idade'] >= 24 ? ((int)$item['idade'] / 12) . ' anos' : $item['idade'] . ' m';
                ?>
                <tr>
                    <td><span class="selo-idade <?= e($classe) ?>"><?= e($rotuloIdade) ?></span></td>
                    <td><?= e($item['nome']) ?><?= !empty($item['alergenico']) ? ' <small title="alergênico comum">⚠️</small>' : '' ?><?= !empty($item['engasgo']) ? ' <small title="risco de engasgo — preparo específico">✂️</small>' : '' ?></td>
                    <td class="texto-apoio"><?= e((string)($item['obs'] ?? '')) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
    </section>
<?php endforeach; ?>

<section class="cartao tutorial-secao" id="legenda">
    <h3><?= icone_ui('alerta', 18, '#B05E3C') ?> Legenda e segurança</h3>
    <p><strong>⚠️ Alergênico comum</strong> (ovo, amendoim, peixe, trigo, leite, soja, castanhas, gergelim, frutos do mar): as sociedades de
        pediatria orientam <em>não atrasar</em> — oferecer a partir dos 6 meses, em pequena quantidade, um por vez, e manter na rotina.
        Bebês com eczema grave ou alergia já conhecida: só com o plano do pediatra.</p>
    <p><strong>✂️ Risco de engasgo</strong>: uva, cereja e tomate-cereja sempre cortados em 4 no comprimento; cenoura e maçã cozidas ou
        raladas; nada de amendoim/castanha inteiros, pipoca, bala ou salsicha em rodelas antes dos 4 anos. A criança come sentada e
        sempre com um adulto por perto.</p>
    <p><strong>Nunca antes de 1 ano:</strong> mel (botulismo) e leite de vaca como bebida. <strong>Nunca antes de 2 anos:</strong> açúcar,
        sal adicionado, ultraprocessados, sucos (mesmo naturais), chá e café.</p>
</section>

<section class="cartao tutorial-secao" id="fontes">
    <h3><?= icone_ui('livro', 18, '#3E6A64') ?> Fontes</h3>
    <ul class="texto-apoio" style="margin:0; padding-left:1.1rem">
        <li><a href="https://bvsms.saude.gov.br/bvs/publicacoes/guia_alimentar_crianca_brasileira_versao_resumida.pdf" target="_blank" rel="noopener">Guia Alimentar para Crianças Brasileiras Menores de 2 Anos — Ministério da Saúde (2019; versão resumida)</a></li>
        <li><a href="https://www.sbp.com.br/fileadmin/user_upload/24579c-GPA_-_Alim_Compl_p_Lactente_Saudavel.pdf" target="_blank" rel="noopener">Alimentação Complementar para o Lactente Saudável — Sociedade Brasileira de Pediatria</a></li>
        <li><a href="https://www.who.int/news-room/fact-sheets/detail/infant-and-young-child-feeding" target="_blank" rel="noopener">Infant and young child feeding — Organização Mundial da Saúde</a></li>
        <li><a href="https://www.espghan.org/dam/jcr:3d960daa-e2f3-499f-9df9-2da682976cec/2017%20Complementary_Feeding__A_Position_Paper_by_the.21.pdf" target="_blank" rel="noopener">Complementary Feeding: Position Paper — ESPGHAN (2017)</a></li>
        <li><a href="https://publications.aap.org/pediatrics/article/151/2/e2022058380/190526/Complementary-and-Allergenic-Food-Introduction-in" target="_blank" rel="noopener">Complementary and Allergenic Food Introduction in Infants — Pediatrics / AAP (2023)</a></li>
    </ul>
    <p class="texto-apoio" style="margin:.6rem 0 0">Este guia é orientação geral e não substitui a avaliação do pediatra, que conhece a história do seu bebê.</p>
</section>
