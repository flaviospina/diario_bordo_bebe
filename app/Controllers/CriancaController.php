<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Autenticacao;
use App\Core\Requisicao;
use App\Core\Resposta;
use App\Core\Sessao;
use App\Core\Visao;
use App\Repositories\RepositorioCategorias;
use App\Repositories\RepositorioConsultasMedicas;
use App\Repositories\RepositorioCriancas;
use App\Repositories\RepositorioMedicoes;
use App\Repositories\RepositorioRegistros;
use App\Repositories\RepositorioVacinas;
use App\Services\ServicoConfiguracoes;
use App\Services\ServicoConsulta;
use App\Services\ServicoCrescimento;

final class CriancaController
{
    /** Ficha essencial da criança (Alteração 01): o cartão que o pediatra vê. */
    public function ver(Requisicao $requisicao): void
    {
        $crianca = $this->criancaDaRota($requisicao);
        $papel = (string)(Autenticacao::usuario()['papel'] ?? '');
        $ehResponsavel = in_array($papel, ['responsavel', 'admin_familia'], true);

        $medicoes = new RepositorioMedicoes();
        $historico = $medicoes->listar((int)$crianca['id'], 60);
        $vacinas = (new RepositorioVacinas())->listar((int)$crianca['id']);

        Visao::exibir('crianca/ver', [
            'titulo' => $crianca['nome'],
            'crianca' => $crianca,
            'ehResponsavel' => $ehResponsavel,
            'ehAdmin' => $papel === 'admin_familia',
            'idade' => $crianca['data_nascimento'] !== null
                ? ServicoCrescimento::idadeFormatada((string)$crianca['data_nascimento']) : null,
            'ultimas' => $medicoes->ultimasMedidas((int)$crianca['id']),
            'pendentes' => $ehResponsavel ? $medicoes->pendentes((int)$crianca['id']) : [],
            'historico' => $historico,
            'curvas' => (new ServicoCrescimento())->curvas($crianca, $historico),
            'calendarioVacinal' => $this->calendarioVacinal($crianca, $vacinas),
            'consultas' => (new RepositorioConsultasMedicas())->listarConsultas((int)$crianca['id'], 20),
        ]);
    }

    public function timeline(Requisicao $requisicao): void
    {
        $crianca = $this->criancaDaRota($requisicao);
        $categoriaSlug = $requisicao->get('categoria');
        $de = $requisicao->get('de');
        $ate = $requisicao->get('ate');
        $dataOk = static fn(?string $d): ?string =>
            is_string($d) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) === 1 ? $d : null;

