<?php
/** @var array $crianca */
/** @var array $criancas */
/** @var array $dia */
/** @var bool $ehHoje */
/** @var string $semaforo */
/** @var ?int $silencioMinutos */
/** @var bool $alertaAtivo */
/** @var int $solicitacoesPendentes */
/** @var int $intercorrenciasSemCiencia */
/** @var array $intercorrencias */

$rotuloEstado = [
    'cinza' => 'Futuro', 'azul' => 'Em andamento', 'verde' => 'Feito',
    'ambar' => 'Atrasado', 'vermelho' => 'Não feito',
];
$ontem = date('Y-m-d', strtotime($dia['data'] . ' -1 day'));
$amanha = date('Y-m-d', strtotime($dia['data'] . ' +1 day'));
$estatisticas = $dia['estatisticas'];

$chip = static function (array $registro): void {
    ?>
    <a class="chip-registro estado-<?= e($registro['status'] === 'feito' ? 'verde' : ($registro['status'] === 'parcial' ? 'ambar' : 'vermelho')) ?>"
       href="<?= e(url('registro.ver', ['codigo' => $registro['codigo_publico']])) ?>">
        <span><?= e($registro['categoria_icone']) ?></span>
        <span><?= e(data_br($registro['inicio'], 'H:i')) ?> · <?= e($registro['categoria_nome']) ?>
            <small class="texto-apoio">(<?= e($registro['usuario_nome']) ?>)</small></span>
    </a>
    <?php
};
?>
<div class="barra-dia" data-acompanhar data-data="<?= e($dia['data']) ?>" data-crianca="<?= e($crianca['slug']) ?>"
     data-versao="<?= e($dia['versao']) ?>">
    <div class="navega-dia">
        <a class="botao botao-pequeno botao-contorno" href="<?= e(url('pais.acompanhar.data', ['data' => $ontem])) ?>">←</a>
        <strong><?= $ehHoje ? 'Hoje' : e(data_br($dia['data'] . ' 00:00:00', 'd/m/Y')) ?></strong>
        <?php if (!$ehHoje): ?>
            <a class="botao botao-pequeno botao-contorno" href="<?= e(url('pais.acompanhar.data', ['data' => $amanha])) ?>">→</a>
            <a class="botao botao-pequeno botao-contorno" href="<?= e(url('pais.acompanhar')) ?>">Hoje</a>
        <?php endif; ?>
    </div>
    <?php if (count($criancas) > 1): ?>
        <form method="get" action="<?= e($ehHoje ? url('pais.acompanhar') : url('pais.acompanhar.data', ['data' => $dia['data']])) ?>" class="form-inline">
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

<div class="faixa-status">
    <?php if ($ehHoje): ?>
        <span class="semaforo semaforo-<?= e($semaforo) ?>" title="Semáforo de omissão">
            <?= $silencioMinutos !== null
                ? 'Última atividade há ' . (int)$silencioMinutos . ' min'
                : 'Fora da janela do dia' ?>
            <?= !$alertaAtivo ? ' (alerta desativado)' : '' ?>
        </span>
    <?php endif; ?>
    <span class="pilula">🍼 <?= (int)$estatisticas['mamadas'] ?> mamadas</span>
    <span class="pilula">😴 <?= (int)$estatisticas['sonecas'] ?> sonecas</span>
    <span class="pilula">🧷 <?= (int)$estatisticas['fraldas'] ?> fraldas</span>
    <?php if ($solicitacoesPendentes > 0): ?>
        <a class="pilula pilula-alerta" href="<?= e(url('solicitacoes.lista')) ?>">
            ✋ <?= (int)$solicitacoesPendentes ?> solicitação(ões)</a>
    <?php endif; ?>
    <?php if ($intercorrenciasSemCiencia > 0): ?>
        <span class="pilula pilula-erro">⚠️ <?= (int)$intercorrenciasSemCiencia ?> intercorrência(s) sem ciência</span>
    <?php endif; ?>
</div>

<?php foreach ($intercorrencias as $intercorrencia): ?>
    <a class="alerta <?= $intercorrencia['ciencia_em'] === null ? 'alerta-erro' : 'alerta-aviso' ?>"
       style="display:block; text-decoration:none"
       href="<?= e(url('intercorrencia.ver', ['codigo' => $intercorrencia['codigo_publico']])) ?>">
        ⚠️ <?= e(ucfirst((string)$intercorrencia['gravidade'])) ?> —
        <?= e(mb_substr((string)$intercorrencia['descricao'], 0, 90)) ?>
        (<?= e(data_br($intercorrencia['ocorrido_em'], 'H:i')) ?>)
        <?= $intercorrencia['ciencia_em'] === null ? '· Toque para dar ciência' : '· Ciência registrada' ?>
    </a>
<?php endforeach; ?>

<?php if ($dia['modo'] === 'roteiro'): ?>
    <div class="grade-dia">
        <?php foreach ($dia['linhas'] as $linha): $bloco = $linha['bloco']; ?>
            <div class="linha-bloco estado-<?= e($linha['estado']) ?>">
                <div class="bloco-horario">
                    <?= e(substr((string)$bloco['hora_inicio'], 0, 5)) ?><br>
                    <span class="texto-apoio"><?= e(substr((string)$bloco['hora_fim'], 0, 5)) ?></span>
                </div>
                <div class="bloco-conteudo">
                    <strong><?= e($bloco['categoria_icone'] ?? '📌') ?> <?= e($bloco['titulo']) ?></strong>
                    <span class="etiqueta-estado"><?= e($rotuloEstado[$linha['estado']]) ?></span>
                    <?php foreach ($linha['registros'] as $registro) { $chip($registro); } ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php if ($dia['avulsos'] !== []): ?>
        <h3>Outros registros</h3>
        <div class="lista-avulsos"><?php foreach ($dia['avulsos'] as $registro) { $chip($registro); } ?></div>
    <?php endif; ?>
<?php elseif ($dia['modo'] === 'slots'): ?>
    <div class="grade-dia">
        <?php foreach ($dia['linhas'] as $linha): ?>
            <div class="linha-bloco estado-<?= e($linha['estado']) ?>">
                <div class="bloco-horario"><?= e($linha['inicio']) ?></div>
                <div class="bloco-conteudo">
                    <?php foreach ($linha['registros'] as $registro) { $chip($registro); } ?>
                    <?php if ($linha['registros'] === []): ?><span class="texto-apoio">—</span><?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="lista-avulsos">
        <?php if ($dia['avulsos'] === []): ?>
            <div class="cartao"><p class="texto-apoio">Nenhum registro neste dia (ainda).</p></div>
        <?php endif; ?>
        <?php foreach ($dia['avulsos'] as $registro) { $chip($registro); } ?>
    </div>
<?php endif; ?>

<p class="texto-apoio">
    <a href="<?= e(url('crianca.timeline', ['slug' => $crianca['slug']])) ?>">Linha do tempo completa</a> ·
    <a href="<?= e(url('crianca.ver', ['slug' => $crianca['slug']])) ?>">Dados de <?= e($crianca['apelido'] ?: $crianca['nome']) ?></a>
    · Atualiza sozinha a cada 60 s.
</p>

<script src="<?= e(asset('js/acompanhar.js')) ?>" defer></script>
