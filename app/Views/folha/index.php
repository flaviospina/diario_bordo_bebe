<?php
use App\Services\ServicoFolha;
/** @var array $colaboradora */
/** @var ?array $ultimo */
/** @var ?array $resumo */
/** @var ?array $ultimoVale */
/** @var array $porTipo */
/** @var array $historico */
$meses = ['01' => 'Janeiro', '02' => 'Fevereiro', '03' => 'Março', '04' => 'Abril', '05' => 'Maio', '06' => 'Junho',
    '07' => 'Julho', '08' => 'Agosto', '09' => 'Setembro', '10' => 'Outubro', '11' => 'Novembro', '12' => 'Dezembro'];
?>
<div class="portal-cabeca">
    <h2>Meus Pagamentos</h2>
    <p class="texto-apoio">Consulte seus contracheques, benefícios e documentos trabalhistas.</p>
</div>

<section class="cartao resumo-financeiro" aria-label="Resumo financeiro">
    <?php if ($ultimo === null): ?>
        <h3>Seu contracheque ainda não está disponível</h3>
        <p class="texto-apoio" style="margin:0">Assim que for publicado, você poderá consultá-lo nesta área.</p>
    <?php else: ?>
        <div class="resumo-topo">
            <div>
                <span class="texto-apoio">Última competência publicada</span>
                <h3 style="margin:0"><?= e(ServicoFolha::rotuloCompetencia($ultimo['competencia'])) ?></h3>
            </div>
            <span class="chip-estado estado-disponivel">Disponível</span>
        </div>
        <dl class="resumo-grade">
            <div><dt>Salário líquido demonstrado</dt><dd><?= $resumo !== null && $resumo['salario_liquido'] !== null ? e(ServicoFolha::dinheiro($resumo['salario_liquido'])) : 'Informações não disponíveis' ?></dd></div>
            <div><dt>Data do pagamento</dt><dd><?= ($resumo['data_pagamento'] ?? $ultimo['data_pagamento']) !== null ? e(data_br(($resumo['data_pagamento'] ?? $ultimo['data_pagamento']) . ' 00:00:00', 'd/m/Y')) : 'não informada' ?></dd></div>
            <div><dt>Horas extras</dt><dd><?= $resumo !== null && $resumo['horas_extras_valor'] !== null ? e(ServicoFolha::dinheiro($resumo['horas_extras_valor'])) . ($resumo['horas_extras_qtd'] !== null ? ' (' . e(number_format((float)$resumo['horas_extras_qtd'], 1, ',', '.')) . ' h)' : '') : 'não informadas' ?></dd></div>
            <div><dt>Vale-transporte</dt><dd><?= $ultimoVale !== null ? e(ServicoFolha::dinheiro($ultimoVale['valor_total'])) . ' · ' . e(ServicoFolha::rotuloCompetencia($ultimoVale['mes_referencia'], true)) : 'não informado' ?></dd></div>
        </dl>
        <a class="botao botao-primario" href="<?= e(url('portal.documento', ['codigo' => $ultimo['codigo_publico']])) ?>">Abrir contracheque</a>
    <?php endif; ?>
</section>

<div class="portal-cartoes">
    <a class="cartao cartao-link" href="<?= e(url('portal.documentos')) ?>?tipo=contracheque">
        <h3><?= icone_ui('documento', 18, '#5A539E') ?> Contracheques</h3>
        <p class="texto-apoio"><?= (int)$porTipo['contracheque'] ?> disponível(is) · vencimentos, descontos e líquido · PDF</p>
    </a>
    <a class="cartao cartao-link" href="<?= e(url('portal.vt')) ?>">
        <h3><?= icone_ui('carrinho', 18, '#5A539E') ?> Vale-transporte</h3>
        <p class="texto-apoio">Valores antecipados, mês de referência e comprovantes</p>
    </a>
    <a class="cartao cartao-link" href="<?= e(url('portal.documentos')) ?>?tipo=horas_extras">
        <h3><?= icone_ui('relogio', 18, '#5A539E') ?> Horas extras</h3>
        <p class="texto-apoio"><?= (int)$porTipo['horas_extras'] ?> documento(s) · valores informados nos documentos</p>
    </a>
    <a class="cartao cartao-link" href="<?= e(url('portal.documentos')) ?>?tipo=ferias">
        <h3><?= icone_ui('sol-nuvem', 18, '#5A539E') ?> Férias e 13º</h3>
        <p class="texto-apoio"><?= (int)$porTipo['ferias'] + (int)$porTipo['decimo_terceiro'] ?> documento(s) · períodos e pagamentos</p>
    </a>
    <a class="cartao cartao-link" href="<?= e(url('portal.documentos')) ?>">
        <h3><?= icone_ui('lupa', 18, '#5A539E') ?> Meus documentos</h3>
        <p class="texto-apoio"><?= array_sum($porTipo) ?> no total · filtros por tipo e competência</p>
    </a>
</div>

<?php if ($historico !== []): ?>
    <section class="cartao" aria-label="Histórico mensal">
        <h3>Histórico mensal</h3>
        <?php foreach ($historico as $ano => $mesesDoAno): ?>
            <p class="grupo-folha"><?= e((string)$ano) ?></p>
            <ul class="historico-mensal">
                <?php foreach ($mesesDoAno as $competencia => $docs): ?>
                    <li>
                        <strong><?= e($meses[substr($competencia, 5, 2)] ?? $competencia) ?></strong>
                        <?php if ($docs === []): ?>
                            <span class="texto-apoio">— aguardando publicação</span>
                        <?php else: ?>
                            <span class="historico-docs">
                                <?php foreach ($docs as $d): ?>
                                    <a href="<?= e(url('portal.documento', ['codigo' => $d['codigo_publico']])) ?>"><?= e(ServicoFolha::TIPOS[$d['tipo']] ?? $d['tipo']) ?></a>
                                <?php endforeach; ?>
                            </span>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endforeach; ?>
    </section>
<?php endif; ?>
<p class="texto-apoio portal-nota">Os documentos oficiais são emitidos pelo eSocial Doméstico; aqui você os consulta e baixa com segurança.
    Abrir ou baixar um documento é registrado como "documento acessado" — não é confirmação de leitura nem de concordância.</p>
