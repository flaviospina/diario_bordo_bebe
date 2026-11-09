<?php
/**
 * Partial: cartão "Cardápio de hoje" (Rodada 6).
 * Espera: ?array $cardapioDia (linha de cardapio_dias + semana_titulo), array $crianca,
 * string $dataCardapio (Y-m-d), bool $podeRegistrarRefeicao, bool $podeMontarCardapio.
 */
/** @var ?array $cardapioDia */
/** @var array $crianca */
/** @var string $dataCardapio */

$refeicoesCardapio = [
    'fruta_manha' => ['Fruta da manhã', 'lanche_manha'],
    'almoco' => ['Almoço', 'almoco'],
    'fruta_tarde' => ['Fruta da tarde', 'lanche_tarde'],
    'jantar' => ['Jantar', 'jantar'],
];
$temAlgo = $cardapioDia !== null && array_filter(array_map(
    static fn(string $chave) => trim((string)($cardapioDia[$chave] ?? '')),
    array_keys($refeicoesCardapio)
)) !== [];
$urlCompras = url('compras') . '?crianca=' . rawurlencode((string)$crianca['slug']);
$alertasDoDia = \App\Services\ServicoCardapio::alertasDoDia($cardapioDia);
$ehPlanejado = $cardapioDia !== null && !empty($cardapioDia['planejado_json']);
$urlRegistrar = static fn(string $refeicao): string => url('registro.criar', ['categoria' => 'introducao-alimentar'])
    . '?' . http_build_query(['crianca' => $crianca['slug'], 'refeicao' => $refeicao, 'cardapio' => 1, 'data' => $dataCardapio]);
?>
<section class="cartao cartao-cardapio-dia" aria-label="Cardápio do dia">
    <div class="cartao-cardapio-cabeca">
        <span class="selo-categoria" style="width:38px;height:38px;background:var(--pessego-suave)"><?= icone_ui('carrinho-compras', 19, 'var(--pessego-escuro)') ?></span>
        <div style="min-width:0">
            <h3 style="margin:0">Cardápio de <?= $dataCardapio === hoje() ? 'hoje' : e(data_br($dataCardapio . ' 00:00:00', 'd/m')) ?></h3>
            <span class="texto-apoio"><?= $cardapioDia !== null ? e((string)$cardapioDia['semana_titulo']) : 'Nenhuma semana montada para esta data' ?><?= $ehPlanejado ? ' · <span class="chip-planejado">Planejado</span>' : '' ?></span>
        </div>
        <a class="botao botao-secundario botao-pequeno" style="margin-left:auto;flex-shrink:0" href="<?= e($urlCompras) ?>">Lista de compras</a>
    </div>
    <?php if ($temAlgo): ?>
        <ul class="lista-cardapio-dia">
            <?php foreach ($refeicoesCardapio as $chave => [$rotulo, $refeicao]): ?>
                <?php $texto = trim((string)($cardapioDia[$chave] ?? '')); if ($texto === '') { continue; } ?>
                <li>
                    <div style="min-width:0">
                        <strong><?= e($rotulo) ?></strong>
                        <span class="cardapio-texto"><?= e($texto) ?></span>
                        <?php foreach ($alertasDoDia[$chave] ?? [] as $alerta): ?>
                            <span class="alerta-alergenico">⚠️ <?= e($alerta['alimento']) ?>: <?= e($alerta['orientacao']) ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php if (!empty($podeRegistrarRefeicao) && in_array($refeicao, ['almoco', 'jantar'], true)): ?>
                        <a class="botao botao-primario botao-pequeno" style="flex-shrink:0"
                           href="<?= e($urlRegistrar($refeicao)) ?>">
                            Registrar <?= $refeicao === 'almoco' ? 'almoço' : 'jantar' ?> 📷</a>
                    <?php elseif (!empty($podeRegistrarRefeicao)): ?>
                        <a class="botao botao-secundario botao-pequeno" style="flex-shrink:0"
                           href="<?= e($urlRegistrar($refeicao)) ?>">Registrar</a>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
        <?php if (!empty($podeRegistrarRefeicao)): ?>
            <p class="texto-apoio foto-obrigatoria-aviso" style="margin:.5rem 0 0">📷 Almoço e jantar são registrados com foto do prato (obrigatória).</p>
        <?php endif; ?>
    <?php else: ?>
        <p class="texto-apoio" style="margin:.6rem 0 0">
            <?php if (!empty($podeMontarCardapio)): ?>
                Monte o cardápio da semana em <a href="<?= e($urlCompras) ?>">Compras semanais</a> — a cuidadora passa a ver aqui o que preparar.
            <?php else: ?>
                Os responsáveis ainda não montaram o cardápio desta data.
            <?php endif; ?>
        </p>
    <?php endif; ?>
</section>
