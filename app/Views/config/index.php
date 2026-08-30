<?php
/** @var int $totalUsuarios */
/** @var int $totalCriancas */
/** @var bool $ehAdmin */
?>
<h2>Configurações</h2>
<div class="grade-cartoes">
    <a class="cartao cartao-link" href="<?= e(url('config.familia')) ?>">
        <h3>⚙️ Família</h3>
        <p class="texto-apoio">Janela do dia, roteiro, regras de edição, alertas, fotos e resumo diário.</p>
    </a>
    <?php if ($ehAdmin): ?>
        <a class="cartao cartao-link" href="<?= e(url('config.usuarios')) ?>">
            <h3>👥 Usuários e convites</h3>
            <p class="texto-apoio"><?= (int)$totalUsuarios ?> usuário(s). Convide responsáveis, cuidadores e leitores.</p>
        </a>
        <a class="cartao cartao-link" href="<?= e(url('config.criancas')) ?>">
            <h3>🧒 Crianças</h3>
            <p class="texto-apoio"><?= (int)$totalCriancas ?> criança(s) cadastrada(s).</p>
        </a>
        <a class="cartao cartao-link" href="<?= e(url('config.categorias')) ?>">
            <h3>🗂️ Categorias</h3>
            <p class="texto-apoio">Escolha o que aparece para o cuidador e as ações rápidas.</p>
        </a>
        <a class="cartao cartao-link" href="<?= e(url('auditoria.index')) ?>">
            <h3>🔍 Auditoria</h3>
            <p class="texto-apoio">Histórico de edições, acessos e aprovações.</p>
        </a>
    <?php endif; ?>
    <a class="cartao cartao-link" href="<?= e(url('perfil.editar')) ?>">
        <h3>👤 Meu perfil</h3>
        <p class="texto-apoio">Seus dados complementares (opcionais).</p>
    </a>
</div>
