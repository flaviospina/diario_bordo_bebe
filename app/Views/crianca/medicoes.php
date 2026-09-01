<?php

use App\Core\Csrf;

/** @var array $crianca */
/** @var array $pendentes */
/** @var array $historico */

$nomeCurto = (string)($crianca['apelido'] ?: $crianca['nome']);
?>
<h2>Medições de <?= e($nomeCurto) ?></h2>
<p class="texto-apoio">Cada medição vira um ponto na curva de crescimento — nada é sobrescrito.
    Errou um valor? Registre de novo: a medição mais recente passa a valer como "última".</p>

<?php foreach ($pendentes as $pendente): ?>
    <div class="cartao-pendencia">
        <span class="selo-categoria" style="width:42px;height:42px;background:#DEEDE9"><?= icone_ui('estetoscopio', 21, '#3E6A64') ?></span>
        <div style="min-width:0">
            <div class="titulo"><?= e($pendente['profissional_nome'] ?? 'O pediatra') ?> registrou em <?= e(data_br($pendente['medido_em'] . ' 00:00:00', 'd/m/Y')) ?></div>
            <div class="detalhe">
                <?= $pendente['peso_g'] !== null ? e(number_format((int)$pendente['peso_g'] / 1000, 3, ',', '.')) . ' kg · ' : '' ?>
                <?= $pendente['altura_mm'] !== null ? e(number_format((int)$pendente['altura_mm'] / 10, 1, ',', '.')) . ' cm · ' : '' ?>
                <?= $pendente['perimetro_cefalico_mm'] !== null ? 'PC ' . e(number_format((int)$pendente['perimetro_cefalico_mm'] / 10, 1, ',', '.')) . ' cm' : '' ?>
            </div>
        </div>
        <form method="post" action="<?= e(url('crianca.medicoes.salvar', ['slug' => $crianca['slug']])) ?>"
              class="form-inline" style="margin-left:auto;flex-shrink:0">
            <?= Csrf::campo() ?>
            <input type="hidden" name="acao" value="confirmar">
            <input type="hidden" name="medicao_id" value="<?= (int)$pendente['id'] ?>">
            <button type="submit" class="botao botao-primario botao-pequeno">Confirmar</button>
        </form>
    </div>
<?php endforeach; ?>

<div class="cartao">
    <h3>Nova medição</h3>
    <p class="texto-apoio">Preencha só o que você mediu. Balança de casa vale — a origem fica registrada.</p>
    <form method="post" action="<?= e(url('crianca.medicoes.salvar', ['slug' => $crianca['slug']])) ?>" class="formulario">
        <?= Csrf::campo() ?>
        <input type="hidden" name="acao" value="criar">
        <div class="linha-campos">
            <div>
                <label for="peso_kg">Peso (kg)</label>
                <input type="number" id="peso_kg" name="peso_kg" min="0.3" max="60" step="0.001" placeholder="7,450">
            </div>
            <div>
                <label for="altura_cm">Altura (cm)</label>
                <input type="number" id="altura_cm" name="altura_cm" min="20" max="160" step="0.1" placeholder="68,5">
            </div>
            <div>
                <label for="pc_cm">Perím. cefálico (cm)</label>
                <input type="number" id="pc_cm" name="pc_cm" min="20" max="70" step="0.1" placeholder="43,0">
            </div>
        </div>
        <div class="linha-campos">
            <div>
                <label for="medido_em">Data da medição</label>
                <input type="date" id="medido_em" name="medido_em" value="<?= e(hoje()) ?>">
            </div>
            <div>
                <label for="profissional_nome">Quem mediu <small>(opcional)</small></label>
                <input type="text" id="profissional_nome" name="profissional_nome" maxlength="120"
                       placeholder="ex.: posto de saúde">
            </div>
        </div>
        <label for="observacao">Observação</label>
        <input type="text" id="observacao" name="observacao" maxlength="200">
        <button type="submit" class="botao botao-primario botao-largo">Registrar medição</button>
    </form>
</div>

<?php if ($historico !== []): ?>
    <div class="cartao">
        <h3>Histórico</h3>
        <div class="tabela-rolavel"><table class="tabela tabela-compacta">
            <thead><tr><th>Data</th><th>Peso</th><th>Altura</th><th>PC</th><th>Origem</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($historico as $medicao): ?>
                <tr>
                    <td><?= e(data_br($medicao['medido_em'] . ' 00:00:00', 'd/m/y')) ?></td>
                    <td><?= $medicao['peso_g'] !== null
                        ? e(number_format((int)$medicao['peso_g'] / 1000, 3, ',', '.')) . ' kg'
                            . ($medicao['percentil_peso'] !== null ? ' <small>P' . e(number_format((float)$medicao['percentil_peso'], 0)) . '</small>' : '')
                        : '—' ?></td>
                    <td><?= $medicao['altura_mm'] !== null
                        ? e(number_format((int)$medicao['altura_mm'] / 10, 1, ',', '.')) . ' cm'
                            . ($medicao['percentil_altura'] !== null ? ' <small>P' . e(number_format((float)$medicao['percentil_altura'], 0)) . '</small>' : '')
                        : '—' ?></td>
                    <td><?= $medicao['perimetro_cefalico_mm'] !== null
                        ? e(number_format((int)$medicao['perimetro_cefalico_mm'] / 10, 1, ',', '.')) . ' cm'
                            . ($medicao['percentil_pc'] !== null ? ' <small>P' . e(number_format((float)$medicao['percentil_pc'], 0)) . '</small>' : '')
                        : '—' ?></td>
                    <td><span class="pilula pilula-origem"><?= $medicao['origem'] === 'pediatra' ? 'consultório' : 'casa' ?></span></td>
                    <td><?= $medicao['status'] === 'pendente' ? '<span class="etiqueta-estado etiqueta-ambar">pendente</span>' : '' ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
    </div>
<?php endif; ?>

<p class="texto-apoio">
    <a href="<?= e(url('crianca.ver', ['slug' => $crianca['slug']])) ?>">← Ficha de <?= e($nomeCurto) ?></a>
</p>
