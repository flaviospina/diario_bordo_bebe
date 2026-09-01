<?php
/** @var array $crianca */
/** @var bool $ehResponsavel */
/** @var bool $ehAdmin */
/** @var ?string $idade */
/** @var array $ultimas peso|altura|pc => ?[medido_em, valor, percentil, origem] */
/** @var array $pendentes */
/** @var array $historico */
/** @var ?array $curvas */
/** @var array{itens:array,outras:array} $calendarioVacinal */
/** @var array $consultas */

$rotuloSexo = ['feminino' => 'Feminino', 'masculino' => 'Masculino'];
$rotuloParto = ['normal' => 'Parto normal', 'cesarea' => 'Cesárea', 'forceps' => 'Fórceps'];
$temValor = static fn($v): bool => $v !== null && trim((string)$v) !== '';
$nomeCurto = (string)($crianca['apelido'] ?: $crianca['nome']);

$desatualizada = static fn(?array $medida): bool => $medida !== null
    && strtotime((string)$medida['medido_em']) < strtotime('-90 days');

$medidaDestaque = static function (?array $medida, string $rotulo, string $unidade, float $fator) use ($desatualizada): void {
    ?>
    <div class="tile-dia tile-medida">
        <span class="tile-numero"><?= $medida !== null
            ? e(number_format((float)$medida['valor'] / $fator, $fator > 100 ? 3 : 1, ',', '.'))
            : '—' ?><small> <?= e($unidade) ?></small></span>
        <span class="tile-rotulo"><?= e($rotulo) ?></span>
        <?php if ($medida !== null): ?>
            <span class="tile-detalhe"><?= e(data_br($medida['medido_em'] . ' 00:00:00', 'd/m/Y')) ?><?=
                $medida['percentil'] !== null ? ' · P' . e(number_format((float)$medida['percentil'], 0)) : '' ?></span>
            <?php if ($desatualizada($medida)): ?>
                <span class="etiqueta-estado etiqueta-ambar">desatualizado</span>
            <?php endif; ?>
        <?php else: ?>
            <span class="tile-detalhe">sem registro</span>
        <?php endif; ?>
    </div>
    <?php
};

?>
<div class="cartao cartao-identidade">
    <?php if (!empty($crianca['foto_codigo'])): ?>
        <img class="foto-crianca" src="<?= e(url('foto.ver', ['codigo' => $crianca['foto_codigo']])) ?>"
             alt="Foto de <?= e($crianca['nome']) ?>">
    <?php else: ?>
        <span class="avatar-crianca avatar-ficha"><?= e(mb_strtoupper(mb_substr($nomeCurto, 0, 1))) ?></span>
    <?php endif; ?>
    <div class="identidade-texto">
        <h2><?= e($crianca['nome']) ?><?= $crianca['apelido'] ? ' <small class="texto-apoio">(' . e($crianca['apelido']) . ')</small>' : '' ?></h2>
        <p class="texto-apoio">
            <?= $idade !== null ? e($idade) : 'idade não informada' ?>
            <?= isset($rotuloSexo[$crianca['sexo']]) ? ' · ' . e($rotuloSexo[$crianca['sexo']]) : '' ?>
            <?= $crianca['data_nascimento'] !== null ? ' · nasc. ' . e(data_br($crianca['data_nascimento'], 'd/m/Y')) : '' ?>
        </p>
        <?php if ($ehResponsavel): ?>
            <div class="acoes-ficha">
                <a class="botao botao-primario botao-pequeno" href="<?= e(url('consulta.gerar', ['slug' => $crianca['slug']])) ?>">
                    <?= icone_ui('estetoscopio', 16, 'currentColor', 2.0) ?> Ficha para consulta</a>
                <a class="botao botao-contorno botao-pequeno" href="<?= e(url('crianca.medicoes', ['slug' => $crianca['slug']])) ?>">Registrar medição</a>
                <?php if ($ehAdmin): ?>
                    <a class="botao botao-contorno botao-pequeno" href="<?= e(url('config.criancas')) ?>?editar=<?= e($crianca['codigo_publico']) ?>">Editar dados</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php foreach ($pendentes as $pendente): ?>
    <div class="cartao-pendencia">
        <span class="selo-categoria" style="width:42px;height:42px;background:#DEEDE9"><?= icone_ui('estetoscopio', 21, '#3E6A64') ?></span>
        <div style="min-width:0">
            <div class="titulo"><?= e($pendente['profissional_nome'] ?? 'O pediatra') ?> registrou uma medição</div>
            <div class="detalhe">
                <?= $pendente['peso_g'] !== null ? e(number_format((int)$pendente['peso_g'] / 1000, 3, ',', '.')) . ' kg · ' : '' ?>
                <?= e(data_br($pendente['medido_em'] . ' 00:00:00', 'd/m/Y')) ?> — confirme para valer na curva
            </div>
        </div>
        <a class="botao botao-primario botao-pequeno" style="margin-left:auto;flex-shrink:0"
           href="<?= e(url('crianca.medicoes', ['slug' => $crianca['slug']])) ?>">Confirmar</a>
    </div>
