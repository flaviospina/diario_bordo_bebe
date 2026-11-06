<?php
use App\Core\Csrf;
use App\Services\ServicoFolha;
/** @var string $aba */ /** @var array $colaboradoras */ /** @var ?array $colaboradora */ /** @var array $semVinculo */
/** @var array $gestores */ /** @var array $responsaveis */ /** @var bool $ehAdminFamilia */
$abas = ['visao' => 'Visão geral', 'documentos' => 'Documentos', 'pagamentos' => 'Pagamentos', 'beneficios' => 'Benefícios',
    'ferias' => 'Férias e 13º', 'eventos' => 'Histórico de acessos', 'colaboradoras' => 'Colaboradoras'];
$urlAba = static fn(string $a): string => url('portal.gestao') . '?aba=' . $a . ($colaboradora !== null ? '&colaboradora=' . rawurlencode((string)$colaboradora['codigo_publico']) : '');
$chipStatus = static function (string $status): string {
    $classe = ['rascunho' => 'estado-rascunho', 'publicado' => 'estado-disponivel', 'revogado' => 'estado-revogado'][$status] ?? '';
    return '<span class="chip-estado ' . $classe . '">' . e(ServicoFolha::STATUS[$status] ?? $status) . '</span>';
};
?>
<div class="portal-cabeca">
    <h2>Gestão da Colaboradora</h2>
    <p class="texto-apoio">Documentos trabalhistas, pagamentos e benefícios — o eSocial continua sendo a fonte oficial.</p>
</div>

<?php if (count($colaboradoras) > 1): ?>
    <form method="get" action="<?= e(url('portal.gestao')) ?>" class="form-inline" style="margin-bottom:.7rem">
        <input type="hidden" name="aba" value="<?= e($aba) ?>">
        <label for="sel_colab" class="texto-apoio">Colaboradora</label>
        <select id="sel_colab" name="colaboradora" data-enviar-ao-mudar>
            <?php foreach ($colaboradoras as $c): ?>
                <option value="<?= e($c['codigo_publico']) ?>" <?= $colaboradora !== null && $c['id'] === $colaboradora['id'] ? 'selected' : '' ?>><?= e($c['nome']) ?><?= $c['situacao'] === 'encerrada' ? ' (encerrada)' : '' ?></option>
            <?php endforeach; ?>
        </select>
    </form>
<?php endif; ?>

<nav class="abas-portal" aria-label="Seções da gestão">
    <?php foreach ($abas as $chave => $rotulo): ?>
        <a class="chip-filtro <?= $aba === $chave ? 'chip-ativo' : '' ?>" href="<?= e($urlAba($chave)) ?>"><?= e($rotulo) ?></a>
    <?php endforeach; ?>
</nav>

<?php if ($colaboradora === null && $aba !== 'colaboradoras'): ?>
    <div class="cartao">
        <h3>Nenhuma colaboradora vinculada</h3>
        <p class="texto-apoio">Vincule o usuário da babá (já cadastrado como cuidadora) para começar.</p>
        <a class="botao botao-primario" href="<?= e($urlAba('colaboradoras')) ?>">Vincular colaboradora</a>
    </div>
<?php endif; ?>

