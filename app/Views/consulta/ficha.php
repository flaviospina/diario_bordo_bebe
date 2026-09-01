<?php
/** @var string $codigo */
/** @var array $convite */
/** @var array $crianca */
/** @var ?string $idade */
/** @var array $ultimas */
/** @var array $historico */
/** @var array $vacinas */
/** @var array $resumo */

$rotuloSexo = ['feminino' => 'Feminino', 'masculino' => 'Masculino'];
$rotuloParto = ['normal' => 'Parto normal', 'cesarea' => 'Cesárea', 'forceps' => 'Fórceps'];
$temValor = static fn($v): bool => $v !== null && trim((string)$v) !== '';

$medidaDestaque = static function (?array $medida, string $rotulo, string $unidade, float $fator): void {
    ?>
    <div class="tile-dia tile-medida">
        <span class="tile-numero"><?= $medida !== null
            ? e(number_format((float)$medida['valor'] / $fator, $fator > 100 ? 3 : 1, ',', '.'))
            : '—' ?><small> <?= e($unidade) ?></small></span>
        <span class="tile-rotulo"><?= e($rotulo) ?></span>
        <?php if ($medida !== null): ?>
            <span class="tile-detalhe"><?= e(data_br($medida['medido_em'] . ' 00:00:00', 'd/m/Y')) ?><?=
                $medida['percentil'] !== null ? ' · P' . e(number_format((float)$medida['percentil'], 0)) : '' ?></span>
        <?php endif; ?>
    </div>
    <?php
};
?>
<div class="cartao cartao-identidade">
    <span class="avatar-crianca avatar-ficha"><?= e(mb_strtoupper(mb_substr((string)$crianca['nome'], 0, 1))) ?></span>
    <div class="identidade-texto">
        <h2><?= e($crianca['nome']) ?></h2>
        <p class="texto-apoio">
            <?= $idade !== null ? e($idade) : 'idade não informada' ?>
            <?= isset($rotuloSexo[$crianca['sexo']]) ? ' · ' . e($rotuloSexo[$crianca['sexo']]) : '' ?>
            <?= $crianca['data_nascimento'] !== null ? ' · nasc. ' . e(data_br($crianca['data_nascimento'], 'd/m/Y')) : '' ?>
        </p>
    </div>
</div>

<p class="aviso-uso-unico texto-apoio">
    <?= icone_ui('relogio', 15, 'currentColor', 2.0) ?>
    Link de uso único gerado pela família — os dados enviados aqui entram como
    pendentes até a confirmação dos responsáveis.
</p>

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
        <?php foreach ($saude as $rotulo => $valor): ?>
            <p><strong><?= e($rotulo) ?>:</strong> <?= e((string)$valor) ?></p>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php
$nascimento = array_filter([
    'Semanas de gestação' => $crianca['semanas_gestacao'],
    'Tipo de parto' => $rotuloParto[$crianca['tipo_parto']] ?? null,
    'Peso ao nascer' => $crianca['peso_nascimento_g'] !== null
        ? number_format((int)$crianca['peso_nascimento_g'] / 1000, 3, ',', '.') . ' kg' : null,
    'Comprimento' => $crianca['comprimento_nascimento_mm'] !== null
        ? number_format((int)$crianca['comprimento_nascimento_mm'] / 10, 1, ',', '.') . ' cm' : null,
    'Perímetro cefálico' => $crianca['perimetro_cefalico_nascimento_mm'] !== null
        ? number_format((int)$crianca['perimetro_cefalico_nascimento_mm'] / 10, 1, ',', '.') . ' cm' : null,
    'Convênio' => $crianca['convenio_nome'],
], $temValor);
?>
<?php if ($nascimento !== []): ?>
    <div class="cartao">
        <h3><?= icone_ui('estrela', 18, '#3E6A64') ?> Nascimento</h3>
        <?php foreach ($nascimento as $rotulo => $valor): ?>
            <p><strong><?= e($rotulo) ?>:</strong> <?= e((string)$valor) ?></p>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php $comMedida = array_values(array_filter($historico, static fn(array $m): bool => $m['status'] === 'confirmada')); ?>
<?php if ($comMedida !== []): ?>
    <div class="cartao">
        <h3><?= icone_ui('grafico', 18, '#3E6A64') ?> Últimas medições</h3>
        <div class="tabela-rolavel"><table class="tabela tabela-compacta">
            <thead><tr><th>Data</th><th>Peso</th><th>Altura</th><th>PC</th><th>Origem</th></tr></thead>
            <tbody>
            <?php foreach (array_slice($comMedida, 0, 8) as $medicao): ?>
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
                    <td><?= e($medicao['origem'] === 'pediatra' ? 'consultório' : 'família') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
    </div>
<?php endif; ?>

<?php if ($vacinas !== []): ?>
    <div class="cartao">
        <h3><?= icone_ui('vacina', 18, '#3E6A64') ?> Vacinas registradas</h3>
        <div class="tabela-rolavel"><table class="tabela tabela-compacta">
            <thead><tr><th>Imunizante</th><th>Dose</th><th>Data</th></tr></thead>
            <tbody>
            <?php foreach ($vacinas as $vacina): ?>
                <tr>
                    <td><?= e($vacina['imunizante']) ?></td>
                    <td><?= e($vacina['dose']) ?></td>
                    <td><?= $vacina['aplicada_em'] !== null ? e(data_br($vacina['aplicada_em'] . ' 00:00:00', 'd/m/y')) : '—' ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
    </div>
<?php endif; ?>

