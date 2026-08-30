<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Gerador de PDF minimalista, sem dependências (restrição da hospedagem:
 * nada de Composer/terminal). Suficiente para os relatórios do produto:
 * texto em Helvetica (normal/negrito), linhas e quebra automática de página.
 * Página A4 em pontos (595 x 842).
 */
final class MiniPdf
{
    private const LARGURA = 595.28;
    private const ALTURA = 841.89;
    private const MARGEM = 48.0;

    /** @var string[] conteúdo (stream) de cada página */
    private array $paginas = [];
    private float $cursorY = 0.0;

    public function __construct()
    {
        $this->novaPagina();
    }

    public function novaPagina(): void
    {
        $this->paginas[] = '';
        $this->cursorY = self::ALTURA - self::MARGEM;
    }

    /** Escreve uma linha no cursor vertical, com quebra de página automática. */
    public function linhaTexto(string $texto, float $tamanho = 10.0, bool $negrito = false, float $recuo = 0.0): void
    {
        $alturaLinha = $tamanho * 1.45;
        if ($this->cursorY - $alturaLinha < self::MARGEM) {
            $this->novaPagina();
        }
        $this->cursorY -= $alturaLinha;
        $this->textoEm(self::MARGEM + $recuo, $this->cursorY, $texto, $tamanho, $negrito);
    }

    /** Quebra um parágrafo em linhas que caibam na largura útil. */
    public function paragrafo(string $texto, float $tamanho = 10.0, bool $negrito = false): void
    {
        $larguraUtil = self::LARGURA - 2 * self::MARGEM;
        // Aproximação de largura de glifo da Helvetica: 0.5 * tamanho
        $maximoCaracteres = max(20, (int)floor($larguraUtil / ($tamanho * 0.5)));
        foreach (explode("\n", $texto) as $linha) {
            $quebrada = wordwrap($linha, $maximoCaracteres, "\n", true);
            foreach (explode("\n", $quebrada) as $pedaco) {
                $this->linhaTexto($pedaco, $tamanho, $negrito);
            }
        }
    }

    public function espaco(float $pontos = 8.0): void
    {
        $this->cursorY -= $pontos;
    }

    public function linhaHorizontal(): void
    {
        if ($this->cursorY - 6 < self::MARGEM) {
            $this->novaPagina();
        }
        $this->cursorY -= 6;
        $y = $this->cursorY;
        $this->paginas[count($this->paginas) - 1] .= sprintf(
            "0.7 0.7 0.7 RG %.2f %.2f m %.2f %.2f l S\n",
            self::MARGEM,
            $y,
            self::LARGURA - self::MARGEM,
            $y
        );
        $this->cursorY -= 4;
    }

    /**
     * Linha de "tabela": colunas com posições X fixas (em pontos, a partir da margem).
     * @param array<int,string> $celulas
     * @param float[] $posicoes
     */
    public function linhaTabela(array $celulas, array $posicoes, float $tamanho = 9.0, bool $negrito = false): void
    {
        $alturaLinha = $tamanho * 1.5;
        if ($this->cursorY - $alturaLinha < self::MARGEM) {
            $this->novaPagina();
        }
        $this->cursorY -= $alturaLinha;
        foreach ($celulas as $indice => $celula) {
            $this->textoEm(self::MARGEM + ($posicoes[$indice] ?? 0.0), $this->cursorY, $celula, $tamanho, $negrito);
        }
    }

    private function textoEm(float $x, float $y, string $texto, float $tamanho, bool $negrito): void
    {
        $fonte = $negrito ? '/F2' : '/F1';
        $this->paginas[count($this->paginas) - 1] .= sprintf(
            "BT %s %.2f Tf %.2f %.2f Td (%s) Tj ET\n",
            $fonte,
            $tamanho,
            $x,
            $y,
            $this->escapar($texto)
        );
    }

    private function escapar(string $texto): string
    {
        // Fontes base usam WinAnsi: converte do UTF-8 e escapa delimitadores
        $convertido = @iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $texto);
        if ($convertido === false) {
            $convertido = preg_replace('/[^\x20-\x7E]/', '?', $texto) ?? '';
        }
        return strtr($convertido, ['\\' => '\\\\', '(' => '\\(', ')' => '\\)', "\r" => '', "\n" => ' ']);
    }

    /** Monta o arquivo PDF completo. */
    public function gerar(): string
    {
        $objetos = [];
        $totalPaginas = count($this->paginas);

        // 1: catálogo | 2: árvore de páginas | 3-4: fontes
        $referenciasPaginas = [];
        for ($i = 0; $i < $totalPaginas; $i++) {
            $referenciasPaginas[] = (5 + $i * 2) . ' 0 R';
        }
        $objetos[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objetos[2] = '<< /Type /Pages /Kids [' . implode(' ', $referenciasPaginas) . '] /Count ' . $totalPaginas . ' >>';
        $objetos[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
        $objetos[4] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';

        foreach ($this->paginas as $indice => $conteudo) {
            $numeroPagina = 5 + $indice * 2;
            $numeroConteudo = $numeroPagina + 1;
            $objetos[$numeroPagina] = sprintf(
                '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %.2f %.2f] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents %d 0 R >>',
                self::LARGURA,
                self::ALTURA,
                $numeroConteudo
            );
            $objetos[$numeroConteudo] = "<< /Length " . strlen($conteudo) . " >>\nstream\n" . $conteudo . 'endstream';
        }

        $saida = "%PDF-1.4\n";
        $posicoes = [];
        ksort($objetos);
        foreach ($objetos as $numero => $corpo) {
            $posicoes[$numero] = strlen($saida);
            $saida .= $numero . " 0 obj\n" . $corpo . "\nendobj\n";
        }
        $inicioXref = strlen($saida);
        $totalObjetos = count($objetos) + 1;
        $saida .= "xref\n0 {$totalObjetos}\n0000000000 65535 f \n";
        foreach ($posicoes as $posicao) {
            $saida .= sprintf("%010d 00000 n \n", $posicao);
        }
        $saida .= "trailer\n<< /Size {$totalObjetos} /Root 1 0 R >>\nstartxref\n{$inicioXref}\n%%EOF";
        return $saida;
    }
}
