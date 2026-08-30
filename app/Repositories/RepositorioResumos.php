<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Identificadores;

final class RepositorioResumos extends RepositorioBase
{
    public function existeParaDia(int $criancaId, string $data): bool
    {
        return $this->buscarUm(
            'SELECT id FROM resumos_diarios
              WHERE familia_id = :familia_id AND crianca_id = :crianca AND data = :data LIMIT 1',
            ['crianca' => $criancaId, 'data' => $data]
        ) !== null;
    }

    public function criar(int $criancaId, string $data, string $texto, array $canais): string
    {
        $codigo = Identificadores::codigoPublico();
        $this->executar(
            'INSERT INTO resumos_diarios (codigo_publico, familia_id, crianca_id, data, texto_gerado, enviado_em, canais)
             VALUES (:codigo, :familia_id, :crianca, :data, :texto, NOW(), :canais)',
            [
                'codigo' => $codigo, 'crianca' => $criancaId, 'data' => $data,
                'texto' => $texto, 'canais' => json_encode($canais, JSON_UNESCAPED_UNICODE),
            ]
        );
        return $codigo;
    }

    public function buscarPorDia(int $criancaId, string $data): ?array
    {
        return $this->buscarUm(
            'SELECT * FROM resumos_diarios
              WHERE familia_id = :familia_id AND crianca_id = :crianca AND data = :data LIMIT 1',
            ['crianca' => $criancaId, 'data' => $data]
        );
    }
}
