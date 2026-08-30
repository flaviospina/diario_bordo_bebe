<?php

use App\Core\Csrf;

/** @var array $usuario */
/** @var array $perfil */

$valor = static fn(string $campo): string => e((string)($perfil[$campo] ?? ''));
?>
<h2>Meu perfil</h2>
<div class="cartao">
    <p><strong><?= e($usuario['nome']) ?></strong> · <?= e($usuario['email']) ?></p>
    <p class="texto-apoio">Todos os campos abaixo são opcionais.</p>

    <form method="post" action="<?= e(url('perfil.salvar')) ?>" class="formulario">
        <?= Csrf::campo() ?>
        <div class="linha-campos">
            <div>
                <label for="cpf">CPF</label>
                <input type="text" id="cpf" name="cpf" maxlength="14" value="<?= $valor('cpf') ?>">
            </div>
            <div>
                <label for="data_nascimento">Nascimento</label>
                <input type="date" id="data_nascimento" name="data_nascimento" value="<?= $valor('data_nascimento') ?>">
            </div>
            <div>
                <label for="profissao">Profissão</label>
                <input type="text" id="profissao" name="profissao" maxlength="120" value="<?= $valor('profissao') ?>">
            </div>
        </div>
        <div class="linha-campos">
            <div>
                <label for="telefone_alternativo">Telefone alternativo</label>
                <input type="tel" id="telefone_alternativo" name="telefone_alternativo" maxlength="20" value="<?= $valor('telefone_alternativo') ?>">
            </div>
            <div>
                <label for="endereco">Endereço</label>
                <input type="text" id="endereco" name="endereco" maxlength="255" value="<?= $valor('endereco') ?>">
            </div>
        </div>
        <div class="linha-campos">
            <div>
                <label for="contato_emergencia_nome">Contato de emergência — nome</label>
                <input type="text" id="contato_emergencia_nome" name="contato_emergencia_nome" maxlength="120" value="<?= $valor('contato_emergencia_nome') ?>">
            </div>
            <div>
                <label for="contato_emergencia_telefone">Contato de emergência — telefone</label>
                <input type="tel" id="contato_emergencia_telefone" name="contato_emergencia_telefone" maxlength="20" value="<?= $valor('contato_emergencia_telefone') ?>">
            </div>
        </div>
        <label for="observacoes">Observações</label>
        <textarea id="observacoes" name="observacoes" rows="3"><?= $valor('observacoes') ?></textarea>

        <button type="submit" class="botao botao-primario botao-largo">Salvar perfil</button>
    </form>
</div>
