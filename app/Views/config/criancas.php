<?php

use App\Core\Csrf;

/** @var array $criancas */
/** @var ?array $editando */

$valor = static fn(string $campo): string => e((string)($editando[$campo] ?? ''));
?>
<h2>Crianças</h2>

<?php if ($criancas !== []): ?>
<div class="cartao">
    <div class="tabela-rolavel"><table class="tabela">
        <thead><tr><th>Nome</th><th>Nascimento</th><th>Alergias</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($criancas as $crianca): ?>
            <tr class="<?= (int)$crianca['ativo'] === 1 ? '' : 'linha-inativa' ?>">
                <td>
                    <a href="<?= e(url('crianca.ver', ['slug' => $crianca['slug']])) ?>"><?= e($crianca['nome']) ?></a>
                    <?= $crianca['apelido'] ? ' (' . e($crianca['apelido']) . ')' : '' ?>
                    <?= (int)$crianca['ativo'] === 1 ? '' : ' <em>(inativa)</em>' ?>
                </td>
                <td><?= e(data_br($crianca['data_nascimento'], 'd/m/Y')) ?></td>
                <td><?= e(mb_substr((string)$crianca['alergias'], 0, 40)) ?></td>
                <td><a class="botao botao-pequeno botao-contorno"
                       href="<?= e(url('config.criancas')) ?>?editar=<?= e($crianca['codigo_publico']) ?>">Editar</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
</div>
<?php endif; ?>

<div class="cartao">
    <h3><?= $editando !== null ? 'Editar ' . e($editando['nome']) : 'Cadastrar criança' ?></h3>
    <p class="texto-apoio">Apenas o nome é obrigatório — preencha o resto quando e se quiser.</p>
    <form method="post" action="<?= e(url('config.criancas.salvar')) ?>" class="formulario">
        <?= Csrf::campo() ?>
        <input type="hidden" name="codigo" value="<?= e((string)($editando['codigo_publico'] ?? '')) ?>">

        <div class="linha-campos">
            <div>
                <label for="nome">Nome *</label>
                <input type="text" id="nome" name="nome" required maxlength="120" value="<?= $valor('nome') ?>">
            </div>
            <div>
                <label for="apelido">Apelido</label>
                <input type="text" id="apelido" name="apelido" maxlength="60" value="<?= $valor('apelido') ?>">
            </div>
        </div>
        <div class="linha-campos">
            <div>
                <label for="data_nascimento">Nascimento</label>
                <input type="date" id="data_nascimento" name="data_nascimento" value="<?= $valor('data_nascimento') ?>">
            </div>
            <div>
                <label for="sexo">Sexo</label>
                <select id="sexo" name="sexo">
                    <option value="">—</option>
                    <?php foreach (['feminino' => 'Feminino', 'masculino' => 'Masculino', 'nao_informado' => 'Prefiro não informar'] as $v => $rotulo): ?>
                        <option value="<?= e($v) ?>" <?= ($editando['sexo'] ?? '') === $v ? 'selected' : '' ?>><?= e($rotulo) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="tipo_sanguineo">Tipo sanguíneo</label>
                <input type="text" id="tipo_sanguineo" name="tipo_sanguineo" maxlength="3" placeholder="O+" value="<?= $valor('tipo_sanguineo') ?>">
            </div>
        </div>

        <label for="alergias">Alergias</label>
        <textarea id="alergias" name="alergias" rows="2"><?= $valor('alergias') ?></textarea>
        <label for="condicoes_saude">Condições de saúde</label>
        <textarea id="condicoes_saude" name="condicoes_saude" rows="2"><?= $valor('condicoes_saude') ?></textarea>
        <label for="medicacoes_continuas">Medicações contínuas</label>
        <textarea id="medicacoes_continuas" name="medicacoes_continuas" rows="2"><?= $valor('medicacoes_continuas') ?></textarea>

        <div class="linha-campos">
            <div>
                <label for="pediatra_nome">Pediatra</label>
                <input type="text" id="pediatra_nome" name="pediatra_nome" maxlength="120" value="<?= $valor('pediatra_nome') ?>">
            </div>
            <div>
                <label for="pediatra_telefone">Telefone do pediatra</label>
                <input type="tel" id="pediatra_telefone" name="pediatra_telefone" maxlength="20" value="<?= $valor('pediatra_telefone') ?>">
            </div>
        </div>

        <?php if ($editando !== null): ?>
            <label class="caixa-selecao">
                <input type="checkbox" name="ativo" value="1" <?= (int)($editando['ativo'] ?? 1) === 1 ? 'checked' : '' ?>>
                <span>Criança ativa (desmarque para arquivar sem apagar o histórico)</span>
            </label>
        <?php else: ?>
            <input type="hidden" name="ativo" value="1">
        <?php endif; ?>

        <button type="submit" class="botao botao-primario botao-largo">
            <?= $editando !== null ? 'Salvar alterações' : 'Cadastrar' ?>
        </button>
    </form>
</div>
