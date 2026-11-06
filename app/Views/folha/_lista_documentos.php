<?php
/** Partial: lista de documentos publicados (área da colaboradora). Espera array $documentos. */
use App\Services\ServicoFolha;
?>
<?php if ($documentos === []): ?>
    <div class="cartao"><p class="texto-apoio" style="margin:0">Nenhum documento disponível com esses filtros.</p></div>
<?php else: ?>
    <div class="lista-docs">
        <?php foreach ($documentos as $d): ?>
            <a class="doc-item" href="<?= e(url('portal.documento', ['codigo' => $d['codigo_publico']])) ?>">
                <span class="selo-categoria doc-selo"><?= icone_ui('documento', 20, '#5A539E') ?></span>
                <div style="min-width:0">
                    <div class="titulo"><?= e(ServicoFolha::TIPOS[$d['tipo']] ?? $d['tipo']) ?> · <?= e(ServicoFolha::rotuloCompetencia($d['competencia'])) ?></div>
                    <div class="detalhe">
                        <?= $d['descricao'] !== null ? e($d['descricao']) . ' · ' : '' ?>
                        disponibilizado em <?= e(data_br($d['publicado_em'], 'd/m/Y')) ?>
                        <?= (int)$d['versao_numero'] > 1 ? ' · versão ' . (int)$d['versao_numero'] : '' ?>
                    </div>
                </div>
                <span class="chip-estado estado-disponivel">Disponível</span>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
