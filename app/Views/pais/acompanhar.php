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
    'cinza' => 'Mais tarde', 'azul' => 'Agora', 'verde' => 'Feito',
    'ambar' => 'Atrasado', 'vermelho' => 'Não feito',
];
$ontem = date('Y-m-d', strtotime($dia['data'] . ' -1 day'));
$amanha = date('Y-m-d', strtotime($dia['data'] . ' +1 day'));
$estatisticas = $dia['estatisticas'];
$nomeCurto = (string)($crianca['apelido'] ?: $crianca['nome']);

$chipRegistro = static function (array $registro): void {
    $estado = $registro['status'] === 'feito' ? 'verde' : ($registro['status'] === 'parcial' ? 'ambar' : 'vermelho');
    ?>
    <a class="chip-registro estado-<?= e($estado) ?>"
       href="<?= e(url('registro.ver', ['codigo' => $registro['codigo_publico']])) ?>">
        <?= selo_categoria((string)$registro['categoria_slug'], (string)$registro['categoria_grupo'], 34, 18) ?>
        <span><?= e(data_br($registro['inicio'], 'H:i')) ?> · <?= e($registro['categoria_nome']) ?>
            <small class="texto-apoio">(<?= e($registro['usuario_nome']) ?>)</small></span>
    </a>
    <?php
};
?>
<div class="barra-dia" data-acompanhar data-data="<?= e($dia['data']) ?>" data-crianca="<?= e($crianca['slug']) ?>"
     data-versao="<?= e($dia['versao']) ?>">
    <div class="saudacao">
        <strong>O dia de <?= e($nomeCurto) ?></strong>
        <span><?= $ehHoje ? 'hoje' : e(data_br($dia['data'] . ' 00:00:00', 'd/m/Y')) ?> · atualiza sozinho</span>
    </div>
    <span class="chip-crianca">
        <span class="avatar-crianca"><?= e(mb_strtoupper(mb_substr($nomeCurto, 0, 1))) ?></span>
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
        <?php else: ?>
            <strong style="font-size:.88rem"><?= e($nomeCurto) ?></strong>
        <?php endif; ?>
    </span>
</div>

<div class="navega-dia" style="margin-bottom:.8rem">
    <a class="chip-dia" href="<?= e(url('pais.acompanhar.data', ['data' => $ontem])) ?>" aria-label="Dia anterior"><?= icone_ui('seta-esq', 15, 'currentColor', 2.4) ?></a>
    <span class="chip-dia"><?= $ehHoje ? 'Hoje' : e(data_br($dia['data'] . ' 00:00:00', 'd/m')) ?></span>
    <?php if (!$ehHoje): ?>
        <a class="chip-dia" href="<?= e(url('pais.acompanhar.data', ['data' => $amanha])) ?>" aria-label="Dia seguinte"><?= icone_ui('seta-dir', 15, 'currentColor', 2.4) ?></a>
        <a class="chip-dia" href="<?= e(url('pais.acompanhar')) ?>">Hoje</a>
    <?php endif; ?>
</div>

<?php if ($ehHoje): ?>
    <div class="faixa-status">
        <span class="semaforo semaforo-<?= e($semaforo) ?>">
            <?= $silencioMinutos !== null
                ? 'Última atividade há ' . (int)$silencioMinutos . ' min'
                : 'Fora da janela do dia' ?><?= !$alertaAtivo ? ' · alerta desativado' : '' ?>
        </span>
    </div>
<?php endif; ?>

<div class="tiles-dia">
    <div class="tile-dia">
        <?= icone_ui('mamadeira', 20, '#3E6A64') ?>
        <span class="tile-numero"><?= (int)$estatisticas['mamadas'] ?></span>
        <span class="tile-rotulo">mamadas</span>
    </div>
    <div class="tile-dia">
        <?= icone_ui('lua', 20, '#5F58A0') ?>
        <span class="tile-numero"><?= (int)$estatisticas['sonecas'] ?></span>
        <span class="tile-rotulo">sonecas</span>
    </div>
    <div class="tile-dia">
        <?= icone_ui('fralda', 20, '#37795B') ?>
        <span class="tile-numero"><?= (int)$estatisticas['fraldas'] ?></span>
        <span class="tile-rotulo">fraldas</span>
    </div>
</div>

