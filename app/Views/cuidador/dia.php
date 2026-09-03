<?php
/** @var array $crianca */
/** @var array $criancas */
/** @var array $dia */
/** @var array $categorias */
/** @var array $rapidas */
/** @var bool $ehHoje */

$rotuloEstado = [
    'cinza' => 'Mais tarde', 'azul' => 'Agora', 'verde' => 'Feito',
    'ambar' => 'Atrasado', 'vermelho' => 'Não feito',
];
$ontem = date('Y-m-d', strtotime($dia['data'] . ' -1 day'));
$amanha = date('Y-m-d', strtotime($dia['data'] . ' +1 day'));
$hora = (int)date('G');
$saudacao = $hora < 12 ? 'Bom dia' : ($hora < 18 ? 'Boa tarde' : 'Boa noite');
$primeiroNome = explode(' ', (string)\App\Core\Autenticacao::usuario()['nome'])[0];
$nomesGrupos = [
    'alimentacao' => 'Alimentação', 'sono' => 'Sono', 'higiene' => 'Higiene', 'saude' => 'Saúde',
    'desenvolvimento' => 'Desenvolvimento', 'rotina' => 'Rotina', 'comportamento' => 'Comportamento',
    'apoio' => 'Apoio doméstico', 'turno' => 'Turno', 'intercorrencia' => 'Intercorrência',
];
$grupos = array_fill_keys(array_keys($nomesGrupos), []);
foreach ($categorias as $categoria) {
    $grupos[$categoria['grupo']][] = $categoria;
}
$grupos = array_filter($grupos);

$chipRegistro = static function (array $registro): void {
    $estado = $registro['status'] === 'feito' ? 'verde' : ($registro['status'] === 'parcial' ? 'ambar' : 'vermelho');
    ?>
    <a class="chip-registro estado-<?= e($estado) ?>"
       href="<?= e(url('registro.ver', ['codigo' => $registro['codigo_publico']])) ?>">
        <?= selo_categoria((string)$registro['categoria_slug'], (string)$registro['categoria_grupo'], 34, 18) ?>
        <span><?= e(data_br($registro['inicio'], 'H:i')) ?> · <?= e($registro['categoria_nome']) ?></span>
    </a>
    <?php
};
?>
<div class="barra-dia">
    <div class="saudacao">
        <strong><?= e($saudacao) ?>, <?= e($primeiroNome) ?></strong>
        <span><?= $ehHoje ? 'hoje' : e(data_br($dia['data'] . ' 00:00:00', 'd/m/Y')) ?> · janela <?= e($dia['janela']['inicio']) ?>–<?= e($dia['janela']['fim']) ?></span>
    </div>
    <span class="chip-crianca">
        <span class="avatar-crianca"><?= e(mb_strtoupper(mb_substr((string)($crianca['apelido'] ?: $crianca['nome']), 0, 1))) ?></span>
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
            <strong style="font-size:.88rem"><?= e($crianca['apelido'] ?: $crianca['nome']) ?></strong>
        <?php endif; ?>
    </span>
    <span id="indicador-conexao" class="indicador-conexao" data-estado="online">Online</span>
</div>

<div class="navega-dia" style="margin-bottom:.9rem">
    <a class="chip-dia" href="<?= e(url('cuidador.dia.data', ['data' => $ontem])) ?>" aria-label="Dia anterior"><?= icone_ui('seta-esq', 15, 'currentColor', 2.4) ?></a>
    <span class="chip-dia"><?= $ehHoje ? 'Hoje' : e(data_br($dia['data'] . ' 00:00:00', 'd/m')) ?></span>
    <?php if (!$ehHoje): ?>
        <a class="chip-dia" href="<?= e(url('cuidador.dia.data', ['data' => $amanha])) ?>" aria-label="Dia seguinte"><?= icone_ui('seta-dir', 15, 'currentColor', 2.4) ?></a>
        <a class="chip-dia" href="<?= e(url('cuidador.dia')) ?>">Hoje</a>
    <?php endif; ?>
    <span class="texto-apoio" style="margin-left:auto">Última atividade:
        <?= $dia['ultima_atividade'] !== null ? e(data_br($dia['ultima_atividade'], 'H:i')) : 'nenhuma' ?></span>
</div>

