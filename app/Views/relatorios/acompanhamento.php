<?php

use App\Core\Csrf;

/** @var array $crianca */
/** @var array $criancas */
/** @var array $analises análises com 'observacoes' */
/** @var bool $iaConfigurada */

$nomeCrianca = $crianca['apelido'] ?: $crianca['nome'];
$tipoObservacao = [
    'padrao' => ['rotulo' => 'Padrão da rotina', 'icone' => 'grafico'],
    'preventivo' => ['rotulo' => 'Vale conversar com o pediatra', 'icone' => 'alerta'],
    'fase' => ['rotulo' => 'Fase e desenvolvimento', 'icone' => 'desenvolvimento'],
    'celebracao' => ['rotulo' => 'Para comemorar', 'icone' => 'estrela'],
];
?>
<h2>Acompanhamento — <?= e($nomeCrianca) ?></h2>

<div class="barra-dia">
    <div class="navega-dia">
        <a class="botao botao-pequeno botao-contorno" href="<?= e(url('relatorios.index')) ?>">
            <?= icone_ui('seta-esq', 15, 'currentColor', 2.2) ?> Relatórios</a>
    </div>
    <?php if (count($criancas) > 1): ?>
        <form method="get" action="<?= e(url('relatorios.acompanhamento')) ?>" class="form-inline">
            <select name="crianca" onchange="this.form.submit()" aria-label="Criança">
                <?php foreach ($criancas as $opcao): ?>
                    <option value="<?= e($opcao['slug']) ?>" <?= $opcao['slug'] === $crianca['slug'] ? 'selected' : '' ?>>
                        <?= e($opcao['apelido'] ?: $opcao['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    <?php endif; ?>
</div>

<div class="cartao cartao-acompanhamento-intro">
    <h3><?= icone_ui('coracao-pulso', 18, '#3E6A64') ?> Como funciona</h3>
    <p class="texto-apoio" style="margin-top:0">
        A cada semana (ou quando você pedir), a inteligência artificial analisa os últimos
        <?= \App\Services\ServicoAcompanhamento::PERIODO_DIAS ?> dias do diário — mamadas, sono,
        cólicas, fraldas, medicações e marcos — e devolve observações sobre padrões e tendências.
        Só idade e sexo são enviados para a análise: nunca o nome, fotos ou textos da família.
    </p>
    <form method="post" action="<?= e(url('relatorios.acompanhamento.gerar')) ?>">
        <?= Csrf::campo() ?>
        <input type="hidden" name="crianca" value="<?= e($crianca['slug']) ?>">
        <button type="submit" class="botao botao-primario botao-largo">
            <?= icone_ui('estrela', 18, 'currentColor', 2.2) ?> Gerar a análise de agora
        </button>
    </form>
    <?php if (!$iaConfigurada): ?>
        <p class="texto-apoio aviso-ia-pendente">A chave da IA ainda não está configurada no servidor
            (ANTHROPIC_API_KEY no .env)<?= \App\Core\Ambiente::ehDesenvolvimento()
                ? ' — em desenvolvimento, a análise sai no modo simulado' : '' ?>.</p>
    <?php endif; ?>
</div>

<?php if ($analises === []): ?>
    <div class="cartao">
        <p class="texto-apoio" style="margin:0">Ainda não há análises. Toque em
            "Gerar a análise de agora" — quanto mais dias registrados, melhor a leitura.</p>
    </div>
<?php endif; ?>

<?php foreach ($analises as $analise): ?>
    <div class="cartao cartao-analise">
        <h3><?= icone_ui('relogio', 16, '#3E6A64') ?>
            Análise de <?= e(data_br((string)$analise['gerado_em'], 'd/m/Y')) ?></h3>
        <p class="texto-apoio" style="margin-top:0">
            Período de <?= e(data_br($analise['periodo_de'] . ' 0:0', 'd/m')) ?>
            a <?= e(data_br($analise['periodo_ate'] . ' 0:0', 'd/m/Y')) ?> ·
            <?= $analise['origem'] === 'automatica' ? 'gerada automaticamente' : 'gerada a pedido' ?>
        </p>
        <?php foreach ($analise['observacoes'] as $observacao): ?>
            <?php $tipo = $tipoObservacao[$observacao['tipo']] ?? $tipoObservacao['padrao']; ?>
            <div class="observacao-ia observacao-<?= e($observacao['tipo']) ?>">
                <span class="selo-categoria selo-observacao">
                    <?= icone_ui($tipo['icone'], 17, 'currentColor', 2.0) ?>
                </span>
                <div class="observacao-texto">
                    <span class="observacao-tipo"><?= e($tipo['rotulo']) ?></span>
                    <strong><?= e($observacao['titulo']) ?></strong>
                    <p><?= e($observacao['texto']) ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endforeach; ?>

<p class="texto-apoio disclaimer-ia">
    As observações acima são geradas por inteligência artificial a partir dos registros do
    diário. Elas apontam padrões e tendências — <strong>não são diagnóstico nem orientação
    médica</strong> e não substituem a avaliação do pediatra. Em qualquer dúvida ou sinal de
    alerta, procure o pediatra ou um serviço de saúde.
</p>
