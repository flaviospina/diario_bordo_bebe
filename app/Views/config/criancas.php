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
    <form method="post" action="<?= e(url('config.criancas.salvar')) ?>" class="formulario" enctype="multipart/form-data">
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
        <label for="restricoes_alimentares">Restrições alimentares</label>
        <textarea id="restricoes_alimentares" name="restricoes_alimentares" rows="2"><?= $valor('restricoes_alimentares') ?></textarea>

        <label for="foto">Foto da criança</label>
        <?php if (!empty($editando['foto_codigo'])): ?>
            <p class="texto-apoio" style="display:flex;align-items:center;gap:.6rem;margin:.2rem 0 .4rem">
                <img class="foto-crianca-mini" src="<?= e(url('foto.ver', ['codigo' => $editando['foto_codigo']])) ?>?thumb=1" alt="Foto atual de <?= e($editando['nome']) ?>">
                Enviar uma nova foto substitui a atual.
            </p>
        <?php endif; ?>
        <input type="file" id="foto" name="foto" accept="image/jpeg,image/png,image/webp">
        <p class="texto-apoio">JPEG, PNG ou WebP até 8 MB. A foto é reprocessada e os metadados
            (localização do celular etc.) são removidos antes de salvar.</p>

        <h3 style="margin-top:1rem">Nascimento</h3>
        <div class="linha-campos">
            <div>
                <label for="semanas_gestacao">Semanas de gestação</label>
                <input type="number" id="semanas_gestacao" name="semanas_gestacao" min="20" max="45" step="1"
                       value="<?= $valor('semanas_gestacao') ?>">
            </div>
            <div>
                <label for="tipo_parto">Tipo de parto</label>
                <select id="tipo_parto" name="tipo_parto">
                    <option value="">—</option>
                    <?php foreach (['normal' => 'Normal', 'cesarea' => 'Cesárea', 'forceps' => 'Fórceps', 'nao_informado' => 'Prefiro não informar'] as $v => $rotulo): ?>
                        <option value="<?= e($v) ?>" <?= ($editando['tipo_parto'] ?? '') === $v ? 'selected' : '' ?>><?= e($rotulo) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="linha-campos">
            <div>
                <label for="peso_nascimento_kg">Peso ao nascer (kg)</label>
                <input type="number" id="peso_nascimento_kg" name="peso_nascimento_kg" min="0.3" max="8" step="0.001"
                       placeholder="3,250" value="<?= !empty($editando['peso_nascimento_g']) ? e(number_format((int)$editando['peso_nascimento_g'] / 1000, 3, '.', '')) : '' ?>">
            </div>
            <div>
                <label for="comprimento_nascimento_cm">Comprimento (cm)</label>
                <input type="number" id="comprimento_nascimento_cm" name="comprimento_nascimento_cm" min="20" max="70" step="0.1"
                       value="<?= !empty($editando['comprimento_nascimento_mm']) ? e(number_format((int)$editando['comprimento_nascimento_mm'] / 10, 1, '.', '')) : '' ?>">
            </div>
            <div>
                <label for="perimetro_cefalico_nascimento_cm">Perímetro cefálico (cm)</label>
                <input type="number" id="perimetro_cefalico_nascimento_cm" name="perimetro_cefalico_nascimento_cm" min="20" max="45" step="0.1"
                       value="<?= !empty($editando['perimetro_cefalico_nascimento_mm']) ? e(number_format((int)$editando['perimetro_cefalico_nascimento_mm'] / 10, 1, '.', '')) : '' ?>">
            </div>
        </div>

        <h3 style="margin-top:1rem">Convênio e referências</h3>
        <div class="linha-campos">
            <div>
                <label for="convenio_nome">Convênio</label>
                <input type="text" id="convenio_nome" name="convenio_nome" maxlength="120" value="<?= $valor('convenio_nome') ?>">
            </div>
            <div>
                <label for="convenio_carteirinha">Carteirinha</label>
                <input type="text" id="convenio_carteirinha" name="convenio_carteirinha" maxlength="60" value="<?= $valor('convenio_carteirinha') ?>">
            </div>
        </div>
        <label for="hospital_referencia">Hospital de referência</label>
        <input type="text" id="hospital_referencia" name="hospital_referencia" maxlength="120" value="<?= $valor('hospital_referencia') ?>">

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