<?php if ($dia['modo'] === 'roteiro'): ?>
    <?php if ($dia['linhas'] === []): ?>
        <div class="cartao"><p class="texto-apoio" style="margin:0">Nenhum bloco de roteiro para este dia.
            <a href="<?= e(url('roteiro.editar')) ?>">Os responsáveis montam o roteiro aqui</a>.</p></div>
    <?php endif; ?>
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
                            <?php if (!empty($bloco['instrucao'])): ?>
                                <p class="instrucao"><?= e($bloco['instrucao']) ?></p>
                            <?php endif; ?>
                        </div>
                        <span class="etiqueta-estado etiqueta-<?= e($linha['estado']) ?>"><?= e($rotuloEstado[$linha['estado']]) ?></span>
                    </div>
                    <?php foreach ($linha['registros'] as $registro) { $chipRegistro($registro); } ?>
                    <?php if ($linha['registros'] === [] && !empty($bloco['categoria_slug']) && in_array($linha['estado'], ['azul', 'ambar', 'vermelho', 'cinza'], true)): ?>
                        <div class="acoes-bloco">
                            <a class="botao botao-pequeno botao-primario"
                               href="<?= e(url('registro.criar', ['categoria' => $bloco['categoria_slug']])) ?>?bloco=<?= (int)$bloco['id'] ?>&data=<?= e($dia['data']) ?>&hora=<?= e(substr((string)$bloco['hora_inicio'], 0, 5)) ?>">Registrar</a>
                            <a class="botao botao-pequeno botao-contorno"
                               href="<?= e(url('registro.criar', ['categoria' => $bloco['categoria_slug']])) ?>?bloco=<?= (int)$bloco['id'] ?>&data=<?= e($dia['data']) ?>&hora=<?= e(substr((string)$bloco['hora_inicio'], 0, 5)) ?>&status=parcial">Parcial</a>
                            <a class="botao botao-pequeno botao-contorno"
                               href="<?= e(url('registro.criar', ['categoria' => $bloco['categoria_slug']])) ?>?bloco=<?= (int)$bloco['id'] ?>&data=<?= e($dia['data']) ?>&hora=<?= e(substr((string)$bloco['hora_inicio'], 0, 5)) ?>&status=nao_feito">Não feito</a>
                            <button type="button" class="botao botao-pequeno botao-contorno" data-abrir-folha
                                    data-hora="<?= e(substr((string)$bloco['hora_inicio'], 0, 5)) ?>">Outro registro</button>
                        </div>
                    <?php elseif ($linha['registros'] === []): ?>
                        <div class="acoes-bloco">
                            <button type="button" class="botao botao-pequeno botao-primario" data-abrir-folha
                                    data-hora="<?= e(substr((string)$bloco['hora_inicio'], 0, 5)) ?>">Registrar</button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php if ($dia['avulsos'] !== []): ?>
        <h3>Outros registros do dia</h3>
        <div class="lista-avulsos"><?php foreach ($dia['avulsos'] as $registro) { $chipRegistro($registro); } ?></div>
    <?php endif; ?>