<?php if ($colaboradora !== null && $aba === 'visao'): ?>
    <section class="cartao">
        <div class="resumo-topo">
            <div><span class="texto-apoio">Colaboradora vinculada</span><h3 style="margin:0"><?= e($colaboradora['nome']) ?></h3>
                <span class="texto-apoio"><?= e($colaboradora['email']) ?><?= $colaboradora['admissao'] !== null ? ' · admissão ' . e(data_br($colaboradora['admissao'] . ' 00:00:00', 'd/m/Y')) : '' ?> · vínculo <?= e($colaboradora['situacao']) ?></span></div>
            <a class="botao botao-primario" href="<?= e(url('portal.gestao.novo')) ?>?colaboradora=<?= e($colaboradora['codigo_publico']) ?>">+ Importar contracheque</a>
        </div>
        <dl class="resumo-grade">
            <div><dt>Última competência publicada</dt><dd><?= e(ServicoFolha::rotuloCompetencia($contagens['ultima_competencia'])) ?></dd></div>
            <div><dt>Documentos</dt><dd><?= (int)$contagens['total'] ?> (<?= (int)$contagens['publicados'] ?> publicados)</dd></div>
            <div><dt>Pendentes (rascunho)</dt><dd><?= (int)$contagens['rascunhos'] ?></dd></div>
            <div><dt>Último acesso a documentos</dt><dd><?= e(ServicoFolha::horaLocal($ultimoAcesso)) ?></dd></div>
        </dl>
    </section>
    <section class="cartao">
        <h3>Últimos pagamentos registrados</h3>
        <?php if ($pagamentos === []): ?><p class="texto-apoio" style="margin:0">Nenhum contracheque ainda.</p><?php else: ?>
            <div class="lista-docs">
                <?php foreach (array_slice($pagamentos, 0, 6) as $p): $d = $p['documento']; $r = $p['resumo']; ?>
                    <a class="doc-item" href="<?= e(url('portal.gestao.documento', ['codigo' => $d['codigo_publico']])) ?>">
                        <div style="min-width:0">
                            <div class="titulo">Contracheque · <?= e(ServicoFolha::rotuloCompetencia($d['competencia'])) ?></div>
                            <div class="detalhe">Líquido <?= $r !== null && $r['salario_liquido'] !== null ? e(ServicoFolha::dinheiro($r['salario_liquido'])) : 'não informado' ?><?= $r !== null && (int)$r['conferido'] === 1 ? ' · conferido' : ' · <strong>sem conferência</strong>' ?><?= ($r['data_pagamento'] ?? $d['data_pagamento']) !== null ? ' · pago em ' . e(data_br(($r['data_pagamento'] ?? $d['data_pagamento']) . ' 00:00:00', 'd/m/Y')) : '' ?></div>
                        </div>
                        <?= $chipStatus($d['status']) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
<?php endif; ?>

<?php if ($colaboradora !== null && in_array($aba, ['documentos', 'ferias'], true)): ?>
    <?php $lista = $aba === 'ferias' ? array_values(array_filter($documentos, static fn(array $d): bool => in_array($d['tipo'], ['ferias', 'decimo_terceiro'], true))) : $documentos; ?>
    <section class="cartao">
        <div class="resumo-topo">
            <h3 style="margin:0"><?= $aba === 'ferias' ? 'Férias e 13º salário' : 'Documentos' ?></h3>
            <a class="botao botao-primario botao-pequeno" href="<?= e(url('portal.gestao.novo')) ?>?colaboradora=<?= e($colaboradora['codigo_publico']) ?><?= $aba === 'ferias' ? '&tipo=ferias' : '' ?>">+ Importar PDF</a>
        </div>
        <?php if ($aba === 'documentos'): ?>
            <form method="get" action="<?= e(url('portal.gestao')) ?>" class="form-inline filtros-docs">
                <input type="hidden" name="aba" value="documentos"><input type="hidden" name="colaboradora" value="<?= e($colaboradora['codigo_publico']) ?>">
                <select name="tipo" data-enviar-ao-mudar aria-label="Tipo"><option value="">Todos os tipos</option>
                    <?php foreach (ServicoFolha::TIPOS as $k => $v): ?><option value="<?= e($k) ?>" <?= $filtroTipo === $k ? 'selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?></select>
                <select name="status" data-enviar-ao-mudar aria-label="Status"><option value="">Todos os status</option>
                    <?php foreach (ServicoFolha::STATUS as $k => $v): ?><option value="<?= e($k) ?>" <?= $filtroStatus === $k ? 'selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?></select>
            </form>
        <?php endif; ?>
        <?php if ($lista === []): ?><p class="texto-apoio" style="margin:0">Nenhum documento.</p><?php else: ?>
            <div class="lista-docs">
                <?php foreach ($lista as $d): ?>
                    <a class="doc-item" href="<?= e(url('portal.gestao.documento', ['codigo' => $d['codigo_publico']])) ?>">
                        <span class="selo-categoria doc-selo"><?= icone_ui('documento', 20, '#5A539E') ?></span>
                        <div style="min-width:0">
                            <div class="titulo"><?= e(ServicoFolha::TIPOS[$d['tipo']] ?? $d['tipo']) ?> · <?= e(ServicoFolha::rotuloCompetencia($d['competencia'])) ?></div>
                            <div class="detalhe">v<?= (int)$d['versao_numero'] ?> · <?= e((string)$d['nome_original']) ?> · <?= $d['publicado_em'] !== null ? 'publicado ' . e(data_br($d['publicado_em'], 'd/m/Y')) : 'criado ' . e(data_br($d['criado_em'], 'd/m/Y')) ?></div>
                        </div>
                        <?= $chipStatus($d['status']) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
