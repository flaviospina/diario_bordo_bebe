<?php

declare(strict_types=1);

/**
 * Migrações via navegador (não há terminal na hospedagem compartilhada).
 * Protegido por token: /install/migrate.php?token=VALOR_DO_MIGRATE_TOKEN
 *
 * O que faz:
 *  1. Executa as migrações pendentes de /database/migrations (idempotente).
 *  2. Aplica o seed de categorias globais (insere apenas slugs inexistentes).
 *  3. Enquanto não existir nenhuma família, permite criar a família inicial
 *     com o primeiro admin_familia (bootstrap do sistema).
 */

define('RAIZ_PROJETO', dirname(__DIR__));

require RAIZ_PROJETO . '/app/Core/Autoloader.php';
\App\Core\Autoloader::registrar();

use App\Core\Ambiente;
use App\Core\BancoDados;
use App\Core\Identificadores;

Ambiente::carregar();
date_default_timezone_set(Ambiente::obter('APP_FUSO', 'America/Sao_Paulo'));
mb_internal_encoding('UTF-8');

header('Content-Type: text/html; charset=utf-8');
header('X-Robots-Tag: noindex, nofollow');

// ── Autorização por token ─────────────────────────────────────
$tokenEsperado = Ambiente::obter('MIGRATE_TOKEN');
$tokenRecebido = (string)($_GET['token'] ?? $_POST['token'] ?? '');
if ($tokenEsperado === '' || strlen($tokenEsperado) < 20 || !hash_equals($tokenEsperado, $tokenRecebido)) {
    http_response_code(403);
    exit('<h1>403</h1><p>Token ausente ou inválido. Defina um MIGRATE_TOKEN longo no .env e informe ?token=...</p>');
}

