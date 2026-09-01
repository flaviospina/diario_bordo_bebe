<?php

declare(strict_types=1);

/**
 * Importação única dos documentos físicos do Arthur (Alteração 01):
 *   - Alta médica do Hospital Albert Einstein (nascimento, 28/04/2026)
 *   - Caderneta da clínica (consultas de 14 dias, 1, 2 e 3 meses)
 *   - Páginas do caderno da babá (rotinas de 16/08 a 01/09/2026)
 *
 * Uso (sem terminal na hospedagem): /install/importar_caderno.php?token=MIGRATE_TOKEN
 *
 * É IDEMPOTENTE: pode rodar mais de uma vez sem duplicar nada —
 *   - dados da criança: só preenche campos que estiverem vazios;
 *   - medições/consultas: pula datas que já existem;
 *   - vacinas: pula imunizante+dose já registrados;
 *   - registros de rotina: uuid determinístico (duplicata é ignorada).
 *
 * Depois de importar e conferir, APAGUE este arquivo do servidor.
 */

define('RAIZ_PROJETO', dirname(__DIR__));

require RAIZ_PROJETO . '/app/Core/Autoloader.php';
\App\Core\Autoloader::registrar();
require RAIZ_PROJETO . '/app/Helpers/funcoes.php';

use App\Core\Ambiente;
use App\Core\BancoDados;
use App\Core\Identificadores;
use App\Repositories\RepositorioCriancas;
use App\Repositories\RepositorioMedicoes;
use App\Repositories\RepositorioRegistros;
use App\Repositories\RepositorioVacinas;
use App\Services\ServicoCrescimento;

Ambiente::carregar();
date_default_timezone_set(Ambiente::obter('APP_FUSO', 'America/Sao_Paulo'));
mb_internal_encoding('UTF-8');

header('Content-Type: text/html; charset=utf-8');
header('X-Robots-Tag: noindex, nofollow');

$tokenEsperado = Ambiente::obter('MIGRATE_TOKEN');
$tokenRecebido = (string)($_GET['token'] ?? $_POST['token'] ?? '');
if ($tokenEsperado === '' || strlen($tokenEsperado) < 20 || !hash_equals($tokenEsperado, $tokenRecebido)) {
    http_response_code(403);
    exit('<h1>403</h1><p>Token ausente ou inválido. Use ?token=MIGRATE_TOKEN (o mesmo do .env).</p>');
}

