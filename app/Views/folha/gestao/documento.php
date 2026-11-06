<?php
use App\Core\Csrf;
use App\Services\ServicoFolha;
/** @var array $documento */ /** @var ?array $resumo */ /** @var array $versoes */ /** @var array $eventos */ /** @var array $indicadores */ /** @var array $camposResumo */ /** @var int $limiteMb */
$tipoRotulo = ServicoFolha::TIPOS[$documento['tipo']] ?? 'Documento';
$acao = url('portal.gestao.documento.acao', ['codigo' => $documento['codigo_publico']]);
$arquivo = url('portal.gestao.documento.arquivo', ['codigo' => $documento['codigo_publico']]);
$editavel = $documento['status'] !== 'revogado';
$rotulos = ['salario_base' => 'Salário-base', 'salario_bruto' => 'Salário bruto', 'horas_extras_qtd' => 'Horas extras (quantidade)', 'horas_extras_valor' => 'Horas extras (R$)',
    'dsr' => 'Reflexo de DSR', 'adicionais' => 'Adicionais', 'outros_vencimentos' => 'Outros vencimentos', 'inss' => 'INSS descontado', 'irrf' => 'IRRF',
    'desconto_vt' => 'Desconto de vale-transporte', 'outros_descontos' => 'Outros descontos', 'salario_liquido' => 'Salário líquido', 'vt_antecipado' => 'Vale-transporte antecipado', 'total_pago' => 'Total pago'];
$fmt = static fn(mixed $v): string => $v === null ? '' : number_format((float)$v, 2, ',', '.');
$chip = ['rascunho' => 'estado-rascunho', 'publicado' => 'estado-disponivel', 'revogado' => 'estado-revogado'][$documento['status']] ?? '';
?>
<p class="texto-apoio"><a href="<?= e(url('portal.gestao')) ?>?aba=documentos">← Documentos</a></p>
<div class="portal-cabeca">
    <h2><?= e($tipoRotulo) ?> · <?= e(ServicoFolha::rotuloCompetencia($documento['competencia'])) ?> <span class="chip-estado <?= e($chip) ?>"><?= e(ServicoFolha::STATUS[$documento['status']]) ?></span></h2>
    <p class="texto-apoio"><?= e($documento['colaboradora_nome']) ?> · versão <?= (int)$documento['versao_numero'] ?> · <?= e((string)$documento['nome_original']) ?> (<?= e(number_format((int)$documento['tamanho'] / 1024, 0, ',', '.')) ?> KB) · SHA-256 <code><?= e(substr((string)$documento['sha256'], 0, 16)) ?>…</code></p>
</div>

<?php if ($documento['status'] === 'rascunho'): ?>
    <div class="alerta alerta-aviso">Rascunho: a colaboradora <strong>não</strong> vê este documento. <?= $documento['tipo'] === 'contracheque' ? 'Confira os valores, marque "conferido" e publique.' : 'Confira a prévia e publique.' ?></div>
<?php elseif ($documento['status'] === 'revogado'): ?>
    <div class="alerta alerta-erro">Revogado em <?= e(data_br($documento['revogado_em'])) ?><?= $documento['motivo_revogacao'] ? ' — ' . e($documento['motivo_revogacao']) : '' ?>. A colaboradora não o acessa mais; o histórico abaixo foi preservado.</div>
<?php endif; ?>

<div class="grade-gestao-doc">
<section class="cartao">
    <h3>Prévia do PDF</h3>
    <div class="visualizador-pdf visualizador-previa"><iframe src="<?= e($arquivo) ?>" title="Prévia do PDF"></iframe></div>
    <div class="acoes-doc"><a class="botao botao-secundario botao-pequeno" href="<?= e($arquivo) ?>" target="_blank" rel="noopener">Abrir em nova aba</a>
        <a class="botao botao-contorno botao-pequeno" href="<?= e($arquivo) ?>&baixar=1">Baixar</a></div>
    <?php if ($documento['status'] === 'rascunho'): ?>
        <form method="post" action="<?= e($acao) ?>" class="form-inline" style="margin-top:.8rem"><?= Csrf::campo() ?><input type="hidden" name="acao" value="publicar">
            <button type="submit" class="botao botao-primario botao-largo">Publicar para a colaboradora</button></form>
        <form method="post" action="<?= e($acao) ?>" class="form-inline" style="margin-top:.5rem" data-confirmar="Excluir este rascunho e o PDF enviado?"><?= Csrf::campo() ?><input type="hidden" name="acao" value="excluir_rascunho">
            <button type="submit" class="botao botao-contorno botao-pequeno">Excluir rascunho</button></form>
    <?php endif; ?>
