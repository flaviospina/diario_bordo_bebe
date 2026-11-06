<?php
/** Partial (gestão): tabela de eventos de auditoria. Espera array $eventos, bool $comDocumento. */
use App\Services\ServicoFolha;
?>
<?php if ($eventos === []): ?>
    <p class="texto-apoio" style="margin:0">Nenhum evento registrado.</p>
<?php else: ?>
    <div class="tabela-rolavel">
    <table class="tabela-eventos">
        <thead><tr><?= !empty($comDocumento) ? '<th>Documento</th>' : '' ?><th>Evento</th><th>Quem</th><th>Versão</th><th>Data</th><th>Horário</th><th>Resultado</th></tr></thead>
        <tbody>
        <?php foreach ($eventos as $ev): ?>
            <tr class="evento-<?= e($ev['categoria']) ?> resultado-<?= e($ev['resultado']) ?>">
                <?php if (!empty($comDocumento)): ?>
                    <td><?php if ($ev['documento_codigo'] !== null): ?>
                        <a href="<?= e(url('portal.gestao.documento', ['codigo' => $ev['documento_codigo']])) ?>"><?= e(ServicoFolha::TIPOS[$ev['documento_tipo']] ?? 'Documento') ?> <?= e(ServicoFolha::rotuloCompetencia($ev['competencia'], true)) ?></a>
                    <?php else: ?>—<?php endif; ?></td>
                <?php endif; ?>
                <td><?= e(ServicoFolha::EVENTOS[$ev['tipo']] ?? $ev['tipo']) ?><?= $ev['detalhe'] !== null ? ' <small class="texto-apoio">' . e($ev['detalhe']) . '</small>' : '' ?></td>
                <td><?= e($ev['usuario_nome'] ?? '—') ?></td>
                <td><?= $ev['versao_numero'] !== null ? 'v' . (int)$ev['versao_numero'] : '—' ?></td>
                <td><?= e(ServicoFolha::horaLocal($ev['criado_em'], 'd/m/Y')) ?></td>
                <td><?= e(ServicoFolha::horaLocal($ev['criado_em'], 'H:i:s')) ?></td>
                <td><?= $ev['resultado'] === 'ok' ? 'ok' : ($ev['resultado'] === 'negado' ? 'negado' : 'erro') ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
<?php endif; ?>
