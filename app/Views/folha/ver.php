<?php
use App\Services\ServicoFolha;
/** @var array $documento */ /** @var string $op */ /** @var string $opBaixar */
$arquivo = url('portal.documento.arquivo', ['codigo' => $documento['codigo_publico']]) . '?op=' . $op;
$baixar = url('portal.documento.baixar', ['codigo' => $documento['codigo_publico']]) . '?op=' . $opBaixar;
?>
<p class="texto-apoio"><a href="<?= e(url('portal.documento', ['codigo' => $documento['codigo_publico']])) ?>">← Voltar ao documento</a></p>
<div class="portal-cabeca" style="margin-bottom:.6rem">
    <h2 style="margin:0"><?= e(ServicoFolha::TIPOS[$documento['tipo']] ?? 'Documento') ?> · <?= e(ServicoFolha::rotuloCompetencia($documento['competencia'], true)) ?></h2>
</div>
<div class="acoes-doc" style="margin-bottom:.7rem">
    <a class="botao botao-secundario botao-pequeno" href="<?= e($arquivo) ?>" target="_blank" rel="noopener">Abrir em nova aba</a>
    <a class="botao botao-primario botao-pequeno" href="<?= e($baixar) ?>"><?= icone_ui('download', 15, 'currentColor', 2.2) ?> Baixar PDF</a>
</div>
<div class="visualizador-pdf">
    <iframe src="<?= e($arquivo) ?>" title="PDF do documento" loading="eager"></iframe>
</div>
<p class="texto-apoio">Se o PDF não aparecer no seu celular, use "Abrir em nova aba" ou "Baixar PDF".</p>