<?php endif; ?>

<?php if ($colaboradora !== null && $aba === 'pagamentos'): ?>
    <section class="cartao">
        <h3>Pagamentos por competência</h3>
        <?php if ($pagamentos === []): ?><p class="texto-apoio" style="margin:0">Nenhum contracheque importado.</p><?php else: ?>
            <div class="tabela-rolavel"><table class="tabela-eventos">
                <thead><tr><th>Competência</th><th>Bruto</th><th>Líquido</th><th>Horas extras</th><th>Pago em</th><th>Conferência</th><th>Status</th></tr></thead>
                <tbody><?php foreach ($pagamentos as $p): $d = $p['documento']; $r = $p['resumo']; ?>
                    <tr><td><a href="<?= e(url('portal.gestao.documento', ['codigo' => $d['codigo_publico']])) ?>"><?= e(ServicoFolha::rotuloCompetencia($d['competencia'], true)) ?></a></td>
                        <td><?= e(ServicoFolha::dinheiro($r['salario_bruto'] ?? null)) ?></td><td><?= e(ServicoFolha::dinheiro($r['salario_liquido'] ?? null)) ?></td>
                        <td><?= e(ServicoFolha::dinheiro($r['horas_extras_valor'] ?? null)) ?></td>
                        <td><?= ($r['data_pagamento'] ?? $d['data_pagamento']) !== null ? e(data_br(($r['data_pagamento'] ?? $d['data_pagamento']) . ' 00:00:00', 'd/m/Y')) : '—' ?></td>
                        <td><?= $r !== null && (int)$r['conferido'] === 1 ? 'conferido' : 'pendente' ?></td><td><?= $chipStatus($d['status']) ?></td></tr>
                <?php endforeach; ?></tbody></table></div>
        <?php endif; ?>
    </section>
<?php endif; ?>