</section>

<section class="cartao">
    <h3>Dados do documento</h3>
    <form method="post" action="<?= e($acao) ?>" class="formulario"><?= Csrf::campo() ?><input type="hidden" name="acao" value="metadados">
        <div class="linha-campos">
            <div><label for="d_comp">Competência</label><input type="month" id="d_comp" name="competencia" value="<?= e($documento['competencia']) ?>" required <?= $editavel ? '' : 'disabled' ?>></div>
            <div><label for="d_pag">Data do pagamento</label><input type="date" id="d_pag" name="data_pagamento" value="<?= e((string)($documento['data_pagamento'] ?? '')) ?>" <?= $editavel ? '' : 'disabled' ?>></div>
            <?php if ($documento['tipo'] === 'vale_transporte'): ?><div><label for="d_mes">Mês de referência</label><input type="month" id="d_mes" name="mes_referencia" value="<?= e((string)($documento['mes_referencia'] ?? '')) ?>" <?= $editavel ? '' : 'disabled' ?>></div><?php endif; ?>
        </div>
        <label for="d_desc">Descrição</label><input type="text" id="d_desc" name="descricao" maxlength="200" value="<?= e((string)($documento['descricao'] ?? '')) ?>" <?= $editavel ? '' : 'disabled' ?>>
        <?php if ($editavel): ?><button type="submit" class="botao botao-contorno">Salvar dados</button><?php endif; ?>
    </form>
</section>
</div>

<?php if (in_array($documento['tipo'], ['contracheque', 'ferias', 'decimo_terceiro', 'horas_extras'], true)): ?>
<section class="cartao">
    <h3>Resumo financeiro <span class="texto-apoio" style="font-weight:400">(valores conforme o documento oficial — deixe em branco o que não está informado)</span></h3>
    <?php if ($resumo !== null && $resumo['origem'] !== 'manual' && (int)$resumo['conferido'] !== 1): ?>
        <div class="alerta alerta-aviso">Valores lidos automaticamente do PDF como <strong>sugestão</strong>. Compare com o documento antes de marcar como conferido.</div>
    <?php endif; ?>
    <form method="post" action="<?= e($acao) ?>" class="formulario"><?= Csrf::campo() ?><input type="hidden" name="acao" value="resumo">
        <div class="grade-valores">
            <?php foreach ($camposResumo as $campo): ?>
                <div><label for="r_<?= e($campo) ?>"><?= e($rotulos[$campo] ?? $campo) ?></label>
                    <input type="text" inputmode="decimal" id="r_<?= e($campo) ?>" name="<?= e($campo) ?>" placeholder="não informado" value="<?= e($fmt($resumo[$campo] ?? null)) ?>" <?= $editavel ? '' : 'disabled' ?>></div>
            <?php endforeach; ?>
            <div><label for="r_pag">Data do pagamento</label><input type="date" id="r_pag" name="data_pagamento" value="<?= e((string)($resumo['data_pagamento'] ?? $documento['data_pagamento'] ?? '')) ?>" <?= $editavel ? '' : 'disabled' ?>></div>
        </div>
        <label for="r_obs">Observações</label><input type="text" id="r_obs" name="observacoes" maxlength="500" value="<?= e((string)($resumo['observacoes'] ?? '')) ?>" <?= $editavel ? '' : 'disabled' ?>>
        <label class="caixa-selecao"><input type="checkbox" name="conferido" value="1" <?= $resumo !== null && (int)$resumo['conferido'] === 1 ? 'checked' : '' ?> <?= $editavel ? '' : 'disabled' ?>> Conferi estes valores com o documento oficial</label>
        <?php if ($resumo !== null && (int)$resumo['conferido'] === 1): ?><p class="texto-apoio">Conferido em <?= e(data_br($resumo['conferido_em'])) ?>.</p><?php endif; ?>
        <?php if ($editavel): ?><button type="submit" class="botao botao-primario">Salvar valores</button><?php endif; ?>
    </form>
