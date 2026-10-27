<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\RepositorioSistema;

/**
 * Introdução alimentar por idade (Rodada 5): lê o catálogo app/Dados/alimentos.php,
 * filtra por idade da criança e resume o que ela já experimentou.
 */
final class ServicoAlimentacao extends RepositorioSistema
{
    public const SLUG_CATEGORIA = 'introducao-alimentar';

    /** @var array<string,array<string,mixed>>|null */
    private static ?array $catalogo = null;

    /** @return array<string,array<string,mixed>> grupos → itens */
    public static function catalogo(): array
    {
        if (self::$catalogo === null) {
            self::$catalogo = require RAIZ_PROJETO . '/app/Dados/alimentos.php';
        }
        return self::$catalogo;
    }

    /** @return array<string,array<string,mixed>> slug → item (com 'grupo' e 'grupo_rotulo') */
    public static function itens(): array
    {
        $itens = [];
        foreach (self::catalogo() as $chaveGrupo => $grupo) {
            foreach ($grupo['itens'] as $item) {
                $item['grupo'] = $chaveGrupo;
                $item['grupo_rotulo'] = $grupo['rotulo'];
                $item['evitar'] = !empty($grupo['evitar']);
                $itens[$item['slug']] = $item;
            }
        }
        return $itens;
    }

    /**
     * Catálogo separado em "indicados agora" e "ainda não" para a idade.
     * Sem idade conhecida, tudo é "indicado" (com o selo da idade mínima).
     * @return array<string,array{rotulo:string, icone:string, evitar:bool, agora:array, depois:array}>
     */
    public static function porIdade(?float $idadeMeses): array
    {
        $saida = [];
        foreach (self::catalogo() as $chave => $grupo) {
            $agora = [];
            $depois = [];
            foreach ($grupo['itens'] as $item) {
                if ($idadeMeses === null || (int)$item['idade'] <= (int)floor($idadeMeses)) {
                    $agora[] = $item;
                } else {
                    $depois[] = $item;
                }
            }
            $saida[$chave] = [
                'rotulo' => $grupo['rotulo'],
                'icone' => $grupo['icone'],
                'evitar' => !empty($grupo['evitar']),
                'agora' => $agora,
                'depois' => $depois,
            ];
        }
        return $saida;
    }

    /** Fase da introdução alimentar pela idade (resumo das recomendações MS/SBP/OMS). */
    public static function fase(?float $idadeMeses): array
    {
        if ($idadeMeses === null) {
            return ['titulo' => 'Idade não informada', 'texto' => 'Cadastre a data de nascimento para ver as orientações da fase.'];
        }
        $m = (int)floor($idadeMeses);
        if ($m < 6) {
            return ['titulo' => 'Até 6 meses — só leite', 'texto' => 'Leite materno exclusivo (ou fórmula) até os 6 meses: nada de água, chá, suco ou papinha (MS/OMS). A introdução alimentar começa aos 6 meses, com a liberação do pediatra.'];
        }
        if ($m < 7) {
            return ['titulo' => '6 meses — começando', 'texto' => 'Fruta amassada ou raspada + 1 refeição principal (almoço) com um alimento de cada grupo: cereal/tubérculo, leguminosa, carne/ovo e legumes. Amassado com garfo — nada de liquidificador ou peneira. Água nos intervalos. Ovo e peixe entram desde já (um por vez, observando reações).'];
        }
        if ($m < 9) {
            return ['titulo' => '7 a 8 meses — 2 refeições principais', 'texto' => '2 frutas + almoço e jantar, cada um com os 4 grupos. Textura amassada evoluindo para pedaços macios. Ofereça o alimento novo por 2–3 dias antes de concluir que não gostou. Sem sal e sem açúcar.'];
        }
        if ($m < 12) {
            return ['titulo' => '9 a 11 meses — rumo à comida da família', 'texto' => 'Pedaços pequenos e macios (OMS: grumos até os 8–10 meses ajudam a mastigação). Comida da família sem sal, sem açúcar e sem ultraprocessados. Pode comer com as mãos. 3 refeições + 2 lanches (frutas).'];
        }
        if ($m < 24) {
            return ['titulo' => '12 a 23 meses — comida da família', 'texto' => 'Come o que a família come, com pouco sal e sem açúcar: café, almoço, jantar + 2 lanches. Leite de vaca integral pode virar bebida. Mel, açúcar, ultraprocessados e sucos continuam fora até os 2 anos.'];
        }
        return ['titulo' => 'A partir de 2 anos', 'texto' => 'Alimentação da família. Continue sem ultraprocessados; mel e açúcar com moderação; nozes/castanhas inteiras, pipoca e uva inteira só após 4 anos (engasgo).'];
    }

    /** Nomes legíveis para uma lista de slugs (exibição no registro). */
    public static function nomes(array $slugs): string
    {
        $itens = self::itens();
        $nomes = [];
        foreach ($slugs as $slug) {
            $nomes[] = $itens[(string)$slug]['nome'] ?? (string)$slug;
        }
        return implode(', ', $nomes);
    }

    /**
     * Alimentos já experimentados pela criança, com a data da primeira vez.
     * @return array<int,array{slug:string,nome:string,grupo_rotulo:string,primeira_vez:string,vezes:int}>
     */
    public function experimentados(int $familiaId, int $criancaId): array
    {
        $linhas = $this->executar(
            'SELECT r.inicio, r.dados
               FROM registros r JOIN categorias c ON c.id = r.categoria_id
              WHERE r.familia_id = :familia AND r.crianca_id = :crianca
                AND c.slug = :slug AND r.excluido_em IS NULL
              ORDER BY r.inicio',
            ['familia' => $familiaId, 'crianca' => $criancaId, 'slug' => self::SLUG_CATEGORIA]
        )->fetchAll();
        $itens = self::itens();
        $resumo = [];
        foreach ($linhas as $linha) {
            $dados = json_decode((string)$linha['dados'], true) ?: [];
            foreach ((array)($dados['alimentos'] ?? []) as $slug) {
                $slug = (string)$slug;
                if (!isset($resumo[$slug])) {
                    $resumo[$slug] = [
                        'slug' => $slug,
                        'nome' => $itens[$slug]['nome'] ?? $slug,
                        'grupo_rotulo' => $itens[$slug]['grupo_rotulo'] ?? '',
                        'primeira_vez' => substr((string)$linha['inicio'], 0, 10),
                        'vezes' => 0,
                    ];
                }
                $resumo[$slug]['vezes']++;
            }
        }
        return array_values($resumo);
    }
}