$esc = static fn(?string $t): string => htmlspecialchars((string)$t, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$mensagens = [];
$erros = [];

try {
    $bd = BancoDados::conexao();
} catch (Throwable $e) {
    http_response_code(500);
    exit('<h1>Erro de conexão</h1><p>Verifique BD_HOST, BD_NOME, BD_USUARIO e BD_SENHA no .env.</p><pre>'
        . $esc($e->getMessage()) . '</pre>');
}

// ── Controle de migrações ─────────────────────────────────────
$bd->exec("CREATE TABLE IF NOT EXISTS migracoes (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    arquivo VARCHAR(120) NOT NULL,
    executado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_migracoes_arquivo (arquivo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$executadas = $bd->query('SELECT arquivo FROM migracoes')->fetchAll(PDO::FETCH_COLUMN);
$arquivos = glob(RAIZ_PROJETO . '/database/migrations/*.php') ?: [];
sort($arquivos);

foreach ($arquivos as $arquivo) {
    $nome = basename($arquivo);
    if (in_array($nome, $executadas, true)) {
        continue;
    }
    $migracao = require $arquivo;
    try {
        foreach (($migracao['sql'] ?? []) as $sql) {
            $bd->exec($sql);
        }
        $declaracao = $bd->prepare('INSERT INTO migracoes (arquivo) VALUES (?)');
        $declaracao->execute([$nome]);
        $mensagens[] = "Migração executada: {$nome} — " . ($migracao['descricao'] ?? '');
    } catch (Throwable $e) {
        $erros[] = "Falha na migração {$nome}: " . $e->getMessage();
        break; // não segue adiante com o esquema inconsistente
    }
}
if ($mensagens === [] && $erros === []) {
    $mensagens[] = 'Nenhuma migração pendente.';
}

// ── Seed de categorias globais (idempotente por slug) ─────────
if ($erros === []) {
    $categorias = require RAIZ_PROJETO . '/database/seeds/categorias_globais.php';
    $existe = $bd->prepare('SELECT id FROM categorias WHERE familia_id IS NULL AND slug = ? LIMIT 1');
    $inserir = $bd->prepare(
        'INSERT INTO categorias (familia_id, grupo, nome, slug, icone, cor, schema_campos, ordem, ativo)
         VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, 1)'
    );
    $inseridas = 0;
    foreach ($categorias as $categoria) {
        $existe->execute([$categoria['slug']]);
        if ($existe->fetch() !== false) {
            continue;
        }
        $inserir->execute([
            $categoria['grupo'],
            $categoria['nome'],
            $categoria['slug'],
            $categoria['icone'],
            $categoria['cor'],
            json_encode($categoria['schema_campos'], JSON_UNESCAPED_UNICODE),
            $categoria['ordem'],
        ]);
        $inseridas++;
    }
    $mensagens[] = $inseridas > 0
        ? "Seed de categorias: {$inseridas} categoria(s) global(is) inserida(s)."
        : 'Seed de categorias: catálogo global já está completo.';
}

// ── Bootstrap: família inicial + primeiro admin ───────────────
$totalFamilias = $erros === []
    ? (int)$bd->query("SELECT COUNT(*) FROM familias WHERE plano <> 'plataforma'")->fetchColumn()
    : -1;
$totalSuperAdmins = $erros === []
    ? (int)$bd->query("SELECT COUNT(*) FROM usuarios WHERE papel = 'super_admin'")->fetchColumn()
    : -1;

// ── Super admin da plataforma (gestão de famílias/planos) ─────
if ($erros === [] && $totalSuperAdmins === 0 && ($_POST['acao'] ?? '') === 'criar_super_admin') {
    $nomeSuper = trim((string)($_POST['super_nome'] ?? ''));
    $emailSuper = mb_strtolower(trim((string)($_POST['super_email'] ?? '')));
    $senhaSuper = (string)($_POST['super_senha'] ?? '');
    if ($nomeSuper === '' || !filter_var($emailSuper, FILTER_VALIDATE_EMAIL) || mb_strlen($senhaSuper) < 10) {
        $erros[] = 'Preencha nome, e-mail válido e senha (mín. 10 caracteres) do super admin.';
    } else {
        try {
            $bd->beginTransaction();
            // Família técnica da plataforma (nunca aparece nas listagens de tenant)
            $plataformaId = $bd->query("SELECT id FROM familias WHERE plano = 'plataforma' LIMIT 1")->fetchColumn();
            if ($plataformaId === false) {
                $declaracao = $bd->prepare("INSERT INTO familias (codigo_publico, slug, nome, plano) VALUES (?, ?, 'Plataforma', 'plataforma')");
                $declaracao->execute([Identificadores::codigoPublico(), 'plataforma-' . Identificadores::codigoPublico(6)]);
                $plataformaId = (int)$bd->lastInsertId();
            }
            $declaracao = $bd->prepare(
                'INSERT INTO usuarios (familia_id, codigo_publico, nome, email, senha_hash, papel)
                 VALUES (?, ?, ?, ?, ?, \'super_admin\')'
            );
            $declaracao->execute([
                (int)$plataformaId,
                Identificadores::codigoPublico(),
                $nomeSuper,
                $emailSuper,
                password_hash($senhaSuper, PASSWORD_ARGON2ID),
            ]);
            $bd->commit();
            $totalSuperAdmins = 1;
            $mensagens[] = "Super admin {$emailSuper} criado. O painel fica em /painel após o login.";
        } catch (Throwable $e) {
            $bd->rollBack();
            $erros[] = 'Falha ao criar o super admin: ' . $e->getMessage();
        }
    }
}

if ($erros === [] && $totalFamilias === 0 && ($_POST['acao'] ?? '') === 'criar_familia') {
    $nomeFamilia = trim((string)($_POST['familia_nome'] ?? ''));
    $nomeAdmin   = trim((string)($_POST['admin_nome'] ?? ''));
    $emailAdmin  = mb_strtolower(trim((string)($_POST['admin_email'] ?? '')));
    $senhaAdmin  = (string)($_POST['admin_senha'] ?? '');

    if ($nomeFamilia === '' || $nomeAdmin === '' || !filter_var($emailAdmin, FILTER_VALIDATE_EMAIL)) {
        $erros[] = 'Preencha nome da família, nome e e-mail válidos do administrador.';
    } elseif (mb_strlen($senhaAdmin) < 10) {
        $erros[] = 'A senha do administrador precisa ter pelo menos 10 caracteres.';
    } else {
        try {
            $bd->beginTransaction();
            $declaracao = $bd->prepare(
                'INSERT INTO familias (codigo_publico, slug, nome) VALUES (?, ?, ?)'
            );
            $declaracao->execute([
                Identificadores::codigoPublico(),
                Identificadores::slug($nomeFamilia),
                $nomeFamilia,
            ]);
            $familiaId = (int)$bd->lastInsertId();

            $declaracao = $bd->prepare(
                'INSERT INTO usuarios (familia_id, codigo_publico, nome, email, senha_hash, papel)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            $declaracao->execute([
                $familiaId,
                Identificadores::codigoPublico(),
                $nomeAdmin,
                $emailAdmin,
                password_hash($senhaAdmin, PASSWORD_ARGON2ID),
                'admin_familia',
            ]);
            $bd->commit();
            $totalFamilias = 1;
            $mensagens[] = "Família \"{$nomeFamilia}\" criada com o administrador {$emailAdmin}. Você já pode entrar pelo /entrar.";
        } catch (Throwable $e) {
            $bd->rollBack();
            $erros[] = 'Falha ao criar a família inicial: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Migrações — Diário do Bebê</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 640px; margin: 2rem auto; padding: 0 1rem; color: #26302f; }
        .ok { background: #e5f4ea; color: #1b6e3c; padding: .6rem .9rem; border-radius: 8px; margin: .4rem 0; }
        .erro { background: #fbe9e7; color: #b3261e; padding: .6rem .9rem; border-radius: 8px; margin: .4rem 0; }
        form { border: 1px solid #dcd7cc; border-radius: 12px; padding: 1rem 1.25rem; margin-top: 1.5rem; }
        label { display: block; font-weight: 600; margin-top: .75rem; }
        input { width: 100%; padding: .6rem .8rem; font: inherit; border: 1px solid #dcd7cc; border-radius: 8px; box-sizing: border-box; }
        button { margin-top: 1rem; padding: .75rem 1.25rem; font: inherit; font-weight: 600; background: #2f6f6a; color: #fff; border: 0; border-radius: 8px; cursor: pointer; }
    </style>
</head>
<body>
<h1>Diário do Bebê — migrações</h1>

<?php foreach ($mensagens as $m): ?><div class="ok"><?= $esc($m) ?></div><?php endforeach; ?>
<?php foreach ($erros as $m): ?><div class="erro"><?= $esc($m) ?></div><?php endforeach; ?>

<?php if ($totalFamilias === 0): ?>
    <form method="post" action="migrate.php">
        <h2>Criar família inicial</h2>
        <p>Nenhuma família cadastrada ainda. Crie a primeira família e o administrador dela.
           (Depois disso, novos usuários entram somente por convite.)</p>
        <input type="hidden" name="token" value="<?= $esc($tokenRecebido) ?>">
        <input type="hidden" name="acao" value="criar_familia">

        <label for="familia_nome">Nome da família</label>
        <input id="familia_nome" name="familia_nome" required maxlength="120">

        <label for="admin_nome">Nome do administrador</label>
        <input id="admin_nome" name="admin_nome" required maxlength="120">

        <label for="admin_email">E-mail do administrador</label>
        <input id="admin_email" name="admin_email" type="email" required maxlength="190">

        <label for="admin_senha">Senha (mínimo 10 caracteres)</label>
        <input id="admin_senha" name="admin_senha" type="password" required minlength="10">

        <button type="submit">Criar família e administrador</button>
    </form>
<?php elseif ($totalFamilias > 0): ?>
    <p>Sistema instalado. Famílias cadastradas: <?= $totalFamilias ?>.</p>
<?php endif; ?>

<?php if ($totalSuperAdmins === 0 && $erros === []): ?>
    <form method="post" action="migrate.php">
        <h2>Criar super admin da plataforma (opcional)</h2>
        <p>Conta de gestão de famílias e planos (/painel). Não enxerga o conteúdo dos diários.</p>
        <input type="hidden" name="token" value="<?= $esc($tokenRecebido) ?>">
        <input type="hidden" name="acao" value="criar_super_admin">
        <label for="super_nome">Nome</label>
        <input id="super_nome" name="super_nome" required maxlength="120">
        <label for="super_email">E-mail</label>
        <input id="super_email" name="super_email" type="email" required maxlength="190">
        <label for="super_senha">Senha (mínimo 10 caracteres)</label>
        <input id="super_senha" name="super_senha" type="password" required minlength="10">
        <button type="submit">Criar super admin</button>
    </form>
<?php endif; ?>
</body>
</html>