</section>
<?php endif; ?>

<?php if ($editavel): ?>
<div class="grade-gestao-doc">
<section class="cartao">
    <h3>Substituir PDF (nova versão)</h3>
    <p class="texto-apoio">A versão atual vai para o histórico e deixa de ser acessível; a colaboradora passa a ver só a nova. Nada é substituído silenciosamente: fica registrado quem, quando e por quê.</p>
    <form method="post" action="<?= e($acao) ?>" enctype="multipart/form-data" class="formulario" data-confirmar="Substituir o PDF por uma nova versão?"><?= Csrf::campo() ?><input type="hidden" name="acao" value="substituir">
        <label for="s_arq">Novo PDF * <span class="texto-apoio">(até <?= (int)$limiteMb ?> MB)</span></label><input type="file" id="s_arq" name="arquivo" accept="application/pdf,.pdf" required>
        <label for="s_mot">Motivo</label><input type="text" id="s_mot" name="motivo" maxlength="300" placeholder="Ex.: PDF da competência errada">
        <button type="submit" class="botao botao-secundario">Enviar nova versão</button>
    </form>
</section>
<section class="cartao">
    <h3>Revogar documento</h3>
    <p class="texto-apoio">Use quando o documento não deve mais ficar disponível (ex.: enviado para a pessoa errada). O histórico de acessos é preservado.</p>
    <form method="post" action="<?= e($acao) ?>" class="formulario" data-confirmar="Revogar este documento? A colaboradora deixará de acessá-lo."><?= Csrf::campo() ?><input type="hidden" name="acao" value="revogar">
        <label for="rv_mot">Motivo</label><input type="text" id="rv_mot" name="motivo" maxlength="300">
        <button type="submit" class="botao botao-contorno">Revogar</button>
    </form>
</section>
</div>
<?php endif; ?>

<section class="cartao">
    <h3>Versões</h3>
    <div class="tabela-rolavel"><table class="tabela-eventos">
        <thead><tr><th>Versão</th><th>Arquivo</th><th>Enviado por</th><th>Em</th><th>Situação</th><th></th></tr></thead>
        <tbody><?php foreach ($versoes as $v): ?>
            <tr><td>v<?= (int)$v['numero'] ?></td><td><?= e($v['nome_original']) ?> <small class="texto-apoio"><?= e(substr($v['sha256'], 0, 12)) ?>…</small></td>
                <td><?= e($v['enviado_por_nome'] ?? '—') ?></td><td><?= e(data_br($v['criado_em'])) ?></td>
                <td><?= e($v['situacao']) ?><?= $v['substituida_em'] !== null ? ' em ' . e(data_br($v['substituida_em'])) : '' ?><?= $v['motivo'] ? ' · ' . e($v['motivo']) : '' ?></td>
                <td><a href="<?= e($arquivo) ?>?versao=<?= (int)$v['id'] ?>" target="_blank" rel="noopener">ver</a></td></tr>
        <?php endforeach; ?></tbody></table></div>
</section>

<section class="cartao">
    <h3>Histórico de acessos</h3>
    <dl class="resumo-grade">
        <div><dt>Primeiro acesso</dt><dd><?= e(ServicoFolha::horaLocal($indicadores['primeiro'])) ?></dd></div>
        <div><dt>Último acesso</dt><dd><?= e(ServicoFolha::horaLocal($indicadores['ultimo'])) ?></dd></div>
        <div><dt>Aberturas</dt><dd><?= (int)$indicadores['aberturas'] ?></dd></div>
        <div><dt>Downloads</dt><dd><?= (int)$indicadores['downloads'] ?></dd></div>
    </dl>
    <?php $comDocumento = false; require RAIZ_PROJETO . '/app/Views/folha/_eventos.php'; ?>
    <p class="texto-apoio" style="margin:.6rem 0 0">Eventos de abertura e download comprovam a entrega técnica registrada pelo servidor; não comprovam leitura, concordância ou quitação.</p>
</section>