<?php endforeach; ?>

<div class="tiles-dia">
    <?php $medidaDestaque($ultimas['peso'], 'peso', 'kg', 1000); ?>
    <?php $medidaDestaque($ultimas['altura'], 'altura', 'cm', 10); ?>
    <?php $medidaDestaque($ultimas['pc'], 'perím. cefálico', 'cm', 10); ?>
</div>

<?php
$saude = array_filter([
    'Alergias' => $crianca['alergias'],
    'Restrições alimentares' => $crianca['restricoes_alimentares'],
    'Condições de saúde' => $crianca['condicoes_saude'],
    'Medicações contínuas' => $crianca['medicacoes_continuas'],
    'Tipo sanguíneo' => $crianca['tipo_sanguineo'],
], $temValor);
?>
<div class="cartao cartao-saude">
    <h3><?= icone_ui('coracao-pulso', 18, '#B05E3C') ?> Saúde</h3>
    <?php if ($saude === []): ?>
        <p class="texto-apoio">Sem alergias, restrições ou condições registradas.</p>
    <?php else: ?>
        <?php foreach ($saude as $rotulo => $valorSaude): ?>
            <p><strong><?= e($rotulo) ?>:</strong> <?= e((string)$valorSaude) ?></p>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php if ($curvas !== null): ?>
    <div class="cartao">
        <h3><?= icone_ui('grafico', 18, '#3E6A64') ?> Crescimento</h3>
        <p class="texto-apoio" style="margin-top:0">O percentil mostra a posição na curva de
            referência da OMS. Quem interpreta é sempre o pediatra.</p>
        <?php foreach (['peso' => 'Peso', 'altura' => 'Altura', 'pc' => 'Perímetro cefálico'] as $tipo => $rotulo): ?>
            <?php if (isset($curvas[$tipo])) { echo grafico_crescimento($rotulo, $curvas[$tipo]); } ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php $confirmadas = array_values(array_filter($historico, static fn(array $m): bool => $m['status'] === 'confirmada')); ?>
<?php if ($confirmadas !== []): ?>
    <div class="cartao">
        <h3><?= icone_ui('relogio', 18, '#3E6A64') ?> Histórico de medições</h3>
        <div class="tabela-rolavel"><table class="tabela tabela-compacta">
            <thead><tr><th>Data</th><th>Peso</th><th>Altura</th><th>PC</th><th>Origem</th></tr></thead>
            <tbody>
            <?php foreach (array_slice($confirmadas, 0, 10) as $medicao): ?>
                <tr>
                    <td><?= e(data_br($medicao['medido_em'] . ' 00:00:00', 'd/m/y')) ?></td>
                    <td><?= $medicao['peso_g'] !== null
                        ? e(number_format((int)$medicao['peso_g'] / 1000, 3, ',', '.')) . ' kg'
                            . ($medicao['percentil_peso'] !== null ? ' <small>P' . e(number_format((float)$medicao['percentil_peso'], 0)) . '</small>' : '')
                        : '—' ?></td>
                    <td><?= $medicao['altura_mm'] !== null
                        ? e(number_format((int)$medicao['altura_mm'] / 10, 1, ',', '.')) . ' cm'
                            . ($medicao['percentil_altura'] !== null ? ' <small>P' . e(number_format((float)$medicao['percentil_altura'], 0)) . '</small>' : '')
                        : '—' ?></td>
                    <td><?= $medicao['perimetro_cefalico_mm'] !== null
                        ? e(number_format((int)$medicao['perimetro_cefalico_mm'] / 10, 1, ',', '.')) . ' cm'
                        : '—' ?></td>
                    <td><span class="pilula pilula-origem"><?= $medicao['origem'] === 'pediatra'
                        ? 'consultório' : 'casa' ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
        <?php if ($ehResponsavel): ?>
            <div class="acoes-pagina">
                <a class="botao botao-contorno botao-pequeno" href="<?= e(url('crianca.medicoes', ['slug' => $crianca['slug']])) ?>">
                    <?= icone_ui('mais', 15, 'currentColor', 2.4) ?> Todas as medições e nova medição</a>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php
$itensVacina = $calendarioVacinal['itens'];
$aplicadasTotal = count(array_filter($itensVacina, static fn(array $i): bool => $i['status'] === 'aplicada'))
    + count($calendarioVacinal['outras']);
