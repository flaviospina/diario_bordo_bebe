<?php
/** @var string $tipo inexistente|usado|expirado|revogado */

$mensagens = [
    'inexistente' => [
        'Ficha não encontrada',
        'Confira se o link foi digitado por completo — ou peça à família um novo QR code.',
    ],
    'usado' => [
        'Esta ficha já foi usada',
        'Cada link abre uma única vez, por segurança. Peça à família um novo QR code para continuar.',
    ],
    'expirado' => [
        'Este link expirou',
        'O link vale por 48 horas após ser gerado. Peça à família um novo QR code.',
    ],
    'revogado' => [
        'Este link foi cancelado',
        'A família cancelou este link. Peça um novo QR code para acessar a ficha.',
    ],
];
[$tituloErro, $detalhe] = $mensagens[$tipo] ?? $mensagens['inexistente'];
?>
<div class="cartao cartao-erro-publico">
    <span class="selo-categoria selo-erro-publico"><?= icone_ui('documento', 26, '#7D776C') ?></span>
    <h2><?= e($tituloErro) ?></h2>
    <p class="texto-apoio"><?= e($detalhe) ?></p>
</div>
