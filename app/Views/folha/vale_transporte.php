<?php
use App\Services\ServicoFolha;
/** @var array $vales */
?>
<div class="portal-cabeca">
    <h2>Vale-transporte</h2>
    <p class="texto-apoio">Valores antecipados por mês de referência, com a data em que foram pagos.</p>
</div>
<?php if ($vales === []): ?>
    <div class="cartao"><p class="texto-apoio" style="margin:0">Nenhum vale-transporte registrado até agora.</p></div>
<?php else: ?>
    <div class="lista-docs">
        <?php foreach ($vales as $v): ?>
            <div class="doc-item doc-vt">
                <span class="selo-categoria doc-selo"><?= icone_ui('carrinho', 20, '#5A539E') ?></span>
                <div style="min-width:0">
                    <div class="titulo">Vale-transporte de <?= e(ServicoFolha::rotuloCompetencia($v['mes_referencia'])) ?>
                        <?= $v['data_pagamento'] !== null ? '— pago em ' . e(data_br($v['data_pagamento'] . ' 00:00:00', 'd/m/Y')) : '' ?></div>
                    <div class="detalhe">
                        <?= $v['valor_total'] !== null ? 'Total ' . e(ServicoFolha::dinheiro($v['valor_total'])) : 'Valor não informado' ?>
                        <?= $v['valor_diario'] !== null && $v['dias'] !== null ? ' · ' . e(ServicoFolha::dinheiro($v['valor_diario'])) . ' × ' . (int)$v['dias'] . ' dia(s)' : '' ?>
                        <?= $v['competencia'] !== null ? ' · registrado na folha de ' . e(ServicoFolha::rotuloCompetencia($v['competencia'], true)) : '' ?>
                        <?= $v['desconto_folha'] !== null ? ' · desconto em folha ' . e(ServicoFolha::dinheiro($v['desconto_folha'])) : '' ?>
                        <?= $v['data_pagamento'] !== null && $v['competencia'] !== null && substr((string)$v['data_pagamento'], 0, 7) < (string)$v['mes_referencia'] ? ' · <strong>antecipado</strong>' : '' ?>
                    </div>
                </div>
                <?php if ($v['documento_codigo'] !== null && $v['documento_status'] === 'publicado'): ?>
                    <a class="botao botao-secundario botao-pequeno" style="flex-shrink:0" href="<?= e(url('portal.documento', ['codigo' => $v['documento_codigo']])) ?>">Comprovante</a>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<p class="texto-apoio portal-nota">O vale-transporte é um benefício pago antecipadamente para o mês seguinte; não se confunde com salário nem com o desconto em folha.</p>