<?php elseif ($dia['modo'] === 'slots'): ?>
    <div class="grade-dia">
        <?php foreach ($dia['linhas'] as $indice => $linha): ?>
            <div class="linha-bloco estado-<?= e($linha['estado']) ?>">
                <div class="coluna-tempo">
                    <span class="hora"><?= e($linha['inicio']) ?></span>
                    <span class="ponto-tempo"></span>
                    <?php if ($indice < count($dia['linhas']) - 1): ?><span class="fio-tempo"></span><?php endif; ?>
                </div>
                <div class="cartao-bloco">
                    <?php if ($linha['registros'] !== []): ?>
                        <?php foreach ($linha['registros'] as $registro) { $chipRegistro($registro); } ?>
                    <?php else: ?>
                        <button type="button" class="botao botao-pequeno botao-contorno" style="align-self:flex-start"
                                data-abrir-folha data-hora="<?= e($linha['inicio']) ?>">+ Registrar às <?= e($linha['inicio']) ?></button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <?php if ($dia['avulsos'] === []): ?>
        <div class="cartao" style="text-align:center; padding:2rem 1rem">
            <?= icone_ui('estrela', 34, '#D9C58A') ?>
            <p class="texto-apoio">Nenhum registro ainda. Toque no botão <strong>+</strong> ou use as ações rápidas.</p>
        </div>
    <?php endif; ?>
    <div class="grade-dia">
        <?php foreach ($dia['avulsos'] as $indice => $registro): ?>
            <div class="linha-bloco estado-<?= e($registro['status'] === 'feito' ? 'verde' : ($registro['status'] === 'parcial' ? 'ambar' : 'vermelho')) ?>">
                <div class="coluna-tempo">
                    <span class="hora"><?= e(data_br($registro['inicio'], 'H:i')) ?></span>
                    <span class="ponto-tempo"></span>
                    <?php if ($indice < count($dia['avulsos']) - 1): ?><span class="fio-tempo"></span><?php endif; ?>
                </div>
                <div class="cartao-bloco">
                    <?php $chipRegistro($registro); ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if ($rapidas !== []): ?>
    <nav class="acoes-rapidas" aria-label="Ações rápidas">
        <?php foreach ($rapidas as $categoria): ?>
            <a class="acao-rapida" href="<?= e(url('registro.criar', ['categoria' => $categoria['slug']])) ?>?data=<?= e($dia['data']) ?>">
                <span class="acao-icone"><?= icone_categoria((string)$categoria['slug'], (string)$categoria['grupo'], 22) ?></span>
                <span><?= e($categoria['nome']) ?></span>
            </a>
        <?php endforeach; ?>
    </nav>
<?php endif; ?>

<!-- Botão flutuante: abre a janela de registro -->
<button type="button" class="fab-registrar" data-abrir-folha data-hora="<?= e(date('H:i')) ?>" aria-label="Registrar agora">
    <?= icone_ui('mais', 26, '#FFFFFF', 2.8) ?>
</button>

<!-- Janela de registro por horário (folha inferior no celular) -->
<div id="folha-registro" hidden>
    <button type="button" class="folha-fundo" data-fechar-folha aria-label="Fechar"></button>
    <div class="folha" role="dialog" aria-modal="true" aria-labelledby="folha-titulo">
        <div class="folha-alca"></div>
        <div class="folha-cabeca">
            <div>
                <span class="titulo" id="folha-titulo">Registrar às <span data-folha-hora><?= e(date('H:i')) ?></span></span>
                <span class="texto-apoio"><?= e($crianca['apelido'] ?: $crianca['nome']) ?> · toque no que aconteceu</span>
            </div>
            <button type="button" class="folha-fechar" data-fechar-folha aria-label="Fechar">
                <?= icone_ui('x', 16, 'currentColor', 2.4) ?>
            </button>
        </div>
        <label class="caixa-selecao alternar-multi">
            <input type="checkbox" data-modo-multi>
            <span>Aconteceu mais de uma coisa? <strong>Selecionar várias</strong></span>
        </label>
        <?php foreach ($grupos as $grupo => $lista): ?>
            <p class="grupo-folha"><?= e($nomesGrupos[$grupo] ?? $grupo) ?></p>
            <div class="grade-opcoes-registro">
                <?php foreach ($lista as $categoria): ?>
                    <a class="opcao-registro" data-link-registro data-slug="<?= e($categoria['slug']) ?>"
                       data-base="<?= e(url('registro.criar', ['categoria' => $categoria['slug']])) ?>"
                       href="<?= e(url('registro.criar', ['categoria' => $categoria['slug']])) ?>?data=<?= e($dia['data']) ?>">
                        <?= selo_categoria((string)$categoria['slug'], (string)$categoria['grupo'], 44, 22) ?>
                        <span><?= e($categoria['nome']) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
        <p class="nota-folha">Sem internet? O registro fica guardado no aparelho e é enviado depois.</p>
        <div class="barra-multi" data-barra-multi hidden>
            <a class="botao botao-primario botao-largo" data-registrar-varios
               data-base="<?= e(url('registro.varios')) ?>" href="<?= e(url('registro.varios')) ?>">
                Registrar <span data-contagem-multi>0</span> atividades juntas</a>
        </div>
    </div>
</div>
<span hidden data-dia-atual="<?= e($dia['data']) ?>"></span>

<script src="<?= e(asset('js/grade.js')) ?>" defer></script>
<script src="<?= e(asset('js/modal.js')) ?>" defer></script>
