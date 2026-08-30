<?php

use App\Core\Csrf;
use App\Core\Sessao;

/** @var array $familias */
/** @var ?string $linkConvite */

Sessao::remover('_link_convite_plataforma');
?>
<h2>Painel da plataforma</h2>
<p class="texto-apoio">Gestão de famílias e planos. O conteúdo dos diários nunca é acessível por aqui.</p>

<?php if (is_string($linkConvite) && $linkConvite !== ''): ?>
    <div class="alerta alerta-sucesso">
        Link de convite do administrador (também enviado por e-mail):<br>
        <code style="word-break:break-all"><?= e($linkConvite) ?></code>
    </div>
<?php endif; ?>

<div class="cartao">
    <h3>Nova família (onboarding)</h3>
    <form method="post" action="<?= e(url('admin.painel.acao')) ?>" class="formulario">
        <?= Csrf::campo() ?>
        <input type="hidden" name="acao" value="criar_familia">
        <div class="linha-campos">
            <div>
                <label for="nome">Nome da família</label>
                <input type="text" id="nome" name="nome" required maxlength="120">
            </div>
            <div>
                <label for="email_admin">E-mail do administrador</label>
                <input type="email" id="email_admin" name="email_admin" required maxlength="190">
            </div>
            <div>
                <label for="plano">Plano</label>
                <select id="plano" name="plano">
                    <option value="familiar">Familiar</option>
                    <option value="premium">Premium</option>
                </select>
            </div>
        </div>
        <button type="submit" class="botao botao-primario">Criar família e convidar admin</button>
    </form>
</div>

<div class="cartao">
    <h3>Famílias (<?= count($familias) ?>)</h3>
    <div class="tabela-rolavel"><table class="tabela">
        <thead><tr><th>Família</th><th>Plano</th><th>Status</th><th>Usuários</th><th>Crianças</th><th>Registros</th><th>Ações</th></tr></thead>
        <tbody>
        <?php foreach ($familias as $familia): ?>
            <tr class="<?= $familia['status'] === 'ativa' ? '' : 'linha-inativa' ?>">
                <td><?= e($familia['nome']) ?><br><small class="texto-apoio">desde <?= e(data_br($familia['criado_em'], 'd/m/Y')) ?></small></td>
                <td>
                    <form method="post" action="<?= e(url('admin.painel.acao')) ?>" class="form-inline">
                        <?= Csrf::campo() ?>
                        <input type="hidden" name="acao" value="plano">
                        <input type="hidden" name="familia" value="<?= e($familia['codigo_publico']) ?>">
                        <select name="novo_plano" onchange="this.form.submit()" aria-label="Plano de <?= e($familia['nome']) ?>">
                            <option value="familiar" <?= $familia['plano'] === 'familiar' ? 'selected' : '' ?>>Familiar</option>
                            <option value="premium" <?= $familia['plano'] === 'premium' ? 'selected' : '' ?>>Premium</option>
                        </select>
                    </form>
                </td>
                <td><?= e($familia['status']) ?></td>
                <td><?= (int)$familia['total_usuarios'] ?></td>
                <td><?= (int)$familia['total_criancas'] ?></td>
                <td><?= (int)$familia['total_registros'] ?></td>
                <td>
                    <form method="post" action="<?= e(url('admin.painel.acao')) ?>" class="form-inline">
                        <?= Csrf::campo() ?>
                        <input type="hidden" name="familia" value="<?= e($familia['codigo_publico']) ?>">
                        <input type="hidden" name="acao" value="<?= $familia['status'] === 'ativa' ? 'suspender' : 'reativar' ?>">
                        <button type="submit" class="botao botao-pequeno botao-contorno">
                            <?= $familia['status'] === 'ativa' ? 'Suspender' : 'Reativar' ?>
                        </button>
                    </form>
                    <details class="form-inline">
                        <summary class="texto-apoio">Excluir…</summary>
                        <form method="post" action="<?= e(url('admin.painel.acao')) ?>" class="formulario">
                            <?= Csrf::campo() ?>
                            <input type="hidden" name="familia" value="<?= e($familia['codigo_publico']) ?>">
                            <input type="hidden" name="acao" value="excluir">
                            <label>Digite "<?= e($familia['nome']) ?>" para excluir TUDO definitivamente (LGPD):</label>
                            <input type="text" name="confirmacao" required>
                            <button type="submit" class="botao botao-pequeno botao-primario">Excluir definitivamente</button>
                        </form>
                    </details>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
</div>