$esc = static fn(?string $t): string => htmlspecialchars((string)$t, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$acoes = [];
$avisos = [];

$bd = BancoDados::conexao();

// ── Família, autor (admin) e cuidadora ────────────────────────
$admin = $bd->query(
    "SELECT id, familia_id FROM usuarios
      WHERE email = 'prof.flavio.spina@gmail.com' AND papel IN ('admin_familia','responsavel') LIMIT 1"
)->fetch() ?: $bd->query(
    "SELECT id, familia_id FROM usuarios WHERE papel = 'admin_familia' ORDER BY id LIMIT 1"
)->fetch();
if ($admin === false || $admin === null) {
    exit('<h1>Erro</h1><p>Nenhum admin de família encontrado — rode o migrate.php primeiro.</p>');
}
$familiaId = (int)$admin['familia_id'];
$adminId = (int)$admin['id'];

$cuidador = $bd->prepare("SELECT id FROM usuarios WHERE familia_id = ? AND papel = 'cuidador' AND ativo = 1 ORDER BY id LIMIT 1");
$cuidador->execute([$familiaId]);
$cuidadorId = (int)($cuidador->fetchColumn() ?: $adminId);

// ── Criança: Arthur Daniel ────────────────────────────────────
$criancas = new RepositorioCriancas($familiaId);
$busca = $bd->prepare("SELECT * FROM criancas WHERE familia_id = ? AND nome LIKE 'Arthur%' LIMIT 1");
$busca->execute([$familiaId]);
$crianca = $busca->fetch() ?: null;

if ($crianca === null) {
    $criancaId = $criancas->criar('Arthur Daniel Brommonschenkel Spina', [
        'apelido' => 'Arthur',
        'data_nascimento' => '2026-04-28',
        'sexo' => 'masculino',
    ]);
    $crianca = $criancas->buscarPorId($criancaId);
    $acoes[] = 'Criança criada: Arthur Daniel Brommonschenkel Spina';
} else {
    $criancaId = (int)$crianca['id'];
    $acoes[] = 'Criança encontrada: ' . $crianca['nome'] . ' (nada foi renomeado)';
}

// Preenche SOMENTE campos vazios (nunca sobrescreve o que você já digitou)
$vazio = static fn($v): bool => $v === null || trim((string)$v) === '';
$manter = static fn($atual, $novo) => $vazio($atual) ? $novo : $atual;

$criancas->atualizar($criancaId, [
    'nome' => $crianca['nome'],
    'apelido' => $manter($crianca['apelido'], 'Arthur'),
    'data_nascimento' => $manter($crianca['data_nascimento'], '2026-04-28'),
    'sexo' => $manter($crianca['sexo'], 'masculino'),
    'tipo_sanguineo' => $manter($crianca['tipo_sanguineo'], 'B+'),
    'alergias' => $manter($crianca['alergias'],
        'APLV — alergia à proteína do leite de vaca (orientada dieta com Pregomin na consulta de 1 mês)'),
    'condicoes_saude' => $manter($crianca['condicoes_saude'],
        'Icterícia zona II ao nascer (fototerapia por 1 dia, resolvida); episódio isolado de hipoglicemia no berçário, resolvido com aleitamento'),
    'medicacoes_continuas' => $manter($crianca['medicacoes_continuas'],
        'Vitamina + Luftal (rotina diária); Camomilina C (orientação da pediatra em 31/08)'),
    'pediatra_nome' => $crianca['pediatra_nome'],
    'pediatra_telefone' => $crianca['pediatra_telefone'],
    'ativo' => (int)($crianca['ativo'] ?? 1),
]);
$criancas->atualizarFichaEssencial($criancaId, [
    'semanas_gestacao' => $vazio($crianca['semanas_gestacao'] ?? null) ? 38 : (int)$crianca['semanas_gestacao'],
    'peso_nascimento_g' => $vazio($crianca['peso_nascimento_g'] ?? null) ? 3365 : (int)$crianca['peso_nascimento_g'],
    'comprimento_nascimento_mm' => $vazio($crianca['comprimento_nascimento_mm'] ?? null) ? 510 : (int)$crianca['comprimento_nascimento_mm'],
    'perimetro_cefalico_nascimento_mm' => $vazio($crianca['perimetro_cefalico_nascimento_mm'] ?? null) ? 365 : (int)$crianca['perimetro_cefalico_nascimento_mm'],
    'tipo_parto' => $manter($crianca['tipo_parto'] ?? null, 'cesarea'),
    'convenio_nome' => $crianca['convenio_nome'] ?? null,
    'convenio_carteirinha' => $crianca['convenio_carteirinha'] ?? null,
    'hospital_referencia' => $manter($crianca['hospital_referencia'] ?? null,
        'Hospital Israelita Albert Einstein — Morumbi/SP'),
    'restricoes_alimentares' => $manter($crianca['restricoes_alimentares'] ?? null,
        'Dieta sem proteína do leite de vaca (APLV) — fórmula Pregomin'),
]);
$crianca = $criancas->buscarPorId($criancaId);
$acoes[] = 'Dados de nascimento/saúde preenchidos (38s, cesárea, 3.365 g, 51 cm, PC 36,5 cm, TS B+, APLV, Einstein)';

// ── Medições (alta médica + caderneta) — percentis congelados ─
// Fonte: nascimento e alta (Einstein) e consultas da caderneta da clínica.
$medicoesDados = [
    ['2026-04-28', 3365, 510, 365, 'Nascimento — Hospital Albert Einstein (IG 38s5d, Apgar 9/10)', 'Equipe Hospital Albert Einstein'],
    ['2026-05-01', 3080, null, null, 'Peso da alta hospitalar (variação de −8,5%)', 'Equipe Hospital Albert Einstein'],
    ['2026-05-12', 3280, 525, null, 'Retorno de 14 dias — BCG ok', 'Pediatra (caderneta da clínica)'],
    ['2026-06-03', 4400, 565, 385, 'Consulta de 1 mês — ganho de 53 g/dia', 'Pediatra (caderneta da clínica)'],
    ['2026-07-01', 5550, 595, 410, 'Consulta de 2 meses — ganho de 41 g/dia', 'Pediatra (caderneta da clínica)'],
    ['2026-07-29', 6380, 620, 420, 'Consulta de 3 meses — ganho de 30 g/dia', 'Pediatra (caderneta da clínica)'],
];
$medicoes = new RepositorioMedicoes($familiaId);
$crescimento = new ServicoCrescimento();
$existeMedicao = $bd->prepare('SELECT COUNT(*) FROM medicoes WHERE familia_id = ? AND crianca_id = ? AND medido_em = ?');
$importadas = 0;
foreach ($medicoesDados as [$data, $pesoG, $alturaMm, $pcMm, $obs, $profissional]) {
    $existeMedicao->execute([$familiaId, $criancaId, $data]);
    if ((int)$existeMedicao->fetchColumn() > 0) {
        continue;
    }
    $percentis = [];
    $idadeMeses = ServicoCrescimento::idadeEmMeses('2026-04-28', $data);
    foreach ([['peso', $pesoG, 1000], ['altura', $alturaMm, 10], ['pc', $pcMm, 10]] as [$tipo, $valor, $fator]) {
        if ($valor === null) {
            continue;
        }
        $resultado = $crescimento->avaliar($tipo, 'masculino', $idadeMeses, $valor / $fator);
        if ($resultado !== null) {
            $percentis['percentil_' . $tipo] = $resultado['percentil'];
            $percentis['escore_z_' . $tipo] = $resultado['z'];
        }
    }
    $medicoes->criar([
        'crianca_id' => $criancaId,
        'medido_em' => $data,
        'peso_g' => $pesoG,
        'altura_mm' => $alturaMm,
        'perimetro_cefalico_mm' => $pcMm,
        'origem' => 'pediatra',
        'registrado_por_usuario_id' => $adminId,
        'profissional_nome_livre' => $profissional,
        'observacao' => $obs,
        'status' => 'confirmada',
    ] + $percentis);
    $importadas++;
}
$acoes[] = "Medições importadas: {$importadas} de " . count($medicoesDados) . ' (as demais já existiam)';

// ── Vacina: BCG (anotada "BCG OK" no retorno de 14 dias) ──────
$vacinas = new RepositorioVacinas($familiaId);
$temBcg = $bd->prepare("SELECT COUNT(*) FROM vacinas WHERE familia_id = ? AND crianca_id = ? AND imunizante LIKE 'BCG%'");
$temBcg->execute([$familiaId, $criancaId]);
if ((int)$temBcg->fetchColumn() === 0) {
    $vacinas->criar([
        'crianca_id' => $criancaId,
        'imunizante' => 'BCG',
        'dose' => 'Dose única',
        'aplicada_em' => null,
        'origem' => 'pais',
        'status' => 'aplicada',
        'observacao' => "Anotado 'BCG OK' na consulta de 14 dias (12/05/26); data exata da aplicação não informada",
    ]);
    $acoes[] = 'Vacina BCG registrada (dose única, data exata não informada)';
} else {
    $acoes[] = 'Vacina BCG já existia — mantida';
}
$avisos[] = 'As caderneta anota "vacinas" nas consultas de 2 e 3 meses, mas sem detalhar imunizantes/datas. '
    . 'Registre essas doses na caderneta digital (ficha do Arthur → Caderneta completa) conferindo o cartão de vacinas.';

// ── Consultas da caderneta ────────────────────────────────────
$consultasDados = [
    ['2026-05-12', 'Retorno de recém-nascido — 14 dias', 'Peso 3.280 g, estatura 52,5 cm. BCG ok.'],
    ['2026-06-03', 'Puericultura — 1 mês', 'Ganho de 53 g/dia. APLV: orientada dieta com fórmula Pregomin. Vacinas dos 2 meses orientadas.'],
    ['2026-07-01', 'Puericultura — 2 meses', 'Ganho de 41 g/dia. Estímulos; exercícios para o lado esquerdo. Orientações de vacinas e sono.'],
    ['2026-07-29', 'Puericultura — 3 meses', 'Ganho de 30 g/dia. Rotinas e sono; indicação de osteopata (Gabi); exercícios lado esquerdo; vacinas; susp. cólicas (conforme caderneta).'],
];
$existeConsulta = $bd->prepare('SELECT COUNT(*) FROM consultas WHERE familia_id = ? AND crianca_id = ? AND realizada_em = ?');
$novasConsultas = 0;
$inserirConsulta = $bd->prepare(
    "INSERT INTO consultas (familia_id, crianca_id, realizada_em, motivo, conduta, origem)
     VALUES (?, ?, ?, ?, ?, 'pais')"
);
foreach ($consultasDados as [$data, $motivo, $conduta]) {
    $existeConsulta->execute([$familiaId, $criancaId, $data]);
    if ((int)$existeConsulta->fetchColumn() > 0) {
        continue;
    }
    $inserirConsulta->execute([$familiaId, $criancaId, $data, $motivo, $conduta]);
    $novasConsultas++;
}
$acoes[] = "Consultas importadas: {$novasConsultas} de " . count($consultasDados);

// ── Rotinas do caderno da babá (16/08 a 01/09/2026) ───────────
// Formato: [data, hora, hora_fim|null, slug, observação, dados|null]
// Convenção do caderno: "H:00 → H:MM evento" = evento aconteceu às H:MM.
$rotina = [
    // 16/08/26
    ['2026-08-16', '10:00', null, 'amamentacao', 'Mamou e dormiu em seguida', null],
    ['2026-08-16', '10:30', null, 'soneca', 'Dormiu após a mamada (horário aproximado)', ['como_adormeceu' => 'mamando']],
    ['2026-08-16', '11:00', null, 'passeio', 'Brincou; passeio pelo quarto', null],
    ['2026-08-16', '13:00', null, 'amamentacao', 'Mamou', null],
    ['2026-08-16', '14:30', null, 'soneca', 'Dormiu', null],
    // 17/08/26
    ['2026-08-17', '06:30', null, 'despertar', 'Acordou 6h30', null],
    ['2026-08-17', '07:00', null, 'fralda', 'Troca de fralda; diálogo', ['conteudo' => 'xixi']],
    ['2026-08-17', '08:20', null, 'amamentacao', 'Mamou 8h20', null],
    ['2026-08-17', '09:00', null, 'soneca', 'Dormiu até cerca de 9h30 (anotação pouco legível)', null],
    ['2026-08-17', '10:00', null, 'passeio', 'Passeio externo; louvores', null],
    ['2026-08-17', '10:30', null, 'amamentacao', 'Mamou 10h30', null],
    ['2026-08-17', '11:00', null, 'passeio', 'Brincou; passeio de carrinho', null],
    ['2026-08-17', '12:00', null, 'soneca', 'Dormiu', null],
    ['2026-08-17', '13:00', null, 'mamadeira', 'Mamou 80 ml', ['volume_ml' => 80, 'tipo' => 'formula']],
    ['2026-08-17', '14:00', null, 'medicacao', 'Luftal', ['nome' => 'Luftal', 'dose' => 'conforme rotina', 'via' => 'oral']],
    ['2026-08-17', '14:15', null, 'amamentacao', 'Mamou com a mamãe (horário aproximado)', null],
    ['2026-08-17', '15:00', null, 'soneca', 'Dormiu', null],
    ['2026-08-17', '17:00', null, 'tummy-time', 'Tummy time e exercícios', null],
    ['2026-08-17', '17:30', null, 'banho', 'Banho (horário aproximado)', null],
    ['2026-08-17', '18:30', '2026-08-18 05:30', 'sono-noturno', 'Dormiu 18h30; acordou 23h para mamar; dormiu até 5h30', null],
    ['2026-08-17', '23:00', null, 'amamentacao', 'Mamada noturna (23h)', null],
    // 19/08/26
    ['2026-08-19', '07:00', null, 'fralda', 'Troca de fralda e roupa', ['conteudo' => 'xixi']],
    ['2026-08-19', '08:00', null, 'amamentacao', 'Conversa, brincou e mamou', null],
    ['2026-08-19', '09:00', null, 'amamentacao', 'Mamou pouco', null],
    ['2026-08-19', '09:50', null, 'soneca', 'Dormiu 9h50', null],
    ['2026-08-19', '11:00', null, 'banho-de-sol', 'Tomou sol', null],
    ['2026-08-19', '12:00', null, 'mamadeira', 'Mamou mamadeira 90 ml', ['volume_ml' => 90, 'tipo' => 'formula']],
    ['2026-08-19', '13:00', null, 'amamentacao', 'Mamou e dormiu no colo', null],
    ['2026-08-19', '14:00', null, 'fralda', 'Fez cocô', ['conteudo' => 'coco']],
    ['2026-08-19', '14:20', null, 'estimulo', 'Fez exercícios', null],
    ['2026-08-19', '15:00', null, 'soneca', 'Dormiu no berço', ['onde_dormiu' => 'berco']],
    // 20/08/26
    ['2026-08-20', '12:30', null, 'amamentacao', 'Mamou 12h30', null],
    ['2026-08-20', '14:00', null, 'soneca', 'Dormiu', null],
    ['2026-08-20', '18:20', null, 'banho', 'Banho 18h20', null],
    // 24/08/26
    ['2026-08-24', '08:00', null, 'despertar', 'Acordou e trocou fraldas', null],
    ['2026-08-24', '09:20', null, 'troca-roupa', 'Troca de roupa e passeio (9h20)', null],
    // 25/08/26
    ['2026-08-25', '06:00', null, 'despertar', 'Acordou, mamou e trocou fralda', null],
    ['2026-08-25', '06:10', null, 'amamentacao', 'Mamada ao acordar', null],
    ['2026-08-25', '07:15', null, 'soneca', 'Voltou a dormir 7h15 (anotação rasurada no caderno)', null],
    ['2026-08-25', '09:00', null, 'amamentacao', 'Acordou e mamou', null],
    ['2026-08-25', '09:30', null, 'banho-de-sol', 'Sol e brincadeira (9h30)', null],
    ['2026-08-25', '10:00', null, 'estimulo', 'Exercícios no tapete; carrinho pela casa', null],
    ['2026-08-25', '11:00', null, 'soneca', 'Dormiu 30 min', null],
    ['2026-08-25', '11:30', null, 'mamadeira', 'Mamou 30 ml', ['volume_ml' => 30, 'tipo' => 'formula']],
    ['2026-08-25', '12:00', null, 'amamentacao', 'Mamou; exercícios no tapete', null],
    ['2026-08-25', '13:30', null, 'soneca', 'Dormiu 13h30', null],
    // 28/08/26
    ['2026-08-28', '06:00', null, 'fralda', 'Acordou; troca com xixi', ['conteudo' => 'xixi']],
    ['2026-08-28', '07:00', null, 'musica', 'Diálogo, oração e hinos', null],
    ['2026-08-28', '08:00', null, 'fralda', 'Troca com cocô', ['conteudo' => 'coco']],
    ['2026-08-28', '08:15', null, 'medicacao', 'Tomou vitamina e Luftal', ['nome' => 'Vitamina + Luftal', 'dose' => 'conforme rotina', 'via' => 'oral']],
    ['2026-08-28', '09:20', null, 'soneca', 'Dormiu 9h20', null],
    ['2026-08-28', '11:28', null, 'amamentacao', 'Mamou 11h28', null],
    ['2026-08-28', '12:46', null, 'amamentacao', 'Mamou 12h46', null],
    ['2026-08-28', '13:00', null, 'passeio', 'Brincou; passeio no quintal', null],
    ['2026-08-28', '14:00', '2026-08-28 15:00', 'soneca', 'Dormiu 14h; acordou 15h', null],
    ['2026-08-28', '16:20', null, 'amamentacao', 'Mamou 16h20', null],
    // 31/08/26 — "Semana das Bênçãos"
    ['2026-08-31', '06:40', null, 'despertar', 'Acordou 6h40', null],
    ['2026-08-31', '07:00', null, 'medicacao', 'Vitamina + Luftal', ['nome' => 'Vitamina + Luftal', 'dose' => 'conforme rotina', 'via' => 'oral']],
    ['2026-08-31', '07:10', null, 'fralda', 'Fez cocô', ['conteudo' => 'coco']],
    ['2026-08-31', '08:00', null, 'amamentacao', 'Diálogo, troca e mamou', null],
    ['2026-08-31', '09:40', null, 'soneca', 'Dormiu 9h40 (seguia na soneca às 10h)', null],
    ['2026-08-31', '11:50', null, 'amamentacao', 'Mamou até 11h50', null],
    ['2026-08-31', '12:35', null, 'soneca', 'Dormiu 12h35 (seguia dormindo às 14h)', null],
    ['2026-08-31', '16:00', null, 'banho', 'Banho, mamadas e brincadeiras', null],
    ['2026-08-31', '17:20', null, 'amamentacao', 'Mamá + soneca (17h20)', null],
    // 01/09/26
    ['2026-09-01', '09:00', null, 'estimulo', 'Atividades no tapete', null],
    ['2026-09-01', '10:40', '2026-09-01 12:15', 'soneca', 'Soneca 10h40; acordou 12h15', null],
    ['2026-09-01', '10:40', null, 'mamadeira', 'Mamadeira junto da soneca (volume não anotado)', null],
    ['2026-09-01', '17:20', null, 'banho', 'Banho 17h20', null],
];

$categorias = [];
foreach ($bd->query('SELECT id, slug FROM categorias')->fetchAll() as $categoria) {
    $categorias[$categoria['slug']] = (int)$categoria['id'];
}
$registros = new RepositorioRegistros($familiaId);
$novosRegistros = 0;
$pulados = 0;
foreach ($rotina as [$data, $hora, $fim, $slug, $obs, $dados]) {
    if (!isset($categorias[$slug])) {
        $avisos[] = "Categoria '{$slug}' não encontrada — evento {$data} {$hora} não importado.";
        continue;
    }
    // uuid determinístico: rodar de novo não duplica
    $hash = md5("importacao-caderno|{$criancaId}|{$data}|{$hora}|{$slug}");
    $uuid = sprintf(
        '%s-%s-%s-%s-%s',
        substr($hash, 0, 8), substr($hash, 8, 4), substr($hash, 12, 4),
        substr($hash, 16, 4), substr($hash, 20, 12)
    );
    $resultado = $registros->criar([
        'uuid_cliente' => $uuid,
        'crianca_id' => $criancaId,
        'categoria_id' => $categorias[$slug],
        'usuario_id' => $cuidadorId,
        'inicio' => $data . ' ' . $hora . ':00',
        'fim' => $fim !== null ? $fim . ':00' : null,
        'dados' => $dados,
        'observacao' => $obs . ' [importado do caderno físico]',
        'status' => 'feito',
        'origem' => 'offline',
    ]);
    $resultado['duplicado'] ? $pulados++ : $novosRegistros++;
}
$acoes[] = "Rotinas do caderno: {$novosRegistros} registro(s) importado(s), {$pulados} já existiam";

$bd->prepare(
    "INSERT INTO log_acessos (familia_id, usuario_id, acao, entidade) VALUES (?, ?, 'importacao_caderno_fisico', 'registros')"
)->execute([$familiaId, $adminId]);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Importação do caderno — Diário do Bebê</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 640px; margin: 2rem auto; padding: 0 1rem; color: #26302f; }
        .ok { background: #e5f4ea; color: #1b6e3c; padding: .6rem .9rem; border-radius: 8px; margin: .4rem 0; }
        .aviso { background: #fdf3dc; color: #7a5b16; padding: .6rem .9rem; border-radius: 8px; margin: .4rem 0; }
        .apagar { background: #fbe9e7; color: #b3261e; padding: .6rem .9rem; border-radius: 8px; margin: 1rem 0; font-weight: 600; }
    </style>
</head>
<body>
<h1>Importação concluída</h1>
<?php foreach ($acoes as $acao): ?>
    <div class="ok"><?= $esc($acao) ?></div>
<?php endforeach; ?>
<?php foreach ($avisos as $aviso): ?>
    <div class="aviso"><?= $esc($aviso) ?></div>
<?php endforeach; ?>
<div class="apagar">Confira a ficha do Arthur no app e depois APAGUE este arquivo
    (<code>install/importar_caderno.php</code>) do servidor.</div>
</body>
</html>
