<?php

use App\Core\Csrf;
use App\Core\Sessao;

/** @var array $usuarios */
/** @var array $convites */
/** @var ?string $linkConvite */

Sessao::remover('_link_convite');

$nomesPapeis = [
    'admin_familia' => 'Admin da família',
    'responsavel' => 'Responsável',
    'cuidador' => 'Cuidador(a)',
    'leitor' => 'Leitor(a)',
];
?>
<h2>Usuários e convites</h2>

<?php if (is_string($linkConvite) && $linkConvite !== ''): ?>
    <div class="alerta alerta-sucesso">
        Link do convite (copie e envie também por WhatsApp, se quiser):<br>
        <code style="word-break:break-all"><?= e($linkConvite) ?></code>
    </div>
<?php endif; ?>

<div class="cartao">
    <h3>Convidar novo usuário</h3>
    <form method="post" action="<?= e(url('config.usuarios.acao')) ?>" class="formulario">
        <?= Csrf::campo() ?>
        <input type="hidden" name="acao" value="convidar">
        <div class="linha-campos">
            <div>
                <label for="email">E-mail</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div>
                <label for="papel">Papel</label>
                <select id="papel" name="papel">
                    <?php foreach ($nomesPapeis as $valor => $rotulo): ?>
                        <option value="<?= e($valor) ?>"><?= e($rotulo) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <button type="submit" class="botao botao-primario">Enviar convite</button>
    </form>
</div>

<div class="cartao">
    <h3>Usuários da família</h3>
    <div class="tabela-rolavel"><table class="tabela">
        <thead><tr><th>Nome</th><th>E-mail</th><th>Papel</th><th>Último acesso</th><th>Ações</th></tr></thead>
        <tbody>
        <?php foreach ($usuarios as $usuario): ?>
            <tr class="<?= (int)$usuario['ativo'] === 1 ? '' : 'linha-inativa' ?>">
                <td><?= e($usuario['nome']) ?><?= (int)$usuario['ativo'] === 1 ? '' : ' <em>(inativo)</em>' ?></td>
                <td><?= e($usuario['email']) ?></td>
                <td>
                    <form method="post" action="<?= e(url('config.usuarios.acao')) ?>" class="form-inline">
                        <?= Csrf::campo() ?>
                        <input type="hidden" name="acao" value="papel">
                        <input type="hidden" name="usuario" value="<?= e($usuario['codigo_publico']) ?>">
                        <select name="novo_papel" onchange="this.form.submit()" aria-label="Papel de <?= e($usuario['nome']) ?>">
                            <?php foreach ($nomesPapeis as $valor => $rotulo): ?>
                                <option value="<?= e($valor) ?>" <?= $usuario['papel'] === $valor ? 'selected' : '' ?>><?= e($rotulo) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </td>
                <td><?= e(data_br($usuario['ultimo_login']) ?: 'nunca') ?></td>
                <td>
                    <form method="post" action="<?= e(url('config.usuarios.acao')) ?>" class="form-inline">
                        <?= Csrf::campo() ?>
                        <input type="hidden" name="acao" value="<?= (int)$usuario['ativo'] === 1 ? 'desativar' : 'ativar' ?>">
                        <input type="hidden" name="usuario" value="<?= e($usuario['codigo_publico']) ?>">
                        <button type="submit" class="botao botao-pequeno botao-contorno">
                            <?= (int)$usuario['ativo'] === 1 ? 'Desativar' : 'Reativar' ?>
                        </button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
</div>

<?php if ($convites !== []): ?>
<div class="cartao">
    <h3>Convites</h3>
    <div class="tabela-rolavel"><table class="tabela">
        <thead><tr><th>E-mail</th><th>Papel</th><th>Situação</th><th>Expira</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($convites as $convite): ?>
            <?php
            $pendente = $convite['aceito_em'] === null && strtotime((string)$convite['expira_em']) > time();
            $situacao = $convite['aceito_em'] !== null ? 'Aceito em ' . data_br($convite['aceito_em'], 'd/m/Y')
                : ($pendente ? 'Pendente' : 'Expirado/cancelado');
            ?>
            <tr>
                <td><?= e($convite['email']) ?></td>
                <td><?= e($nomesPapeis[$convite['papel']] ?? $convite['papel']) ?></td>
                <td><?= e($situacao) ?></td>
                <td><?= e(data_br($convite['expira_em'], 'd/m/Y H:i')) ?></td>
                <td>
                    <?php if ($pendente): ?>
                        <form method="post" action="<?= e(url('config.usuarios.acao')) ?>" class="form-inline">
                            <?= Csrf::campo() ?>
                            <input type="hidden" name="acao" value="cancelar_convite">
                            <input type="hidden" name="convite_id" value="<?= (int)$convite['id'] ?>">
                            <button type="submit" class="botao botao-pequeno botao-contorno">Cancelar</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
</div>
<?php endif; ?>