<?php if ($colaboradora !== null && $aba === 'beneficios'): ?>
    <section class="cartao">
        <h3><?= $vale !== null ? 'Editar vale-transporte' : 'Registrar vale-transporte antecipado' ?></h3>
        <p class="texto-apoio">Diferencie o <strong>mês de referência</strong> (para que mês o benefício serve), a <strong>data do pagamento</strong> e a <strong>competência</strong> da folha em que foi registrado. Ex.: VT de novembro/2026 — pago em 30/10/2026 — folha de outubro/2026.</p>
        <form method="post" action="<?= e(url('portal.gestao.vt')) ?>" class="formulario">
            <?= Csrf::campo() ?>
            <input type="hidden" name="colaboradora_id" value="<?= (int)$colaboradora['id'] ?>">
            <div class="linha-campos">
                <div><label for="vt_mes">Mês de referência *</label><input type="month" id="vt_mes" name="mes_referencia" required value="<?= e((string)($vale['mes_referencia'] ?? date('Y-m', strtotime('first day of next month')))) ?>"></div>
                <div><label for="vt_pag">Data do pagamento</label><input type="date" id="vt_pag" name="data_pagamento" value="<?= e((string)($vale['data_pagamento'] ?? '')) ?>"></div>
                <div><label for="vt_comp">Competência da folha</label><input type="month" id="vt_comp" name="competencia" value="<?= e((string)($vale['competencia'] ?? '')) ?>"></div>
            </div>
            <div class="linha-campos">
                <div><label for="vt_diario">Valor diário (R$)</label><input type="text" inputmode="decimal" id="vt_diario" name="valor_diario" placeholder="22,00" value="<?= $vale !== null && $vale['valor_diario'] !== null ? e(number_format((float)$vale['valor_diario'], 2, ',', '.')) : '' ?>"></div>
                <div><label for="vt_dias">Dias considerados</label><input type="number" min="0" max="31" id="vt_dias" name="dias" value="<?= e((string)($vale['dias'] ?? '')) ?>"></div>
                <div><label for="vt_total">Valor total (R$)</label><input type="text" inputmode="decimal" id="vt_total" name="valor_total" placeholder="vazio = diário × dias" value="<?= $vale !== null && $vale['valor_total'] !== null ? e(number_format((float)$vale['valor_total'], 2, ',', '.')) : '' ?>"></div>
                <div><label for="vt_desc">Desconto em folha (R$)</label><input type="text" inputmode="decimal" id="vt_desc" name="desconto_folha" value="<?= $vale !== null && $vale['desconto_folha'] !== null ? e(number_format((float)$vale['desconto_folha'], 2, ',', '.')) : '' ?>"></div>
            </div>
            <label for="vt_doc">Comprovante (código do documento já importado, opcional)</label>
            <select id="vt_doc" name="documento_codigo"><option value="">— sem comprovante —</option>
                <?php foreach ($documentos as $d): if (!in_array($d['tipo'], ['vale_transporte', 'comprovante_pagamento'], true)) { continue; } ?>
                    <option value="<?= e($d['codigo_publico']) ?>" <?= $vale !== null && (int)$vale['documento_id'] === (int)$d['id'] ? 'selected' : '' ?>><?= e(ServicoFolha::TIPOS[$d['tipo']]) ?> · <?= e(ServicoFolha::rotuloCompetencia($d['competencia'], true)) ?> (<?= e(ServicoFolha::STATUS[$d['status']]) ?>)</option>
                <?php endforeach; ?></select>
            <label for="vt_obs">Observações administrativas</label>
            <input type="text" id="vt_obs" name="observacoes" maxlength="500" value="<?= e((string)($vale['observacoes'] ?? '')) ?>">
            <button type="submit" class="botao botao-primario">Salvar vale-transporte</button>
        </form>
    </section>
    <section class="cartao">
        <h3>Histórico de vale-transporte</h3>
        <?php if ($vales === []): ?><p class="texto-apoio" style="margin:0">Nenhum lançamento.</p><?php else: ?>
            <div class="tabela-rolavel"><table class="tabela-eventos">
                <thead><tr><th>Referência</th><th>Pago em</th><th>Folha</th><th>Diário × dias</th><th>Total</th><th>Desconto</th><th></th></tr></thead>
                <tbody><?php foreach ($vales as $v): ?>
                    <tr><td><?= e(ServicoFolha::rotuloCompetencia($v['mes_referencia'], true)) ?><?= $v['data_pagamento'] !== null && substr((string)$v['data_pagamento'], 0, 7) < (string)$v['mes_referencia'] ? ' <small class="texto-apoio">antecipado</small>' : '' ?></td>
                        <td><?= $v['data_pagamento'] !== null ? e(data_br($v['data_pagamento'] . ' 00:00:00', 'd/m/Y')) : '—' ?></td>
                        <td><?= e(ServicoFolha::rotuloCompetencia($v['competencia'], true)) ?></td>
                        <td><?= $v['valor_diario'] !== null ? e(ServicoFolha::dinheiro($v['valor_diario'])) . ' × ' . (int)$v['dias'] : '—' ?></td>
                        <td><?= e(ServicoFolha::dinheiro($v['valor_total'])) ?></td><td><?= e(ServicoFolha::dinheiro($v['desconto_folha'])) ?></td>
                        <td class="acoes-linha"><a href="<?= e($urlAba('beneficios')) ?>&vt=<?= (int)$v['id'] ?>">editar</a>
                            <form method="post" action="<?= e(url('portal.gestao.vt')) ?>" class="form-inline" data-confirmar="Excluir o vale-transporte de <?= e(ServicoFolha::rotuloCompetencia($v['mes_referencia'], true)) ?>?"><?= Csrf::campo() ?>
                                <input type="hidden" name="acao" value="excluir"><input type="hidden" name="colaboradora_id" value="<?= (int)$colaboradora['id'] ?>"><input type="hidden" name="id" value="<?= (int)$v['id'] ?>">
                                <button type="submit" class="botao-remover-item" aria-label="Excluir">×</button></form></td></tr>
                <?php endforeach; ?></tbody></table></div>
        <?php endif; ?>
    </section>
<?php endif; ?>

<?php if ($colaboradora !== null && $aba === 'eventos'): ?>
    <section class="cartao">
        <h3>Histórico de acessos</h3>
        <p class="texto-apoio">Eventos técnicos registrados pelo servidor (UTC no banco, exibidos no horário de São Paulo). "Documento acessado" não comprova leitura, concordância ou pagamento.</p>
        <?php $comDocumento = true; require RAIZ_PROJETO . '/app/Views/folha/_eventos.php'; ?>
    </section>
<?php endif; ?>

