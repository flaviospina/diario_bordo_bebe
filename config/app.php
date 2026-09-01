<?php

declare(strict_types=1);

use App\Core\Ambiente;

/**
 * Constantes globais da aplicação, derivadas do .env.
 * Carregado uma única vez por App\Core\Aplicacao.
 */

Ambiente::carregar();

date_default_timezone_set(Ambiente::obter('APP_FUSO', 'America/Sao_Paulo'));
mb_internal_encoding('UTF-8');

/**
 * BASE_PATH: autodetectado do caminho do front controller, com override no .env.
 * Assim o sistema roda em subpasta (itthrive.com.br/diariobebe) e em domínio
 * próprio (diariobebe.com.br) sem alterar uma linha de código.
 * Valor sempre sem barra final; raiz do domínio = string vazia.
 */
$basePath = Ambiente::obter('BASE_PATH');
if ($basePath === '') {
    $basePath = str_replace('\\', '/', dirname((string)($_SERVER['SCRIPT_NAME'] ?? '/')));
}
$basePath = rtrim($basePath, '/');
define('BASE_PATH', $basePath);

define('APP_NOME', 'Diário do Bebê');
define('APP_VERSAO', '0.4.0');

/** Pasta de armazenamento (fotos, PDFs, logs) — configurável para fora do webroot. */
$storage = Ambiente::obter('STORAGE_PATH');
define('STORAGE_PATH', $storage !== '' ? rtrim($storage, '/') : RAIZ_PROJETO . '/storage');

/** Versões vigentes dos termos LGPD por tipo de titular. */
define('LGPD_VERSOES_TERMOS', [
    'responsavel' => '1.0',
    'cuidador'    => '1.0',
    'leitor'      => '1.0',
]);
