<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Autenticacao;
use App\Core\Identificadores;
use App\Repositories\RepositorioSistema;

/**
 * Fotos de registros: validação de MIME real, reencodificação via GD (o que
 * também descarta EXIF/geolocalização — LGPD), redimensionamento e armazenagem
 * FORA do webroot. A entrega é sempre por /foto/{codigo}, autenticada.
 */
final class ServicoFotos extends RepositorioSistema
{
    private const LADO_MAXIMO = 1600;
    private const LADO_THUMB = 480;
    private const TAMANHO_MAXIMO = 8 * 1024 * 1024; // 8 MB

    /**
     * Processa um upload ($_FILES['foto']) e vincula ao registro.
     * @return ?string mensagem de erro (null = sucesso ou nenhum arquivo enviado)
     */
    public function anexar(array $arquivo, int $registroId): ?string
    {
        if (($arquivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if ($arquivo['error'] !== UPLOAD_ERR_OK) {
            return 'Falha no envio da foto. Tente novamente.';
        }
        if ((int)$arquivo['size'] > self::TAMANHO_MAXIMO) {
            return 'A foto excede o limite de 8 MB.';
        }

        // MIME REAL do conteúdo, nunca a extensão informada pelo cliente
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file((string)$arquivo['tmp_name']);
        $imagem = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg((string)$arquivo['tmp_name']),
            'image/png'  => @imagecreatefrompng((string)$arquivo['tmp_name']),
            'image/webp' => @imagecreatefromwebp((string)$arquivo['tmp_name']),
            default      => false,
        };
        if ($imagem === false) {
            return 'Formato não aceito. Envie JPEG, PNG ou WebP.';
        }

        $pasta = STORAGE_PATH . '/fotos/' . Autenticacao::familiaId();
        $pastaThumb = STORAGE_PATH . '/thumbs/' . Autenticacao::familiaId();
        if (!is_dir($pasta) && !mkdir($pasta, 0755, true)) {
            imagedestroy($imagem);
            return 'Não foi possível salvar a foto (storage indisponível).';
        }
        if (!is_dir($pastaThumb)) {
            mkdir($pastaThumb, 0755, true);
        }

        $codigo = Identificadores::codigoPublico();
        $caminho = $pasta . '/' . $codigo . '.jpg';
        $caminhoThumb = $pastaThumb . '/' . $codigo . '.jpg';

        $this->salvarRedimensionada($imagem, $caminho, self::LADO_MAXIMO);
        $this->salvarRedimensionada($imagem, $caminhoThumb, self::LADO_THUMB);
        imagedestroy($imagem);

        $this->executar(
            'INSERT INTO registro_fotos (codigo_publico, registro_id, caminho, thumb)
             VALUES (:codigo, :registro, :caminho, :thumb)',
            [
                'codigo'   => $codigo,
                'registro' => $registroId,
                'caminho'  => 'fotos/' . Autenticacao::familiaId() . '/' . $codigo . '.jpg',
                'thumb'    => 'thumbs/' . Autenticacao::familiaId() . '/' . $codigo . '.jpg',
            ]
        );
        return null;
    }

    /** Reencodifica como JPEG (remove metadados) limitando o maior lado. */
    private function salvarRedimensionada(\GdImage $origem, string $destino, int $ladoMaximo): void
    {
        $largura = imagesx($origem);
        $altura = imagesy($origem);
        $fator = min(1.0, $ladoMaximo / max($largura, $altura));
        $novaLargura = max(1, (int)round($largura * $fator));
        $novaAltura = max(1, (int)round($altura * $fator));

        $nova = imagecreatetruecolor($novaLargura, $novaAltura);
        // Fundo branco para PNGs com transparência
        $branco = imagecolorallocate($nova, 255, 255, 255);
        imagefill($nova, 0, 0, $branco);
        imagecopyresampled($nova, $origem, 0, 0, 0, 0, $novaLargura, $novaAltura, $largura, $altura);
        imagejpeg($nova, $destino, 82);
        imagedestroy($nova);
    }

    /** Metadados da foto, garantindo que pertence à família do usuário logado. */
    public function buscarDaFamilia(string $codigo): ?array
    {
        return $this->buscarUm(
            'SELECT f.* FROM registro_fotos f
               JOIN registros r ON r.id = f.registro_id
              WHERE f.codigo_publico = :codigo AND r.familia_id = :familia
              LIMIT 1',
            ['codigo' => $codigo, 'familia' => Autenticacao::familiaId()]
        );
    }

    /** @return array<int,array<string,mixed>> fotos de um registro */
    public function listarDoRegistro(int $registroId): array
    {
        return $this->executar(
            'SELECT * FROM registro_fotos WHERE registro_id = :registro ORDER BY id',
            ['registro' => $registroId]
        )->fetchAll();
    }
}
