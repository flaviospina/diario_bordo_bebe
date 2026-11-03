<?php

use App\Core\Csrf;

/** @var array $crianca */
/** @var array $criancas */
/** @var array $semanas */
/** @var ?array $semana */
/** @var array $dias */
/** @var array $itens */
/** @var array $progresso */
/** @var array $modelo */
/** @var bool $modeloCarregado */
/** @var bool $podeEditar */
/** @var bool $podeMarcar */

$nomeCrianca = $crianca['apelido'] ?: $crianca['nome'];
$iconeCategoria = ['Frutas' => '🍌', 'Proteínas' => '🍗', 'Cereais e tubérculos' => '🌾', 'Leguminosas' => '🫘',
    'Legumes e verduras' => '🥦', 'Temperos e preparo' => '🌿', 'Outros' => '🧺'];
$diasSemana = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
$urlBase = url('compras') . '?crianca=' . rawurlencode((string)$crianca['slug']);
?>
<h2>Compras semanais — <?= e($nomeCrianca) ?></h2>
<p class="texto-apoio nao-imprimir" style="margin-top:0">Cardápio da semana montado pelos pais + lista do que comprar.
    No mercado, toque no quadrado ao colocar cada item no carrinho — vale para toda a família, em qualquer aparelho.
    <a href="<?= e(url('suprimentos.index')) ?>">Pedidos avulsos (fralda, fórmula…) ficam em Itens →</a></p>

