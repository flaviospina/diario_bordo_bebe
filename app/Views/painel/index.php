<?php

use App\Core\Csrf;
use App\Core\Sessao;

/** @var array $familias */
/** @var array $planos */
/** @var array $convitesFamilia */
/** @var array $listaEspera */
/** @var ?string $linkConvite */

Sessao::remover('_link_convite_plataforma');

$seletorPlanos = static function (string $nomeCampo, string $selecionado) use ($planos): void {
    ?>
    <select name="<?= e($nomeCampo) ?>" aria-label="Plano">
        <?php foreach ($planos as $plano): ?>
            <option value="<?= e($plano['chave']) ?>" <?= $plano['chave'] === $selecionado ? 'selected' : '' ?>>
                <?= e($plano['nome']) ?><?= (int)$plano['preco_centavos'] > 0
                    ? ' — R$ ' . number_format((int)$plano['preco_centavos'] / 100, 2, ',', '.') : '' ?>
            </option>
        <?php endforeach; ?>
    </select>
    <?php
};
$esperaNovos = count(array_filter($listaEspera, static fn(array $l): bool => $l['status'] === 'novo'));
?>
<h2>Painel da plataforma</h2>
<p class="texto-apoio">Gestão de famílias, planos e convites. O conteúdo dos diários nunca é acessível por aqui.</p>

<?php if (is_string($linkConvite) && $linkConvite !== ''): ?>
    <div class="alerta alerta-sucesso">
        Link gerado — copie e envie pelo WhatsApp:<br>
        <code style="word-break:break-all"><?= e($linkConvite) ?></code>
    </div>
<?php endif; ?>

<div class="cartao">
    <h3>Convite de família fundadora</h3>
    <p class="texto-apoio">Gera um link único: o casal abre, cria a própria família e já começa.
        Cada link vale para <strong>uma</strong> família.</p>
    <form method="post" action="<?= e(url('admin.painel.acao')) ?>" class="formulario">
        <?= Csrf::campo() ?>
        <input type="hidden" name="acao" value="convite_familia">
        <div class="linha-campos">
            <div>
                <label for="conv_plano">Plano</label>
                <?php $seletorPlanos('plano', 'fundador'); ?>
            </div>
            <div>
                <label for="validade_dias">Validade (dias)</label>
                <input type="number" id="validade_dias" name="validade_dias" min="1" max="30" value="7">
            </div>
            <div>
                <label for="observacao">Para quem é <small>(opcional)</small></label>
                <input type="text" id="observacao" name="observacao" maxlength="160" placeholder="ex.: João e Maria">
            </div>
        </div>
        <button type="submit" class="botao botao-primario">Gerar link de convite</button>
    </form>

    <?php $abertos = array_filter($convitesFamilia, static fn(array $c): bool => $c['status'] === 'aberto' && (int)$c['ja_expirado'] === 0); ?>
    <?php if ($convitesFamilia !== []): ?>
        <div class="tabela-rolavel"><table class="tabela tabela-compacta">
            <thead><tr><th>Convite</th><th>Plano</th><th>Situação</th><th>Família criada</th><th></th></tr></thead>
            <tbody>
            <?php foreach (array_slice($convitesFamilia, 0, 15) as $convite): ?>
                <tr>
                    <td><code><?= e($convite['codigo_publico']) ?></code>
                        <?= $convite['observacao'] !== null ? '<br><small class="texto-apoio">' . e($convite['observacao']) . '</small>' : '' ?></td>
                    <td><?= e($convite['plano']) ?></td>
                    <td><?php
                        if ($convite['status'] === 'usado') {
                            echo '<span class="etiqueta-estado etiqueta-verde">usado</span>';
                        } elseif ($convite['status'] === 'revogado') {
                            echo '<span class="etiqueta-estado etiqueta-cinza">revogado</span>';
                        } elseif ((int)$convite['ja_expirado'] === 1) {
                            echo '<span class="etiqueta-estado etiqueta-cinza">expirado</span>';
                        } else {
                            echo '<span class="etiqueta-estado etiqueta-azul">aberto até ' . e(data_br($convite['expira_em'], 'd/m')) . '</span>';
                        }
                    ?></td>
                    <td><?= e($convite['familia_nome'] ?? '—') ?></td>
                    <td>
                        <?php if ($convite['status'] === 'aberto' && (int)$convite['ja_expirado'] === 0): ?>
                            <span class="texto-apoio" style="word-break:break-all"><?= e(url_absoluta('comecar', ['codigo' => $convite['codigo_publico']])) ?></span>
                            <form method="post" action="<?= e(url('admin.painel.acao')) ?>" class="form-inline">
                                <?= Csrf::campo() ?>
                                <input type="hidden" name="acao" value="revogar_convite_familia">
                                <input type="hidden" name="convite_id" value="<?= (int)$convite['id'] ?>">
                                <button type="submit" class="botao botao-pequeno botao-contorno">Revogar</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
    <?php endif; ?>
</div>

<div class="cartao">
    <h3>Lista de espera
        <?php if ($esperaNovos > 0): ?><span class="selo-contagem"><?= $esperaNovos ?></span><?php endif; ?>
    </h3>
    <?php if ($listaEspera === []): ?>
        <p class="texto-apoio">Ninguém na lista ainda. Os pedidos da landing pública aparecem aqui.</p>
    <?php else: ?>
        <div class="tabela-rolavel"><table class="tabela tabela-compacta">
            <thead><tr><th>Nome</th><th>Contato</th><th>Mensagem</th><th>Situação</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($listaEspera as $lead): ?>
                <tr class="<?= $lead['status'] === 'descartado' ? 'linha-inativa' : '' ?>">
                    <td><?= e($lead['nome']) ?><br><small class="texto-apoio"><?= e(data_br($lead['criado_em'], 'd/m/Y')) ?></small></td>
                    <td><?= e($lead['email']) ?><?= $lead['whatsapp'] !== null ? '<br><small class="texto-apoio">' . e($lead['whatsapp']) . '</small>' : '' ?></td>
                    <td><small><?= e(mb_substr((string)$lead['mensagem'], 0, 80)) ?></small></td>
                    <td><span class="etiqueta-estado <?= $lead['status'] === 'novo' ? 'etiqueta-azul' : ($lead['status'] === 'convidado' ? 'etiqueta-verde' : 'etiqueta-cinza') ?>"><?= e($lead['status']) ?></span></td>
                    <td>
                        <?php if ($lead['status'] === 'novo'): ?>
                            <form method="post" action="<?= e(url('admin.painel.acao')) ?>" class="form-inline">
                                <?= Csrf::campo() ?>
                                <input type="hidden" name="acao" value="espera_convidar">
                                <input type="hidden" name="espera_id" value="<?= (int)$lead['id'] ?>">
                                <button type="submit" class="botao botao-pequeno botao-primario">Gerar convite</button>
                            </form>
                            <form method="post" action="<?= e(url('admin.painel.acao')) ?>" class="form-inline">
                                <?= Csrf::campo() ?>
                                <input type="hidden" name="acao" value="espera_descartar">
                                <input type="hidden" name="espera_id" value="<?= (int)$lead['id'] ?>">
                                <button type="submit" class="botao botao-pequeno botao-contorno">Descartar</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
    <?php endif; ?>
</div>

<div class="cartao">
    <h3>Nova família (onboarding por e-mail)</h3>
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
                <label>Plano</label>
                <?php $seletorPlanos('plano', 'fundador'); ?>
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
                        <?php $seletorPlanos('novo_plano', (string)$familia['plano']); ?>
                        <button type="submit" class="botao botao-pequeno botao-contorno">Mudar</button>
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
