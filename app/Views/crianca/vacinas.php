<?php

use App\Core\Csrf;

/** @var array $crianca */
/** @var array{itens:array,outras:array} $calendarioVacinal */

$nomeCurto = (string)($crianca['apelido'] ?: $crianca['nome']);
$rotuloStatus = ['aplicada' => 'aplicada', 'atrasada' => 'em atraso', 'prevista' => 'prevista'];
$classeStatus = ['aplicada' => 'etiqueta-verde', 'atrasada' => 'etiqueta-ambar', 'prevista' => 'etiqueta-cinza'];
?>
<h2>Caderneta de vacinas — <?= e($nomeCurto) ?></h2>
<p class="texto-apoio">Calendário do PNI (Ministério da Saúde) cruzado com o que já foi registrado.
    O calendário é informativo — quem orienta é sempre o pediatra.</p>

<div class="cartao">
    <h3>Calendário PNI</h3>
    <div class="tabela-rolavel"><table class="tabela tabela-compacta">
        <thead><tr><th>Idade</th><th>Imunizante</th><th>Dose</th><th>Situação</th></tr></thead>
        <tbody>
        <?php foreach ($calendarioVacinal['itens'] as $item): ?>
            <tr>
                <td><?= (int)$item['idade_meses'] === 0 ? 'ao nascer' : (int)$item['idade_meses'] . 'm' ?></td>
                <td><?= e($item['imunizante']) ?></td>
                <td><?= e($item['dose']) ?></td>
                <td>
                    <span class="etiqueta-estado <?= e($classeStatus[$item['status']]) ?>"><?= e($rotuloStatus[$item['status']]) ?></span>
                    <?php if ($item['registro'] !== null && $item['registro']['aplicada_em'] !== null): ?>
                        <small class="texto-apoio"><?= e(data_br($item['registro']['aplicada_em'] . ' 00:00:00', 'd/m/y')) ?></small>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
</div>

<?php if ($calendarioVacinal['outras'] !== []): ?>
    <div class="cartao">
        <h3>Outras doses registradas</h3>
        <?php foreach ($calendarioVacinal['outras'] as $vacina): ?>
            <p class="linha-vacina">
                <span class="etiqueta-estado etiqueta-verde">aplicada</span>
                <?= e($vacina['imunizante']) ?> — <?= e($vacina['dose']) ?>
                <?php if ($vacina['aplicada_em'] !== null): ?>
                    <small class="texto-apoio"><?= e(data_br($vacina['aplicada_em'] . ' 00:00:00', 'd/m/Y')) ?></small>
                <?php endif; ?>
                <?php if ($vacina['profissional_nome'] !== null): ?>
                    <small class="texto-apoio">· <?= e($vacina['profissional_nome']) ?></small>
                <?php endif; ?>
            </p>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="cartao">
    <h3>Registrar dose aplicada</h3>
    <form method="post" action="<?= e(url('crianca.vacinas.salvar', ['slug' => $crianca['slug']])) ?>" class="formulario">
        <?= Csrf::campo() ?>
        <div class="linha-campos">
            <div>
                <label for="imunizante">Imunizante *</label>
                <input type="text" id="imunizante" name="imunizante" required maxlength="120"
                       placeholder="ex.: Pentavalente">
            </div>
            <div>
                <label for="dose">Dose *</label>
                <input type="text" id="dose" name="dose" required maxlength="40" placeholder="ex.: 2ª dose">
            </div>
        </div>
        <div class="linha-campos">
            <div>
                <label for="aplicada_em">Data da aplicação</label>
                <input type="date" id="aplicada_em" name="aplicada_em" value="<?= e(hoje()) ?>">
            </div>
            <div>
                <label for="lote">Lote <small>(opcional)</small></label>
                <input type="text" id="lote" name="lote" maxlength="40">
            </div>
            <div>
                <label for="local_aplicacao">Local <small>(opcional)</small></label>
                <input type="text" id="local_aplicacao" name="local_aplicacao" maxlength="80" placeholder="UBS, clínica…">
            </div>
        </div>
        <button type="submit" class="botao botao-primario botao-largo">Registrar vacina</button>
    </form>
</div>

<div class="acoes-pagina">
    <a class="botao botao-contorno" href="<?= e(url('crianca.ver', ['slug' => $crianca['slug']])) ?>">
        <?= icone_ui('seta-esq', 15, 'currentColor', 2.4) ?> Ficha de <?= e($nomeCurto) ?></a>
</div>