<?php foreach ($intercorrencias as $intercorrencia): ?>
    <a class="cartao-pendencia <?= $intercorrencia['ciencia_em'] === null ? 'pendencia-grave' : '' ?>"
       href="<?= e(url('intercorrencia.ver', ['codigo' => $intercorrencia['codigo_publico']])) ?>">
        <span class="selo-categoria" style="width:42px;height:42px;background:#F9E3DF"><?= icone_ui('alerta', 21, '#A5473A') ?></span>
        <div style="min-width:0">
            <div class="titulo"><?= e(ucfirst((string)$intercorrencia['gravidade'])) ?> às <?= e(data_br($intercorrencia['ocorrido_em'], 'H:i')) ?>
                <?= $intercorrencia['ciencia_em'] === null ? '— dar ciência' : '· ciência registrada' ?></div>
            <div class="detalhe"><?= e(mb_substr((string)$intercorrencia['descricao'], 0, 90)) ?></div>
        </div>
        <span style="margin-left:auto; flex-shrink:0"><?= icone_ui('seta-dir', 17, '#A5473A', 2.2) ?></span>
    </a>
<?php endforeach; ?>

<?php if ($solicitacoesPendentes > 0): ?>
    <a class="cartao-pendencia" href="<?= e(url('solicitacoes.lista')) ?>">
        <span class="selo-categoria" style="width:42px;height:42px;background:#E3EDF8"><?= icone_ui('balao', 21, '#3D6CA3') ?></span>
        <div>
            <div class="titulo"><?= (int)$solicitacoesPendentes ?> solicitação(ões) de alteração</div>
            <div class="detalhe">Aguardando a sua aprovação</div>
        </div>
        <span class="selo-contagem"><?= (int)$solicitacoesPendentes ?></span>
    </a>
<?php endif; ?>

<h3 style="display:flex; align-items:center; gap:.5rem">Linha do tempo
    <svg width="56" height="12" viewBox="0 0 60 12" fill="none" aria-hidden="true"><path d="M2 7 q 5 -6 10 0 t 10 0 t 10 0 t 10 0 t 10 0" stroke="#EDE6DB" stroke-width="2" stroke-linecap="round"></path></svg>
</h3>

<?php if ($dia['modo'] === 'roteiro'): ?>
    <div class="grade-dia">
        <?php foreach ($dia['linhas'] as $indice => $linha): $bloco = $linha['bloco']; ?>
            <div class="linha-bloco estado-<?= e($linha['estado']) ?>">
                <div class="coluna-tempo">
                    <span class="hora"><?= e(substr((string)$bloco['hora_inicio'], 0, 5)) ?></span>
                    <span class="ponto-tempo"></span>
                    <?php if ($indice < count($dia['linhas']) - 1): ?><span class="fio-tempo"></span><?php endif; ?>
                </div>
                <div class="cartao-bloco">
                    <div class="cabeca">
                        <?php if (!empty($bloco['categoria_slug'])): ?>
                            <?= selo_categoria((string)$bloco['categoria_slug'], (string)($bloco['categoria_grupo'] ?? ''), 42, 21) ?>
                        <?php endif; ?>
                        <div style="min-width:0">
                            <div class="titulo-bloco"><?= e($bloco['titulo']) ?></div>
                        </div>
                        <span class="etiqueta-estado etiqueta-<?= e($linha['estado']) ?>"><?= e($rotuloEstado[$linha['estado']]) ?></span>
                    </div>
                    <?php foreach ($linha['registros'] as $registro) { $chipRegistro($registro); } ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php if ($dia['avulsos'] !== []): ?>
        <div class="lista-avulsos"><?php foreach ($dia['avulsos'] as $registro) { $chipRegistro($registro); } ?></div>
    <?php endif; ?>
<?php elseif ($dia['modo'] === 'slots'): ?>
    <div class="grade-dia">
        <?php foreach ($dia['linhas'] as $indice => $linha): ?>
            <?php if ($linha['registros'] === []) { continue; } ?>
            <div class="linha-bloco estado-<?= e($linha['estado']) ?>">
                <div class="coluna-tempo">
                    <span class="hora"><?= e($linha['inicio']) ?></span>
                    <span class="ponto-tempo"></span>
                </div>
                <div class="cartao-bloco">
                    <?php foreach ($linha['registros'] as $registro) { $chipRegistro($registro); } ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="lista-avulsos">
        <?php if ($dia['avulsos'] === []): ?>
            <div class="cartao" style="width:100%"><p class="texto-apoio" style="margin:0">Nenhum registro neste dia (ainda).</p></div>
        <?php endif; ?>
        <?php foreach ($dia['avulsos'] as $registro) { $chipRegistro($registro); } ?>
    </div>
<?php endif; ?>

<p class="texto-apoio">
    <a href="<?= e(url('crianca.timeline', ['slug' => $crianca['slug']])) ?>">Linha do tempo completa</a> ·
    <a href="<?= e(url('crianca.ver', ['slug' => $crianca['slug']])) ?>">Dados de <?= e($nomeCurto) ?></a> ·
    <a href="<?= e(url('relatorios.resumo', ['data' => $dia['data']])) ?>">Resumo do dia</a>
</p>

<script src="<?= e(asset('js/acompanhar.js')) ?>" defer></script>
