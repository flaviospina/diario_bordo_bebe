<?php
/** @var int $totalUsuarios */
/** @var int $totalCriancas */
/** @var bool $ehAdmin */
?>
<h2>Configurações</h2>
<div class="grade-cartoes">
    <a class="cartao cartao-link" href="<?= e(url('config.familia')) ?>">
        <h3><?= icone_ui('engrenagem', 20, '#3E6A64') ?> Família</h3>
        <p class="texto-apoio">Janela do dia, roteiro, regras de edição, alertas, fotos e resumo diário.</p>
    </a>
    <?php if ($ehAdmin): ?>
        <a class="cartao cartao-link" href="<?= e(url('config.usuarios')) ?>">
            <h3><?= icone_ui('pessoas', 20, '#3E6A64') ?> Usuários e convites</h3>
            <p class="texto-apoio"><?= (int)$totalUsuarios ?> usuário(s). Convide responsáveis, cuidadores e leitores.</p>
        </a>
        <a class="cartao cartao-link" href="<?= e(url('config.criancas')) ?>">
            <h3><?= icone_ui('sorriso', 20, '#3E6A64') ?> Crianças</h3>
            <p class="texto-apoio"><?= (int)$totalCriancas ?> criança(s) cadastrada(s).</p>
        </a>
        <a class="cartao cartao-link" href="<?= e(url('config.categorias')) ?>">
            <h3><?= icone_ui('estrela', 20, '#3E6A64') ?> Categorias</h3>
            <p class="texto-apoio">Escolha o que aparece para o cuidador e as ações rápidas.</p>
        </a>
        <a class="cartao cartao-link" href="<?= e(url('auditoria.index')) ?>">
            <h3><?= icone_ui('lupa', 20, '#3E6A64') ?> Auditoria</h3>
            <p class="texto-apoio">Histórico de edições, acessos e aprovações.</p>
        </a>
    <?php endif; ?>
    <a class="cartao cartao-link" href="<?= e(url('perfil.editar')) ?>">
        <h3><?= icone_ui('pessoa', 20, '#3E6A64') ?> Meu perfil</h3>
        <p class="texto-apoio">Seus dados complementares (opcionais).</p>
    </a>
</div>