<div class="barra-dia nao-imprimir">
    <div class="navega-dia">
        <?php if ($semanas !== []): ?>
            <form method="get" action="<?= e(url('compras')) ?>" class="form-inline">
                <input type="hidden" name="crianca" value="<?= e($crianca['slug']) ?>">
                <select name="semana" data-enviar-ao-mudar aria-label="Semana">
                    <?php foreach ($semanas as $opcao): ?>
                        <option value="<?= (int)$opcao['id'] ?>" <?= $semana !== null && (int)$opcao['id'] === (int)$semana['id'] ? 'selected' : '' ?>>
                            <?= e(data_br($opcao['inicio'] . ' 00:00:00', 'd/m')) ?>–<?= e(data_br($opcao['fim'] . ' 00:00:00', 'd/m/Y')) ?>
                            · <?= (int)$opcao['comprados'] ?>/<?= (int)$opcao['total_itens'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        <?php endif; ?>
        <?php if ($semana !== null): ?>
            <button type="button" class="botao botao-pequeno botao-contorno" data-imprimir>🖨 Imprimir</button>
        <?php endif; ?>
    </div>
    <?php if (count($criancas) > 1): ?>
        <form method="get" action="<?= e(url('compras')) ?>" class="form-inline">
            <select name="crianca" data-enviar-ao-mudar aria-label="Criança">
                <?php foreach ($criancas as $opcao): ?>
                    <option value="<?= e($opcao['slug']) ?>" <?= $opcao['slug'] === $crianca['slug'] ? 'selected' : '' ?>><?= e($opcao['apelido'] ?: $opcao['nome']) ?></option>
                <?php endforeach; ?>
            </select>
        </form>
    <?php endif; ?>
</div>

<?php if ($podeEditar): ?>
    <div class="cartao nao-imprimir">
        <h3><?= icone_ui('mais', 18, '#3E6A64', 2.4) ?> Semanas</h3>
        <?php if (!$modeloCarregado): ?>
            <p class="texto-apoio" style="margin-top:0">A <strong>semana-modelo</strong> (<?= e($modelo['titulo']) ?>,
                <?= e(data_br($modelo['inicio'] . ' 00:00:00', 'd/m')) ?> a <?= e(data_br($modelo['fim'] . ' 00:00:00', 'd/m/Y')) ?>)
                traz <?= count($modelo['itens']) ?> itens em <?= count($modelo['categorias']) ?> categorias e o cardápio dos 7 dias
                (<?= e($modelo['descricao']) ?>). Carregue-a uma vez e ajuste o que quiser.</p>
            <form method="post" action="<?= e(url('compras.acao')) ?>" class="form-inline" style="margin-bottom:.8rem">
                <?= Csrf::campo() ?>
                <input type="hidden" name="crianca" value="<?= e($crianca['slug']) ?>">
                <input type="hidden" name="acao" value="carregar_modelo">
                <button type="submit" class="botao botao-primario">Carregar a semana-modelo</button>
            </form>
        <?php endif; ?>
        <form method="post" action="<?= e(url('compras.acao')) ?>" class="formulario">
            <?= Csrf::campo() ?>
            <input type="hidden" name="crianca" value="<?= e($crianca['slug']) ?>">
            <input type="hidden" name="acao" value="nova_semana">
            <div class="linha-campos">
                <div>
                    <label for="inicio">Nova semana a partir de</label>
                    <input type="date" id="inicio" name="inicio" value="<?= e(date('Y-m-d', strtotime('next monday'))) ?>" required>
                </div>
                <div style="align-self:end">
                    <label class="caixa-selecao"><input type="checkbox" name="do_modelo" value="1" checked>
                        <span>Copiar itens e cardápio do modelo (tudo desmarcado — revise)</span></label>
                </div>
            </div>
            <button type="submit" class="botao botao-contorno">Criar semana</button>
        </form>
    </div>
<?php endif; ?>

<?php if ($semana === null): ?>
    <div class="cartao" style="text-align:center; padding:2rem 1rem">
        <?= icone_ui('cesto', 34, '#D9C58A') ?>
        <p class="texto-apoio" style="margin:.5rem 0 0">Ainda não há semana de cardápio para <?= e($nomeCrianca) ?>.
            <?= $podeEditar ? 'Carregue a semana-modelo acima ou crie uma semana.' : 'Os responsáveis montam o cardápio e a lista.' ?></p>
    </div>
<?php else: ?>

    <?php $seg = $diasSemana; ?>
    <div class="cartao cabecalho-impressao">
        <h3><?= icone_ui('cesto', 18, '#3E6A64') ?> <?= e($semana['titulo']) ?></h3>
        <p class="texto-apoio" style="margin:0">📅 <?= e(data_br($semana['inicio'] . ' 00:00:00', 'd/m')) ?> a
            <?= e(data_br($semana['fim'] . ' 00:00:00', 'd/m/Y')) ?><?= $semana['descricao'] !== null ? ' · ' . e($semana['descricao']) : '' ?></p>
    </div>

    <!-- ── Cardápio da semana ─────────────────────────────── -->
    <div class="cartao" id="cardapio">
        <h3><?= icone_ui('alimentacao', 18, '#3E6A64') ?> Cardápio da semana</h3>
        <?php if ($podeEditar): ?>
            <p class="texto-apoio nao-imprimir" style="margin-top:0">Escreva os alimentos separados por vírgula (ex.: "Arroz, feijão, frango, abóbora e couve").
                Ao salvar, a cuidadora recebe aviso no app, no celular e por e-mail; no dia, o registro do almoço já vem pré-preenchido.</p>
            <form method="post" action="<?= e(url('compras.acao')) ?>" class="formulario" data-form-cardapio>
                <?= Csrf::campo() ?>
                <input type="hidden" name="crianca" value="<?= e($crianca['slug']) ?>">
                <input type="hidden" name="acao" value="salvar_cardapio">
                <input type="hidden" name="semana_id" value="<?= (int)$semana['id'] ?>">
                <div class="tabela-rolavel"><table class="tabela tabela-compacta tabela-cardapio">
                    <thead><tr><th>Dia</th><th>Fruta da manhã</th><th>Fruta da tarde</th><th>Almoço</th><th>Jantar</th></tr></thead>
                    <tbody>
                    <?php foreach ($dias as $dia): $d = (string)$dia['data']; $ehHoje = $d === hoje(); ?>
                        <tr class="<?= $ehHoje ? 'linha-hoje' : '' ?>">
                            <td><strong><?= e($seg[(int)date('w', strtotime($d))]) ?></strong><br><small><?= e(data_br($d . ' 00:00:00', 'd/m')) ?></small></td>
                            <td><input type="text" name="dias[<?= e($d) ?>][fruta_manha]" value="<?= e((string)$dia['fruta_manha']) ?>" maxlength="160"></td>
                            <td><input type="text" name="dias[<?= e($d) ?>][fruta_tarde]" value="<?= e((string)$dia['fruta_tarde']) ?>" maxlength="160"></td>
                            <td><input type="text" name="dias[<?= e($d) ?>][almoco]" value="<?= e((string)$dia['almoco']) ?>" maxlength="255"></td>
                            <td><input type="text" name="dias[<?= e($d) ?>][jantar]" value="<?= e((string)$dia['jantar']) ?>" maxlength="255" placeholder="—"></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table></div>
                <button type="submit" class="botao botao-primario nao-imprimir">Salvar cardápio e avisar a cuidadora</button>
            </form>
        <?php else: ?>
            <div class="tabela-rolavel"><table class="tabela tabela-compacta tabela-cardapio">
                <thead><tr><th>Dia</th><th>Manhã</th><th>Tarde</th><th>Almoço</th><th>Jantar</th></tr></thead>
                <tbody>
                <?php foreach ($dias as $dia): $d = (string)$dia['data']; ?>
                    <tr class="<?= $d === hoje() ? 'linha-hoje' : '' ?>">
                        <td><strong><?= e($seg[(int)date('w', strtotime($d))]) ?></strong> <?= e(data_br($d . ' 00:00:00', 'd/m')) ?></td>
                        <td><?= e((string)($dia['fruta_manha'] ?? '—')) ?></td>
                        <td><?= e((string)($dia['fruta_tarde'] ?? '—')) ?></td>
                        <td><?= e((string)($dia['almoco'] ?? '—')) ?></td>
                        <td><?= $dia['jantar'] !== null && $dia['jantar'] !== '' ? e((string)$dia['jantar']) : '<span class="texto-apoio">—</span>' ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table></div>
        <?php endif; ?>
    </div>

    <!-- ── Lista de compras ───────────────────────────────── -->
    <div class="cartao" id="lista" data-compras data-pode-marcar="<?= $podeMarcar ? '1' : '0' ?>">
        <div class="compras-cabeca">
            <h3 style="margin:0"><?= icone_ui('carrinho-compras', 18, '#3E6A64') ?> Alimentos a comprar</h3>
            <span class="compras-progresso-texto"><strong data-comprados><?= (int)$progresso['comprados'] ?></strong>/<span data-total><?= (int)$progresso['total'] ?></span> no carrinho ·
                <span data-porcentagem><?= (int)$progresso['porcentagem'] ?></span>%</span>
        </div>
        <div class="barra-progresso" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= (int)$progresso['porcentagem'] ?>">
            <div class="barra-progresso-preenchida" data-barra style="width:<?= (int)$progresso['porcentagem'] ?>%"></div>
        </div>
        <div class="compras-acoes nao-imprimir">
            <div class="filtros-compras" role="group" aria-label="Filtro">
                <button type="button" class="chip-filtro chip-ativo" data-filtro="todos">Todos</button>
                <button type="button" class="chip-filtro" data-filtro="pendentes">Pendentes</button>
                <button type="button" class="chip-filtro" data-filtro="marcados">No carrinho</button>
            </div>
            <span class="texto-apoio" data-estado-salvar aria-live="polite"></span>
            <?php if ($podeMarcar && $itens !== []): ?>
                <form method="post" action="<?= e(url('compras.acao')) ?>" class="form-inline" data-confirmar="Desmarcar todos os <?= (int)$progresso['total'] ?> itens desta semana?">
                    <?= Csrf::campo() ?>
                    <input type="hidden" name="crianca" value="<?= e($crianca['slug']) ?>">
                    <input type="hidden" name="acao" value="desmarcar_todos">
                    <input type="hidden" name="semana_id" value="<?= (int)$semana['id'] ?>">
                    <button type="submit" class="botao botao-pequeno botao-contorno">↺ Desmarcar tudo</button>
                </form>
            <?php endif; ?>
        </div>

        <?php if ($itens === []): ?>
            <p class="texto-apoio">A lista está vazia. <?= $podeEditar ? 'Adicione itens abaixo.' : '' ?></p>
        <?php endif; ?>
        <?php foreach ($progresso['categorias'] as $nomeCategoria => $categoria): ?>
            <div class="categoria-compras" data-categoria="<?= e($nomeCategoria) ?>">
                <p class="grupo-folha categoria-compras-titulo">
                    <span><?= e($iconeCategoria[$nomeCategoria] ?? '🧺') ?> <?= e($nomeCategoria) ?></span>
                    <span class="contador-categoria"><span data-cat-comprados><?= (int)$categoria['comprados'] ?></span>/<?= (int)$categoria['total'] ?></span>
                </p>
                <ul class="lista-compras">
                    <?php foreach ($categoria['itens'] as $item): ?>
                        <li class="item-compra <?= (int)$item['comprado'] === 1 ? 'item-comprado' : '' ?>" data-item="<?= (int)$item['id'] ?>" data-comprado="<?= (int)$item['comprado'] ?>">
                            <label class="item-compra-rotulo">
                                <input type="checkbox" class="item-check" <?= (int)$item['comprado'] === 1 ? 'checked' : '' ?> <?= $podeMarcar ? '' : 'disabled' ?>
                                       aria-label="<?= e($item['nome']) ?> — colocado no carrinho">
                                <span class="item-compra-texto">
                                    <strong><?= e($item['nome']) ?></strong>
                                    <?php if ($item['nota'] !== null && $item['nota'] !== ''): ?><small><?= e($item['nota']) ?></small><?php endif; ?>
                                </span>
                                <span class="item-compra-qtd"><?= e((string)($item['quantidade'] ?? '')) ?></span>
                            </label>
                            <?php if ($podeEditar): ?>
                                <form method="post" action="<?= e(url('compras.acao')) ?>" class="form-inline nao-imprimir" data-confirmar="Remover &quot;<?= e($item['nome']) ?>&quot; da lista?">
                                    <?= Csrf::campo() ?>
                                    <input type="hidden" name="crianca" value="<?= e($crianca['slug']) ?>">
                                    <input type="hidden" name="acao" value="item_remover">
                                    <input type="hidden" name="semana_id" value="<?= (int)$semana['id'] ?>">
                                    <input type="hidden" name="item_id" value="<?= (int)$item['id'] ?>">
                                    <button type="submit" class="botao-remover-item" aria-label="Remover item">×</button>
                                </form>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endforeach; ?>

        <?php if ($podeEditar): ?>
            <details class="nao-imprimir" style="margin-top:.8rem">
                <summary class="texto-apoio">Adicionar item à lista</summary>
                <form method="post" action="<?= e(url('compras.acao')) ?>" class="formulario" style="margin-top:.5rem">
                    <?= Csrf::campo() ?>
                    <input type="hidden" name="crianca" value="<?= e($crianca['slug']) ?>">
                    <input type="hidden" name="acao" value="item_adicionar">
                    <input type="hidden" name="semana_id" value="<?= (int)$semana['id'] ?>">
                    <div class="linha-campos">
                        <div><label for="novo_nome">Item</label><input type="text" id="novo_nome" name="nome" required maxlength="120" placeholder="Ovo"></div>
                        <div><label for="novo_categoria">Categoria</label>
                            <select id="novo_categoria" name="categoria">
                                <?php foreach (array_unique(array_merge($modelo['categorias'], array_keys($progresso['categorias']), ['Outros'])) as $cat): ?>
                                    <option value="<?= e($cat) ?>"><?= e($cat) ?></option>
                                <?php endforeach; ?>
                            </select></div>
                        <div><label for="novo_qtd">Quantidade</label><input type="text" id="novo_qtd" name="quantidade" maxlength="120" placeholder="6 unidades"></div>
                        <div><label for="novo_nota">Observação</label><input type="text" id="novo_nota" name="nota" maxlength="255" placeholder="para os almoços de 3 e 5/11"></div>
                    </div>
                    <button type="submit" class="botao botao-contorno">Adicionar</button>
                </form>
            </details>
        <?php endif; ?>
    </div>

    <?php if ($podeEditar): ?>
        <div class="cartao nao-imprimir">
            <h3><?= icone_ui('download', 18, '#3E6A64') ?> Importar marcações do HTML anterior</h3>
            <p class="texto-apoio" style="margin-top:0">Usou a lista em HTML antes do app? Na lista original, clique em
                <strong>"Salvar HTML com marcações"</strong> e envie aqui o arquivo baixado. O app lê apenas o estado salvo
                (nenhum script do arquivo é executado) e mostra uma prévia antes de aplicar.</p>
            <form method="post" action="<?= e(url('compras.acao')) ?>" class="form-inline" enctype="multipart/form-data">
                <?= Csrf::campo() ?>
                <input type="hidden" name="crianca" value="<?= e($crianca['slug']) ?>">
                <input type="hidden" name="acao" value="importar_preparar">
                <input type="hidden" name="semana_id" value="<?= (int)$semana['id'] ?>">
                <input type="file" name="arquivo_html" accept=".html,.htm,text/html" required aria-label="Arquivo HTML">
                <button type="submit" class="botao botao-pequeno botao-contorno">Ler o arquivo</button>
            </form>
        </div>
    <?php endif; ?>

    <div class="cartao">
        <h3><?= icone_ui('livro', 18, '#3E6A64') ?> Orientações para preparar e armazenar</h3>
        <ul class="texto-apoio" style="margin:0; padding-left:1.1rem">
            <?php foreach ($modelo['orientacoes'] as $linha): ?><li><?= e($linha) ?></li><?php endforeach; ?>
        </ul>
        <p class="texto-apoio" style="margin:.6rem 0 0">Marcar um item como comprado <strong>não</strong> registra que o bebê comeu:
            o consumo é anotado pela cuidadora em Alimentação → Introdução alimentar. Quem orienta o cardápio é o pediatra.</p>
    </div>
<?php endif; ?>

<script src="<?= e(asset('js/compras.js')) ?>" defer></script>