$atrasadas = array_values(array_filter($itensVacina, static fn(array $i): bool => $i['status'] === 'atrasada'));
$proximas = array_values(array_filter($itensVacina, static fn(array $i): bool => $i['status'] === 'prevista'));
?>
<div class="cartao">
    <h3><?= icone_ui('vacina', 18, '#3E6A64') ?> Vacinas</h3>
    <p class="texto-apoio" style="margin-top:0"><?= $aplicadasTotal ?> dose(s) registrada(s) na caderneta digital.</p>
    <?php foreach (array_slice($atrasadas, 0, 4) as $item): ?>
        <p class="linha-vacina"><span class="etiqueta-estado etiqueta-ambar">em atraso</span>
            <?= e($item['imunizante']) ?> — <?= e($item['dose']) ?>
            <small class="texto-apoio">(prevista aos <?= (int)$item['idade_meses'] ?> meses)</small></p>
    <?php endforeach; ?>
    <?php foreach (array_slice($proximas, 0, 2) as $item): ?>
        <p class="linha-vacina"><span class="etiqueta-estado etiqueta-cinza">próxima</span>
            <?= e($item['imunizante']) ?> — <?= e($item['dose']) ?>
            <small class="texto-apoio">(aos <?= (int)$item['idade_meses'] ?> meses)</small></p>
    <?php endforeach; ?>
    <?php if ($ehResponsavel): ?>
        <div class="acoes-pagina">
            <a class="botao botao-contorno botao-pequeno" href="<?= e(url('crianca.vacinas', ['slug' => $crianca['slug']])) ?>">
                <?= icone_ui('vacina', 15, 'currentColor', 2.0) ?> Caderneta completa</a>
        </div>
    <?php endif; ?>
</div>

<?php if ($consultas !== []): ?>
    <div class="cartao">
        <h3><?= icone_ui('estetoscopio', 18, '#3E6A64') ?> Consultas</h3>
        <?php foreach (array_slice($consultas, 0, 6) as $consulta): ?>
            <p class="linha-vacina">
                <strong><?= e(data_br($consulta['realizada_em'] . ' 00:00:00', 'd/m/Y')) ?></strong>
                <?= $consulta['profissional_nome'] !== null ? '· ' . e($consulta['profissional_nome']) : '' ?>
                <?= $temValor($consulta['motivo']) ? '— ' . e((string)$consulta['motivo']) : '' ?>
                <?php if ($temValor($consulta['conduta'])): ?>
                    <small class="texto-apoio"><br><?= e(mb_substr((string)$consulta['conduta'], 0, 140)) ?></small>
                <?php endif; ?>
                <?php if ($consulta['retorno_em'] !== null): ?>
                    <small class="texto-apoio"><br>retorno: <?= e(data_br($consulta['retorno_em'] . ' 00:00:00', 'd/m/Y')) ?></small>
                <?php endif; ?>
            </p>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php
$nascimentoDados = array_filter([
    'Semanas de gestação' => $crianca['semanas_gestacao'],
    'Tipo de parto' => $rotuloParto[$crianca['tipo_parto']] ?? null,
    'Peso ao nascer' => $crianca['peso_nascimento_g'] !== null
        ? number_format((int)$crianca['peso_nascimento_g'] / 1000, 3, ',', '.') . ' kg' : null,
    'Comprimento ao nascer' => $crianca['comprimento_nascimento_mm'] !== null
        ? number_format((int)$crianca['comprimento_nascimento_mm'] / 10, 1, ',', '.') . ' cm' : null,
    'Perímetro cefálico ao nascer' => $crianca['perimetro_cefalico_nascimento_mm'] !== null
        ? number_format((int)$crianca['perimetro_cefalico_nascimento_mm'] / 10, 1, ',', '.') . ' cm' : null,
    'Convênio' => $temValor($crianca['convenio_nome'])
        ? trim((string)$crianca['convenio_nome'] . ' ' . (string)($crianca['convenio_carteirinha'] ?? '')) : null,
    'Hospital de referência' => $crianca['hospital_referencia'],
], $temValor);
?>
<?php if ($nascimentoDados !== []): ?>
    <div class="cartao">
        <h3><?= icone_ui('estrela', 18, '#3E6A64') ?> Nascimento e convênio</h3>
        <?php foreach ($nascimentoDados as $rotulo => $valorNascimento): ?>
            <p><strong><?= e($rotulo) ?>:</strong> <?= e((string)$valorNascimento) ?></p>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="acoes-pagina">
    <a class="botao botao-contorno" href="<?= e(url('crianca.timeline', ['slug' => $crianca['slug']])) ?>">
        <?= icone_ui('relogio', 16, 'currentColor', 2.0) ?> Linha do tempo completa</a>
</div>