<div class="cartao">
    <h3><?= icone_ui('rotina', 18, '#3E6A64') ?> Rotina — últimos 30 dias</h3>
    <?php if ((int)$resumo['dias_com_registro'] === 0): ?>
        <p class="texto-apoio">Ainda sem registros de rotina no período.</p>
    <?php else: ?>
        <p class="texto-apoio" style="margin-top:0"><?= (int)$resumo['dias_com_registro'] ?> dia(s) com registro</p>
        <div class="tiles-dia">
            <div class="tile-dia">
                <span class="tile-numero"><?= e(number_format((float)$resumo['mamadas_dia'], 1, ',', '')) ?></span>
                <span class="tile-rotulo">mamadas/dia</span>
            </div>
            <div class="tile-dia">
                <span class="tile-numero"><?= (int)round((int)$resumo['sono_medio_min'] / 60) ?>h</span>
                <span class="tile-rotulo">sono/dia (aprox.)</span>
            </div>
            <div class="tile-dia">
                <span class="tile-numero"><?= e(number_format((float)$resumo['fraldas_dia'], 1, ',', '')) ?></span>
                <span class="tile-rotulo">fraldas/dia</span>
            </div>
        </div>
        <?php if ($resumo['sintomas'] !== []): ?>
            <p><strong>Sintomas relatados:</strong>
                <?php $partes = [];
                foreach ($resumo['sintomas'] as $tipo => $vezes) {
                    $partes[] = e(str_replace('_', ' ', (string)$tipo)) . ' (' . (int)$vezes . '×)';
                } ?>
                <?= implode(' · ', $partes) ?></p>
        <?php endif; ?>
        <?php if ($resumo['intercorrencias'] !== []): ?>
            <p style="margin-bottom:.3rem"><strong>Intercorrências no período:</strong></p>
            <?php foreach (array_slice($resumo['intercorrencias'], 0, 5) as $intercorrencia): ?>
                <p class="texto-apoio" style="margin:.15rem 0">
                    <?= e(data_br($intercorrencia['ocorrido_em'], 'd/m H:i')) ?> —
                    <?= e(ucfirst((string)$intercorrencia['gravidade'])) ?>:
                    <?= e(mb_substr((string)$intercorrencia['descricao'], 0, 120)) ?>
                </p>
            <?php endforeach; ?>
        <?php endif; ?>
    <?php endif; ?>
</div>

<div class="cartao cartao-envio-pediatra" id="envio">
    <h3><?= icone_ui('estetoscopio', 18, '#3E6A64') ?> Registrar a consulta de hoje</h3>
    <p class="texto-apoio">Preencha o que houver — só o seu nome é obrigatório.
        Ao enviar, este link se encerra e a família recebe os dados para confirmar.</p>

    <form class="formulario" data-envio-consulta
          data-endpoint="<?= e(url('api.consulta.enviar', ['codigo' => $codigo])) ?>">
        <div class="linha-campos">
            <div>
                <label for="profissional_nome">Seu nome *</label>
                <input type="text" id="profissional_nome" name="profissional_nome" required maxlength="120"
                       placeholder="Dra. Ana Souza">
            </div>
            <div>
                <label for="medido_em">Data da consulta</label>
                <input type="date" id="medido_em" name="medido_em" value="<?= e(hoje()) ?>">
            </div>
        </div>
        <div class="linha-campos">
            <div>
                <label for="conselho_sigla">Conselho</label>
                <input type="text" id="conselho_sigla" name="conselho_sigla" maxlength="10" placeholder="CRM">
            </div>
            <div>
                <label for="conselho_numero">Número</label>
                <input type="text" id="conselho_numero" name="conselho_numero" maxlength="20" placeholder="123456">
            </div>
            <div>
                <label for="conselho_uf">UF</label>
                <input type="text" id="conselho_uf" name="conselho_uf" maxlength="2" placeholder="SP">
            </div>
        </div>

        <div class="linha-campos">
            <div>
                <label for="peso_kg">Peso (kg)</label>
                <input type="number" id="peso_kg" name="peso_kg" min="0.3" max="60" step="0.001" placeholder="7,450">
            </div>
            <div>
                <label for="altura_cm">Altura (cm)</label>
                <input type="number" id="altura_cm" name="altura_cm" min="20" max="160" step="0.1" placeholder="68,5">
            </div>
            <div>
                <label for="pc_cm">Perím. cefálico (cm)</label>
                <input type="number" id="pc_cm" name="pc_cm" min="20" max="70" step="0.1" placeholder="43,0">
            </div>
        </div>

        <label for="vacinas">Vacinas aplicadas hoje <small>(uma por linha: imunizante — dose)</small></label>
        <textarea id="vacinas" name="vacinas" rows="2" placeholder="Pentavalente — 2ª dose&#10;VIP — 2ª dose"></textarea>

        <label for="motivo">Motivo da consulta</label>
        <input type="text" id="motivo" name="motivo" maxlength="200" placeholder="Puericultura de rotina">
        <label for="conduta">Conduta / orientações</label>
        <textarea id="conduta" name="conduta" rows="3"></textarea>
        <div class="linha-campos">
            <div>
                <label for="retorno_em">Retorno sugerido</label>
                <input type="date" id="retorno_em" name="retorno_em">
            </div>
        </div>

        <div class="alerta alerta-erro" data-erro-envio hidden></div>
        <button type="submit" class="botao botao-primario botao-largo">Enviar para a família</button>
    </form>

    <div class="cartao-envio-ok" data-envio-ok hidden>
        <span class="selo-categoria selo-envio-ok"><?= icone_ui('check', 24, '#2F6B4F', 2.4) ?></span>
        <h3>Enviado!</h3>
        <p class="texto-apoio">A família recebeu os dados e confirma no aplicativo.
            Este link foi encerrado — obrigado!</p>
    </div>
</div>

<script src="<?= e(asset('js/consulta.js')) ?>" defer></script>
