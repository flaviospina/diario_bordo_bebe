<?php

use App\Core\Csrf;

/** @var array $catalogo */
/** @var array $inativas */
/** @var array $acoesRapidas */

$nomesGrupos = [
    'alimentacao' => 'Alimentação', 'sono' => 'Sono', 'higiene' => 'Higiene', 'saude' => 'Saúde',
    'desenvolvimento' => 'Desenvolvimento', 'rotina' => 'Rotina e deslocamento',
    'comportamento' => 'Comportamento', 'apoio' => 'Apoio doméstico', 'turno' => 'Turno',
    'intercorrencia' => 'Intercorrência',
];
$porGrupo = [];
foreach ($catalogo as $categoria) {
    $porGrupo[$categoria['grupo']][] = $categoria;
}
?>
<h2>Categorias de registro</h2>
<p class="texto-apoio">Marque o que deve aparecer para o cuidador e escolha até 4 ações rápidas para o rodapé da tela "Meu Dia".</p>

<form method="post" action="<?= e(url('config.categorias.salvar')) ?>" class="formulario">
    <?= Csrf::campo() ?>

    <?php foreach ($porGrupo as $grupo => $categorias): ?>
        <fieldset class="cartao">
            <legend><?= e($nomesGrupos[$grupo] ?? $grupo) ?></legend>
            <div class="grade-categorias">
                <?php foreach ($categorias as $categoria): ?>
                    <div class="item-categoria">
                        <label class="caixa-selecao" style="margin:.2rem 0">
                            <input type="checkbox" name="ativas[]" value="<?= e($categoria['slug']) ?>"
                                <?= in_array($categoria['slug'], $inativas, true) ? '' : 'checked' ?>>
                            <span><?= e($categoria['icone']) ?> <?= e($categoria['nome']) ?></span>
                        </label>
                        <label class="rotulo-rapida" title="Ação rápida">
                            <input type="checkbox" name="acoes_rapidas[]" value="<?= e($categoria['slug']) ?>"
                                <?= in_array($categoria['slug'], $acoesRapidas, true) ? 'checked' : '' ?>>
                            ⚡
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
        </fieldset>
    <?php endforeach; ?>

    <button type="submit" class="botao botao-primario botao-largo">Salvar categorias</button>
</form>