        $registros = (new RepositorioRegistros())->linhaDoTempo(
            (int)$crianca['id'],
            $categoriaSlug,
            $dataOk($de),
            $dataOk($ate),
            200
        );
        Visao::exibir('crianca/timeline', [
            'titulo' => 'Linha do tempo — ' . ($crianca['apelido'] ?: $crianca['nome']),
            'crianca' => $crianca,
            'registros' => $registros,
            'categorias' => (new RepositorioCategorias())->ativasParaFamilia(
                (array)(new ServicoConfiguracoes())->obter('categorias_inativas')
            ),
            'filtros' => ['categoria' => (string)$categoriaSlug, 'de' => (string)$de, 'ate' => (string)$ate],
        ]);
    }

    // ── Medições (peso, altura, perímetro cefálico) ───────────

    public function medicoes(Requisicao $requisicao): void
    {
        $crianca = $this->criancaDaRota($requisicao);
        $medicoes = new RepositorioMedicoes();
        Visao::exibir('crianca/medicoes', [
            'titulo' => 'Medições — ' . ($crianca['apelido'] ?: $crianca['nome']),
            'crianca' => $crianca,
            'pendentes' => $medicoes->pendentes((int)$crianca['id']),
            'historico' => $medicoes->listar((int)$crianca['id'], 100),
        ]);
    }

    public function medicoesSalvar(Requisicao $requisicao): void
    {
        $crianca = $this->criancaDaRota($requisicao);
        $servico = new ServicoConsulta();

        if ($requisicao->post('acao') === 'confirmar') {
            $medicaoId = (int)$requisicao->post('medicao_id', '0');
            $erro = $servico->confirmarMedicao($medicaoId);
            Sessao::flash($erro === null ? 'sucesso' : 'erro', $erro ?? 'Medição confirmada.');
        } else {
            $resultado = $servico->medicaoManual($crianca, $_POST);
            Sessao::flash(
                $resultado['erro'] === null ? 'sucesso' : 'erro',
                $resultado['erro'] ?? 'Medição registrada.'
            );
        }
        Resposta::redirecionarRota('crianca.medicoes', ['slug' => $crianca['slug']]);
    }

    // ── Vacinas ───────────────────────────────────────────────

    public function vacinas(Requisicao $requisicao): void
    {
        $crianca = $this->criancaDaRota($requisicao);
        $aplicadas = (new RepositorioVacinas())->listar((int)$crianca['id']);
        Visao::exibir('crianca/vacinas', [
            'titulo' => 'Vacinas — ' . ($crianca['apelido'] ?: $crianca['nome']),
            'crianca' => $crianca,
            'calendarioVacinal' => $this->calendarioVacinal($crianca, $aplicadas),
        ]);
    }

    public function vacinasSalvar(Requisicao $requisicao): void
    {
        $crianca = $this->criancaDaRota($requisicao);
        $imunizante = trim((string)$requisicao->post('imunizante', ''));
        $dose = trim((string)$requisicao->post('dose', ''));
        if ($imunizante === '' || $dose === '') {
            Sessao::flash('erro', 'Informe o imunizante e a dose.');
            Resposta::redirecionarRota('crianca.vacinas', ['slug' => $crianca['slug']]);
        }
        $data = (string)$requisicao->post('aplicada_em', '');
        (new RepositorioVacinas())->criar([
            'crianca_id' => (int)$crianca['id'],
            'imunizante' => mb_substr($imunizante, 0, 120),
            'dose' => mb_substr($dose, 0, 40),
            'aplicada_em' => preg_match('/^\d{4}-\d{2}-\d{2}$/', $data) === 1 ? $data : hoje(),
            'lote' => trim((string)$requisicao->post('lote', '')) ?: null,
            'local_aplicacao' => trim((string)$requisicao->post('local_aplicacao', '')) ?: null,
            'origem' => 'pais',
            'status' => 'aplicada',
        ]);
        Sessao::flash('sucesso', 'Vacina registrada na caderneta.');
        Resposta::redirecionarRota('crianca.vacinas', ['slug' => $crianca['slug']]);
    }

    // ── Auxiliares ────────────────────────────────────────────

    private function criancaDaRota(Requisicao $requisicao): array
    {
        $crianca = (new RepositorioCriancas())->buscarPorSlug($requisicao->parametro('slug'));
        if ($crianca === null) {
            Visao::erro404();
        }
        return $crianca;
    }

    /**
     * Calendário PNI cruzado com as vacinas registradas.
     * @return array{itens:array<int,array<string,mixed>>, outras:array<int,array<string,mixed>>}
     */
    private function calendarioVacinal(array $crianca, array $aplicadas): array
    {
        $normalizar = static function (string $texto): string {
            $texto = mb_strtolower(trim($texto));
            $convertido = @iconv('UTF-8', 'ASCII//TRANSLIT', $texto);
            return $convertido !== false ? $convertido : $texto;
        };
        $idadeMeses = null;
        if (!empty($crianca['data_nascimento'])) {
            $idadeMeses = ServicoCrescimento::idadeEmMeses((string)$crianca['data_nascimento'], hoje());
        }

        $usadas = [];
        $itens = [];
        foreach (require RAIZ_PROJETO . '/database/dados/pni.php' as [$imunizante, $dose, $idadeRecomendada]) {
            $chaveImunizante = $normalizar($imunizante);
            $chaveDose = $normalizar($dose);
            $registro = null;
            foreach ($aplicadas as $indice => $vacina) {
                if (isset($usadas[$indice])) {
                    continue;
                }
                $nome = $normalizar((string)$vacina['imunizante']);
                $doseNome = $normalizar((string)$vacina['dose']);
                $nomeBate = $nome === $chaveImunizante
                    || str_contains($chaveImunizante, $nome) || str_contains($nome, $chaveImunizante);
                $doseBate = $doseNome === $chaveDose
                    || str_contains($chaveDose, $doseNome) || str_contains($doseNome, $chaveDose);
                if ($nomeBate && $doseBate) {
                    $registro = $vacina;
                    $usadas[$indice] = true;
                    break;
                }
            }
            $status = 'prevista';
            if ($registro !== null) {
                $status = 'aplicada';
            } elseif ($idadeMeses !== null && $idadeMeses > $idadeRecomendada + 1) {
                $status = 'atrasada'; // 1 mês de folga antes de sinalizar
            }
            $itens[] = [
                'imunizante' => $imunizante,
                'dose' => $dose,
                'idade_meses' => $idadeRecomendada,
                'status' => $status,
                'registro' => $registro,
            ];
        }

        $outras = array_values(array_filter(
            $aplicadas,
            static fn(int $indice): bool => !isset($usadas[$indice]),
            ARRAY_FILTER_USE_KEY
        ));
        return ['itens' => $itens, 'outras' => $outras];
    }
}
