<?php
use App\Services\ServicoFolha;
/** @var array $documentos */ /** @var string $tipo */ /** @var string $ano */ /** @var array $anos */
?>
<div class="portal-cabeca">
    <h2>Meus documentos</h2>
    <p class="texto-apoio">Listagem cronológica dos documentos disponibilizados.</p>
</div>
<form method="get" action="<?= e(url('portal.documentos')) ?>" class="form-inline filtros-docs">
    <label for="f_tipo" class="texto-apoio">Tipo</label>
    <select id="f_tipo" name="tipo" data-enviar-ao-mudar>
        <option value="">Todos</option>
        <?php foreach (ServicoFolha::TIPOS as $chave => $rotulo): ?>
            <option value="<?= e($chave) ?>" <?= $tipo === $chave ? 'selected' : '' ?>><?= e($rotulo) ?></option>
        <?php endforeach; ?>
    </select>
    <label for="f_ano" class="texto-apoio">Ano</label>
    <select id="f_ano" name="ano" data-enviar-ao-mudar>
        <option value="">Todos</option>
        <?php foreach ($anos as $a): ?><option value="<?= e($a) ?>" <?= $ano === $a ? 'selected' : '' ?>><?= e($a) ?></option><?php endforeach; ?>
    </select>
    <noscript><button type="submit" class="botao botao-pequeno botao-contorno">Filtrar</button></noscript>
</form>
<?php require RAIZ_PROJETO . '/app/Views/folha/_lista_documentos.php'; ?>
