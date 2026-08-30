<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\RepositorioConfiguracoes;

/**
 * Configurações da família (seção 4 do escopo — o coração do produto).
 * Nada disso é fixo no código: aqui moram apenas os PADRÕES; os valores
 * efetivos vêm do banco, versionados por família.
 */
final class ServicoConfiguracoes
{
    public function __construct(
        private readonly RepositorioConfiguracoes $repositorio = new RepositorioConfiguracoes(),
    ) {
    }

    /** @return array<string,mixed> */
    public static function padroes(): array
    {
        return [
            // 1. Janela do dia
            'janela_dia' => [
                'inicio' => '07:00',
                'fim' => '19:00',
                'variacao_semana' => false,
                // usado apenas quando variacao_semana = true (dom..sab)
                'por_dia' => [],
            ],
            // 2. Granularidade dos blocos: flexivel | 15 | 30 | 60
            'granularidade' => 'flexivel',
            // 3. Usar roteiro prescrito (opcional, nunca obrigatório)
            'roteiro_ativo' => false,
            // 4. Local de permanência: casa | casa_escola | casa_creche | outro
            'local_permanencia' => 'casa',
            // 5. Regra de edição: livre | mesmo_dia | janela_minutos | somente_com_aprovacao
            'regra_edicao' => ['tipo' => 'mesmo_dia', 'janela_minutos' => 60],
            // 6. Edição de dias anteriores (false = vira solicitação aos pais)
            'edicao_dias_anteriores' => false,
            // 7. Alerta de omissão
            'alerta_omissao' => ['ativo' => false, 'minutos' => 90],
            // 8. Fotos: obrigatoria | opcional | desativada
            'fotos' => 'opcional',
            // 9. Resumo diário
            'resumo_diario' => ['ativo' => false, 'horario' => '19:30', 'canais' => ['whatsapp', 'email']],
            // 10. Categorias desativadas pela família (lista de slugs)
            'categorias_inativas' => [],
            // Grade: minutos após o fim do bloco em que ele fica âmbar antes de vermelho
            'tolerancia_atraso_minutos' => 15,
            // Ações rápidas do rodapé do cuidador (4 slugs de categoria)
            'acoes_rapidas' => ['mamadeira', 'fralda', 'soneca', 'agua'],
            // Retenção de dados: null = manter tudo; N = expurgar registros após N meses
            'retencao_meses' => null,
        ];
    }

    /** Valor efetivo (banco → padrão). */
    public function obter(string $chave): mixed
    {
        $valor = $this->repositorio->obter($chave);
        return $valor ?? (self::padroes()[$chave] ?? null);
    }

    /** @return array<string,mixed> todas as chaves com valor efetivo */
    public function todas(): array
    {
        return array_merge(self::padroes(), $this->repositorio->todas());
    }

    public function salvar(string $chave, mixed $valor, int $usuarioId): void
    {
        if (!array_key_exists($chave, self::padroes())) {
            throw new \InvalidArgumentException("Chave de configuração desconhecida: {$chave}");
        }
        $this->repositorio->salvar($chave, $valor, $usuarioId);
    }

    /** Janela do dia efetiva para uma data (considera variação por dia da semana). */
    public function janelaParaData(string $data): array
    {
        $janela = $this->obter('janela_dia');
        $inicio = (string)($janela['inicio'] ?? '07:00');
        $fim = (string)($janela['fim'] ?? '19:00');
        if (!empty($janela['variacao_semana'])) {
            $dias = ['dom', 'seg', 'ter', 'qua', 'qui', 'sex', 'sab'];
            $dia = $dias[(int)(new \DateTime($data))->format('w')];
            if (!empty($janela['por_dia'][$dia]['inicio'])) {
                $inicio = (string)$janela['por_dia'][$dia]['inicio'];
            }
            if (!empty($janela['por_dia'][$dia]['fim'])) {
                $fim = (string)$janela['por_dia'][$dia]['fim'];
            }
        }
        return ['inicio' => $inicio, 'fim' => $fim];
    }
}