<?php if ($aba === 'colaboradoras'): ?>
    <section class="cartao">
        <h3>Colaboradoras vinculadas</h3>
        <?php if ($colaboradoras === []): ?><p class="texto-apoio">Nenhuma ainda.</p><?php endif; ?>
        <?php foreach ($colaboradoras as $c): ?>
            <form method="post" action="<?= e(url('portal.gestao.colaboradoras')) ?>" class="formulario vinculo-form">
                <?= Csrf::campo() ?><input type="hidden" name="acao" value="atualizar"><input type="hidden" name="colaboradora_id" value="<?= (int)$c['id'] ?>">
                <div class="linha-campos">
                    <div><strong><?= e($c['nome']) ?></strong><br><span class="texto-apoio"><?= e($c['email']) ?><?= (int)$c['usuario_ativo'] !== 1 ? ' · usuário desativado' : '' ?></span></div>
                    <div><label>Admissão</label><input type="date" name="admissao" value="<?= e((string)($c['admissao'] ?? '')) ?>"></div>
                    <div><label>Vínculo</label><select name="situacao"><option value="ativa" <?= $c['situacao'] === 'ativa' ? 'selected' : '' ?>>Ativo</option><option value="encerrada" <?= $c['situacao'] === 'encerrada' ? 'selected' : '' ?>>Encerrado</option></select></div>
                    <div><label>Observações</label><input type="text" name="observacoes" maxlength="500" value="<?= e((string)($c['observacoes'] ?? '')) ?>"></div>
                </div>
                <button type="submit" class="botao botao-contorno botao-pequeno">Salvar</button>
            </form>
        <?php endforeach; ?>
    </section>
    <section class="cartao">
        <h3>Vincular colaboradora</h3>
        <?php if ($semVinculo === []): ?>
            <p class="texto-apoio" style="margin:0">Todas as cuidadoras ativas já estão vinculadas. Para uma nova babá, convide-a primeiro em Configurações → Usuários (papel cuidadora).</p>
        <?php else: ?>
            <form method="post" action="<?= e(url('portal.gestao.colaboradoras')) ?>" class="formulario">
                <?= Csrf::campo() ?><input type="hidden" name="acao" value="vincular">
                <div class="linha-campos">
                    <div><label for="v_usuario">Cuidadora (usuário existente)</label><select id="v_usuario" name="usuario_id" required>
                        <?php foreach ($semVinculo as $u): ?><option value="<?= (int)$u['id'] ?>"><?= e($u['nome']) ?> — <?= e($u['email']) ?></option><?php endforeach; ?></select></div>
                    <div><label for="v_adm">Data de admissão</label><input type="date" id="v_adm" name="admissao"></div>
                </div>
                <label for="v_obs">Observações</label><input type="text" id="v_obs" name="observacoes" maxlength="500">
                <button type="submit" class="botao botao-primario">Vincular</button>
            </form>
        <?php endif; ?>
    </section>
    <?php if ($ehAdminFamilia): ?>
        <section class="cartao">
            <h3>Quem pode gerir a folha</h3>
            <p class="texto-apoio">Você (admin da família) sempre pode. Outros responsáveis só com autorização explícita. Cuidadoras e avós nunca.</p>
            <?php foreach ($gestores as $g): ?>
                <form method="post" action="<?= e(url('portal.gestao.colaboradoras')) ?>" class="form-inline" style="margin-bottom:.4rem"><?= Csrf::campo() ?>
                    <input type="hidden" name="acao" value="retirar"><input type="hidden" name="usuario_id" value="<?= (int)$g['usuario_id'] ?>">
                    <span><?= e($g['nome']) ?> <small class="texto-apoio"><?= e($g['email']) ?></small></span>
                    <button type="submit" class="botao botao-contorno botao-pequeno">Retirar acesso</button></form>
            <?php endforeach; ?>
            <?php $candidatos = array_filter($responsaveis, static fn(array $r): bool => !in_array($r['id'], array_column($gestores, 'usuario_id'), true)); ?>
            <?php if ($candidatos !== []): ?>
                <form method="post" action="<?= e(url('portal.gestao.colaboradoras')) ?>" class="form-inline"><?= Csrf::campo() ?><input type="hidden" name="acao" value="conceder">
                    <select name="usuario_id" aria-label="Responsável"><?php foreach ($candidatos as $r): ?><option value="<?= (int)$r['id'] ?>"><?= e($r['nome']) ?></option><?php endforeach; ?></select>
                    <button type="submit" class="botao botao-secundario botao-pequeno">Conceder gestão</button></form>
            <?php endif; ?>
        </section>
    <?php endif; ?>
<?php endif; ?>
