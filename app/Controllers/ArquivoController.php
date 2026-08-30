<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Autenticacao;
use App\Core\Requisicao;
use App\Core\Visao;
use App\Services\ServicoFotos;

/**
 * Entrega de arquivos protegidos. Fotos e downloads NUNCA são servidos por
 * caminho físico: sempre por código público com verificação de família.
 */
final class ArquivoController
{
    public function foto(Requisicao $requisicao): void
    {
        $foto = (new ServicoFotos())->buscarDaFamilia($requisicao->parametro('codigo'));
        if ($foto === null) {
            Visao::erro404();
        }
        $relativo = $requisicao->get('thumb') === '1' && $foto['thumb'] !== null
            ? (string)$foto['thumb'] : (string)$foto['caminho'];
        $caminho = STORAGE_PATH . '/' . $relativo;
        if (!is_file($caminho)) {
            Visao::erro404();
        }
        header('Content-Type: image/jpeg');
        header('Content-Length: ' . (string)filesize($caminho));
        header('Cache-Control: private, max-age=86400');
        readfile($caminho);
        exit;
    }

    public function download(Requisicao $requisicao): void
    {
        $bd = \App\Core\BancoDados::conexao();
        $declaracao = $bd->prepare(
            'SELECT * FROM arquivos_gerados
              WHERE codigo_publico = :codigo AND familia_id = :familia
                AND (expira_em IS NULL OR expira_em > NOW())
              LIMIT 1'
        );
        $declaracao->execute([
            'codigo' => $requisicao->parametro('codigo'),
            'familia' => Autenticacao::familiaId(),
        ]);
        $arquivo = $declaracao->fetch();
        if ($arquivo === false) {
            Visao::erro404();
        }
        $caminho = STORAGE_PATH . '/' . $arquivo['caminho'];
        if (!is_file($caminho)) {
            Visao::erro404();
        }
        $nome = preg_replace('/[^a-z0-9\-_]/', '-', mb_strtolower((string)$arquivo['descricao']))
            . '.' . $arquivo['tipo'];
        header('Content-Type: ' . ($arquivo['tipo'] === 'pdf' ? 'application/pdf' : 'text/csv; charset=utf-8'));
        header('Content-Disposition: attachment; filename="' . $nome . '"');
        header('Content-Length: ' . (string)filesize($caminho));
        readfile($caminho);
        exit;
    }
}
