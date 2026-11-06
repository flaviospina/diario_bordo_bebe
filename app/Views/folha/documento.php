<?php
use App\Services\ServicoFolha;
/** @var array $documento */ /** @var ?array $resumo */ /** @var string $opVer */ /** @var string $opBaixar */
$tipoRotulo = ServicoFolha::TIPOS[$documento['tipo']] ?? 'Documento';
$ver = url('portal.documento.ver', ['codigo' => $documento['codigo_publico']]) . '?op=' . $opVer;
$baixar = url('portal.documento.baixar', ['codigo' => $documento['codigo_publico']]) . '?op=' . $opBaixar;
$linhas = [
    'Salário-base' => 'salario_base', 'Salário bruto' => 'salario_bruto', 'Horas extras' => 'horas_extras_valor', 'DSR' => 'dsr',
    'Adicionais' => 'adicionais', 'Outros vencimentos' => 'outros_vencimentos', 'INSS' => 'inss', 'IRRF' => 'irrf',
    'Desconto de vale-transporte' => 'desconto_vt', 'Outros descontos' => 'outros_descontos', 'Salário líquido' => 'salario_liquido',
    'Vale-transporte antecipado' => 'vt_antecipado', 'Total pago' => 'total_pago',
];
?>
<p class="texto-apoio"><a href="<?= e(url('portal.documentos')) ?>">← Meus documentos</a></p>
<div class="portal-cabeca">
    <h2><?= e($tipoRotulo) ?></h2>
    <p class="texto-apoio">Competência <?= e(ServicoFolha::rotuloCompetencia($documento['competencia'])) ?>
        <?= $documento['mes_referencia'] !== null ? ' · referente a ' . e(ServicoFolha::rotuloCompetencia($documento['mes_referencia'])) : '' ?></p>
</div>
<section class="cartao">
    <dl class="resumo-grade">
        <div><dt>Situação</dt><dd><span class="chip-estado estado-disponivel">Disponível</span></dd></div>
        <div><dt>Disponibilizado em</dt><dd><?= e(data_br($documento['publicado_em'])) ?></dd></div>
        <?php if (($resumo['data_pagamento'] ?? $documento['data_pagamento']) !== null): ?>
            <div><dt>Data do pagamento</dt><dd><?= e(data_br(($resumo['data_pagamento'] ?? $documento['data_pagamento']) . ' 00:00:00', 'd/m/Y')) ?></dd></div>
        <?php endif; ?>
        <?php if ((int)$documento['versao_numero'] > 1): ?><div><dt>Versão</dt><dd><?= (int)$documento['versao_numero'] ?> (versão corrigida)</dd></div><?php endif; ?>
        <?php if ($documento['descricao'] !== null): ?><div><dt>Descrição</dt><dd><?= e($documento['descricao']) ?></dd></div><?php endif; ?>
    </dl>
    <div class="acoes-doc">
        <a class="botao botao-primario" href="<?= e($ver) ?>"><?= icone_ui('olho', 16, 'currentColor', 2.2) ?> Visualizar PDF</a>
        <a class="botao botao-secundario" href="<?= e($baixar) ?>"><?= icone_ui('download', 16, 'currentColor', 2.2) ?> Baixar PDF</a>
    </div>
</section>
<?php if ($resumo !== null): ?>
    <section class="cartao" aria-label="Valores resumidos">
        <h3>Valores resumidos <span class="texto-apoio" style="font-weight:400">(conferidos pelo empregador a partir do documento oficial)</span></h3>
        <table class="tabela-valores">
            <?php foreach ($linhas as $rotulo => $campo): ?>
                <?php if ($resumo[$campo] === null) { continue; } ?>
                <tr class="<?= $campo === 'salario_liquido' ? 'linha-destaque' : '' ?>"><th><?= e($rotulo) ?><?= $campo === 'horas_extras_valor' && $resumo['horas_extras_qtd'] !== null ? ' (' . e(number_format((float)$resumo['horas_extras_qtd'], 1, ',', '.')) . ' h)' : '' ?></th>
                    <td><?= e(ServicoFolha::dinheiro($resumo[$campo])) ?></td></tr>
            <?php endforeach; ?>
        </table>
        <?php if ($resumo['observacoes'] !== null): ?><p class="texto-apoio"><?= e($resumo['observacoes']) ?></p><?php endif; ?>
        <p class="texto-apoio" style="margin:0">Valores não listados não foram informados. O PDF oficial é a referência.</p>
    </section>
<?php endif; ?>
