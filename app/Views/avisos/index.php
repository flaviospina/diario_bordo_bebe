<?php
/** @var array $avisos */
/** @var string $vapidPublica */
/** @var bool $temInscricao */

$iconePorTipo = [
    'registro_ajustado' => 'documento', 'registro_excluido' => 'x', 'registro_pais' => 'saude',
    'solicitacao' => 'balao', 'roteiro' => 'relogio', 'suprimento' => 'cesto',
    'intercorrencia_ciencia' => 'check', 'configuracao' => 'engrenagem',
    'intercorrencia' => 'alerta', 'solicitacao_edicao' => 'balao', 'suprimento_baixo' => 'cesto',
    'medicao_pendente' => 'grafico', 'consulta_link_aberto' => 'estetoscopio',
    'consulta_link_usado' => 'estetoscopio', 'alerta_omissao' => 'relogio',
];
?>
<h2>Avisos</h2>

<div class="cartao cartao-push" data-push-cartao data-vapid="<?= e($vapidPublica) ?>" data-inscrito="<?= $temInscricao ? '1' : '0' ?>">
    <h3><?= icone_ui('balao', 18, '#3E6A64') ?> Notificações neste aparelho</h3>
    <p class="texto-apoio" style="margin-top:0" data-push-texto>
        Receba os avisos na hora, mesmo com o app fechado — como uma mensagem no celular.
    </p>
    <div class="acoes-push">
        <button type="button" class="botao botao-primario" data-push-ativar>Ativar notificações</button>
        <button type="button" class="botao botao-contorno" data-push-testar hidden>Enviar um teste</button>
        <button type="button" class="botao botao-contorno botao-pequeno" data-push-desativar hidden>Desativar neste aparelho</button>
    </div>
    <p class="texto-apoio nota-ios" data-push-ios hidden>
        No iPhone, as notificações só funcionam com o app <strong>adicionado à tela de início</strong>
        (Compartilhar → "Adicionar à Tela de Início") e abrindo o app por esse ícone.
    </p>
</div>

<?php if ($avisos === []): ?>
    <div class="cartao" style="text-align:center; padding:2rem 1rem">
        <?= icone_ui('estrela', 34, '#D9C58A') ?>
        <p class="texto-apoio" style="margin:.5rem 0 0">Nenhum aviso por enquanto. Quando algo mudar na rotina, aparece aqui.</p>
    </div>
<?php else: ?>
    <div class="cartao lista-avisos">
        <?php foreach ($avisos as $aviso): ?>
            <?php $corpo = '<span class="selo-categoria selo-aviso">' . icone_ui($iconePorTipo[$aviso['tipo']] ?? 'estrela', 17, 'currentColor', 2.0) . '</span>'
                . '<div class="aviso-texto"><strong>' . e($aviso['titulo']) . '</strong>'
                . ($aviso['mensagem'] !== null && $aviso['mensagem'] !== '' ? '<p>' . e($aviso['mensagem']) . '</p>' : '')
                . '<span class="texto-apoio">' . e(data_br((string)$aviso['criado_em'], 'd/m H:i')) . '</span></div>'; ?>
            <?php if ($aviso['url'] !== null && $aviso['url'] !== ''): ?>
                <a class="aviso-item <?= $aviso['lido_em'] === null ? 'aviso-novo' : '' ?>" href="<?= e($aviso['url']) ?>"><?= $corpo ?></a>
            <?php else: ?>
                <div class="aviso-item <?= $aviso['lido_em'] === null ? 'aviso-novo' : '' ?>"><?= $corpo ?></div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<script src="<?= e(asset('js/push.js')) ?>" defer></script>
