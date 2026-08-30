<?php
/**
 * Diário do Bebê — front controller único.
 * Todo acesso de navegação e de API passa por aqui (ver .htaccess).
 */

declare(strict_types=1);

define('RAIZ_PROJETO', __DIR__);

require RAIZ_PROJETO . '/app/Core/Autoloader.php';
\App\Core\Autoloader::registrar();

require RAIZ_PROJETO . '/app/Helpers/funcoes.php';

$aplicacao = new \App\Core\Aplicacao();
$aplicacao->executar();
