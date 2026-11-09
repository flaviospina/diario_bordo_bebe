<?php
use App\Core\Csrf;
/** @var array $crianca */ /** @var array $plano */ /** @var array $resumo */
$rotulos = ['fruta_manha' => 'Fruta da manhã', 'almoco' => 'Almoço', 'fruta_tarde' => 'Fruta da tarde', 'jantar' => 'Jantar'];
$estados = ['criar' => ['Nova', 'estado-criar'], 'identico' => ['Já igual', 'estado-identico'], 'atualizar' => ['Atualizar', 'estado-atualizar'],
    'conflito' => ['Editada por vocês — preservada', 'estado-conflito'], 'sobrescrever' => ['Sobrescrever', 'estado-conflito']];
$voltar = url('compras') . '?crianca=' . rawurlencode((string)$crianca['slug']);
?>
<p class="texto-apoio"><a href="<?= e($voltar) ?>">← Compras semanais</a></p>
<h2>Importar planejamento — <?= e($crianca['apelido'] ?: $crianca['nome']) ?></h2>
<div class="cartao">
    <h3><?= e($plano['titulo']) ?></h3>
    <p class="texto-apoio" style="margin-top:0"><?= e(data_br($plano['inicio'] . ' 00:00:00', 'd/m/Y')) ?> a <?= e(data_br($plano['fim'] . ' 00:00:00', 'd/m/Y')) ?> ·
        <?= (int)$plano['totais']['dias'] ?> dias · <?= (int)$plano['totais']['refeicoes'] ?> refeições (<?= (int)$plano['totais']['frutas'] ?> frutas, <?= (int)$plano['totais']['almocos'] ?> almoços, <?= (int)$plano['totais']['jantares'] ?> jantares) ·
        ovo em <?= (int)$plano['totais']['com_ovo'] ?> refeições, peixe em <?= (int)$plano['totais']['com_peixe'] ?>. <?= e($plano['semana_evolucao']) ?></p>
    <p><strong>Prévia</strong> (nada foi gravado ainda):
        <span class="chip-estado estado-criar"><?= (int)$resumo['criar'] ?> novas</span>
        <span class="chip-estado estado-atualizar"><?= (int)$resumo['atualizar'] ?> a atualizar</span>
        <span class="chip-estado estado-identico"><?= (int)$resumo['identicos'] ?> já iguais</span>
        <span class="chip-estado estado-conflito"><?= (int)$resumo['conflitos'] ?> editadas por vocês (preservadas)</span>
        <?php if ($resumo['semanas_novas'] !== []): ?><br><span class="texto-apoio">Semanas que serão criadas (sem lista de compras — você adiciona itens quando quiser): <?= e(implode(', ', $resumo['semanas_novas'])) ?>.</span><?php endif; ?>
    </p>
    <form method="post" action="<?= e(url('compras.acao')) ?>" class="formulario" data-confirmar="Aplicar o planejamento? Compras, itens marcados e registros do diário não são alterados.">
        <?= Csrf::campo() ?>
        <input type="hidden" name="crianca" value="<?= e($crianca['slug']) ?>">
        <input type="hidden" name="acao" value="planejamento_aplicar">
        <?php if ($resumo['conflitos'] > 0): ?>
            <label class="caixa-selecao"><input type="checkbox" name="sobrescrever_manuais" value="1">
                <span>Substituir também as <?= (int)$resumo['conflitos'] ?> refeições que vocês editaram à mão pelo planejado</span></label>
        <?php endif; ?>
        <div class="acoes-doc">
            <button type="submit" class="botao botao-primario">Aplicar planejamento e avisar a cuidadora</button>
            <a class="botao botao-contorno" href="<?= e($voltar) ?>">Cancelar</a>
        </div>
    </form>
</div>
<div class="cartao">
    <h3>Dia a dia</h3>
    <div class="tabela-rolavel"><table class="tabela tabela-compacta tabela-previa">
        <thead><tr><th>Dia</th><?php foreach ($rotulos as $r): ?><th><?= e($r) ?></th><?php endforeach; ?></tr></thead>
        <tbody>
        <?php foreach ($resumo['linhas'] as $linha): ?>
            <tr>
                <td><strong><?= e(data_br($linha['data'] . ' 00:00:00', 'd/m')) ?></strong><br><small><?= e(ucfirst(substr($linha['dia_semana'], 0, 3))) ?></small></td>
                <?php foreach ($rotulos as $slot => $r): $s = $linha['slots'][$slot]; [$rotuloEstado, $classe] = $estados[$s['estado']]; ?>
                    <td>
                        <div><?= e($s['planejado']) ?></div>
                        <?php foreach ($linha['alertas'][$slot] ?? [] as $a): ?><small class="alerta-alergenico" title="<?= e($a['orientacao']) ?>">⚠️ <?= e($a['alimento']) ?></small><?php endforeach; ?>
                        <small class="chip-estado <?= e($classe) ?>"><?= e($rotuloEstado) ?></small>
                        <?php if ($s['estado'] === 'conflito' || $s['estado'] === 'sobrescrever'): ?><br><small class="texto-apoio">atual: <?= e($s['atual']) ?></small><?php endif; ?>
                    </td>
                <?php endforeach; ?>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <details style="margin-top:.6rem"><summary class="texto-apoio">Orientações gerais do planejamento</summary>
        <ul class="texto-apoio"><?php foreach ($plano['alertas_gerais'] as $a): ?><li><?= e($a) ?></li><?php endforeach; ?></ul></details>
</div>
