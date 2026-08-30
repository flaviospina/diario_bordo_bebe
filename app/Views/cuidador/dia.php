<?php

use App\Core\Csrf;

/** @var array $crianca */
/** @var array $criancas */
/** @var array $dia */
/** @var array $categorias */
/** @var array $rapidas */
/** @var bool $ehHoje */

$rotuloEstado = [
    'cinza' => 'Futuro', 'azul' => 'Em andamento', 'verde' => 'Feito',
    'ambar' => 'Atrasado', 'vermelho' => 'Não feito',
];
$ontem = date('Y-m-d', strtotime($dia['data'] . ' -1 day'));
$amanha = date('Y-m-d', strtotime($dia['data'] . ' +1 day'));
$grupos = [];
foreach ($categorias as $categoria) {
    $grupos[$categoria['grupo']][] = $categoria;
}

$cartaoRegistro = static function (array $registro): void {
    ?>
    <a class="chip-registro estado-<?= e($registro['status'] === 'feito' ? 'verde' : ($registro['status'] === 'parcial' ? 'ambar' : 'vermelho')) ?>"
       href="<?= e(url('registro.ver', ['codigo' => $registro['codigo_publico']])) ?>">
        <span><?= e($registro['categoria_icone']) ?></span>
        <span><?= e(data_br($registro['inicio'], 'H:i')) ?> · <?= e($registro['categoria_nome']) ?></span>
    </a>
    <?php
};
?>
<div class="barra-dia">
    <div class="navega-dia">
        <a class="botao botao-pequeno botao-contorno" href="<?= e(url('cuidador.dia.data', ['data' => $ontem])) ?>">←</a>
        <strong><?= $ehHoje ? 'Hoje' : e(data_br($dia['data'] . ' 00:00:00', 'd/m/Y')) ?></strong>
        <?php if (!$ehHoje): ?>
            <a class="botao botao-pequeno botao-contorno" href="<?= e(url('cuidador.dia.data', ['data' => $amanha])) ?>">→</a>
            <a class="botao botao-pequeno botao-contorno" href="<?= e(url('cuidador.dia')) ?>">Hoje</a>
        <?php endif; ?>
    </div>
    <?php if (count($criancas) > 1): ?>
        <form method="get" action="<?= e($ehHoje ? url('cuidador.dia') : url('cuidador.dia.data', ['data' => $dia['data']])) ?>" class="form-inline">
            <select name="crianca" onchange="this.form.submit()" aria-label="Criança">
                <?php foreach ($criancas as $opcao): ?>
                    <option value="<?= e($opcao['slug']) ?>" <?= $opcao['slug'] === $crianca['slug'] ? 'selected' : '' ?>>
                        <?= e($opcao['apelido'] ?: $opcao['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    <?php else: ?>
        <strong class="texto-apoio"><?= e($crianca['apelido'] ?: $crianca['nome']) ?></strong>
    <?php endif; ?>
    <span id="indicador-conexao" class="indicador-conexao" data-estado="online">Online</span>
</div>

<p class="texto-apoio">Janela do dia: <?= e($dia['janela']['inicio']) ?>–<?= e($dia['janela']['fim']) ?> ·
    Última atividade: <?= $dia['ultima_atividade'] !== null ? e(data_br($dia['ultima_atividade'], 'H:i')) : 'nenhuma hoje' ?></p>

<?php if ($dia['modo'] === 'roteiro'): ?>
    <?php if ($dia['linhas'] === []): ?>
        <div class="cartao"><p>Nenhum bloco de roteiro para este dia.
            <a href="<?= e(url('roteiro.editar')) ?>">Os responsáveis podem montar o roteiro aqui</a>.</p></div>
    <?php endif; ?>
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
                    <?php if (!empty($bloco['instrucao'])): ?>
                        <p class="texto-apoio instrucao"><?= e($bloco['instrucao']) ?></p>
                    <?php endif; ?>
                    <?php foreach ($linha['registros'] as $registro) { $cartaoRegistro($registro); } ?>
                    <?php if ($linha['registros'] === [] && !empty($bloco['categoria_slug'])): ?>
                        <div class="acoes-bloco">
                            <a class="botao botao-pequeno botao-primario"
                               href="<?= e(url('registro.criar', ['categoria' => $bloco['categoria_slug']])) ?>?bloco=<?= (int)$bloco['id'] ?>&data=<?= e($dia['data']) ?>">Feito</a>
                            <a class="botao botao-pequeno botao-contorno"
                               href="<?= e(url('registro.criar', ['categoria' => $bloco['categoria_slug']])) ?>?bloco=<?= (int)$bloco['id'] ?>&data=<?= e($dia['data']) ?>&status=parcial">Parcial</a>
                            <a class="botao botao-pequeno botao-contorno"
                               href="<?= e(url('registro.criar', ['categoria' => $bloco['categoria_slug']])) ?>?bloco=<?= (int)$bloco['id'] ?>&data=<?= e($dia['data']) ?>&status=nao_feito">Não feito</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php if ($dia['avulsos'] !== []): ?>
        <h3>Outros registros do dia</h3>
        <div class="lista-avulsos"><?php foreach ($dia['avulsos'] as $registro) { $cartaoRegistro($registro); } ?></div>
    <?php endif; ?>
<?php elseif ($dia['modo'] === 'slots'): ?>
    <div class="grade-dia">
        <?php foreach ($dia['linhas'] as $linha): ?>
            <div class="linha-bloco estado-<?= e($linha['estado']) ?>">
                <div class="bloco-horario"><?= e($linha['inicio']) ?></div>
                <div class="bloco-conteudo">
                    <?php foreach ($linha['registros'] as $registro) { $cartaoRegistro($registro); } ?>
                    <?php if ($linha['registros'] === []): ?>
                        <span class="texto-apoio">—</span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="lista-avulsos">
        <?php if ($dia['avulsos'] === []): ?>
            <div class="cartao"><p class="texto-apoio">Nenhum registro ainda. Use as ações rápidas abaixo ou o botão "+ Registrar".</p></div>
        <?php endif; ?>
        <?php foreach ($dia['avulsos'] as $registro) { $cartaoRegistro($registro); } ?>
    </div>
<?php endif; ?>

<details class="cartao seletor-categorias">
    <summary class="botao botao-primario botao-largo">+ Registrar</summary>
    <?php foreach ($grupos as $grupo => $lista): ?>
        <div class="grupo-registro">
            <?php foreach ($lista as $categoria): ?>
                <a class="botao botao-contorno botao-categoria"
                   href="<?= e(url('registro.criar', ['categoria' => $categoria['slug']])) ?>?data=<?= e($dia['data']) ?>">
                    <?= e($categoria['icone']) ?> <?= e($categoria['nome']) ?>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>
</details>

<?php if ($rapidas !== []): ?>
    <nav class="acoes-rapidas" aria-label="Ações rápidas">
        <?php foreach ($rapidas as $categoria): ?>
            <a class="acao-rapida" href="<?= e(url('registro.criar', ['categoria' => $categoria['slug']])) ?>?data=<?= e($dia['data']) ?>">
                <span class="acao-icone"><?= e($categoria['icone']) ?></span>
                <span><?= e($categoria['nome']) ?></span>
            </a>
        <?php endforeach; ?>
    </nav>
<?php endif; ?>

<script src="<?= e(asset('js/grade.js')) ?>" defer></script>
