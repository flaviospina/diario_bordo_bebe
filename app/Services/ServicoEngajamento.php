<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\RepositorioConfiguracoesPlataforma;
use App\Repositories\RepositorioEmails;
use App\Repositories\RepositorioSistema;
use App\Repositories\RepositorioUsuariosFamilia;

/**
 * Engajamento das famílias (Rodada 3): o dashboard "Saúde das famílias" do
 * Painel e os e-mails automáticos de relacionamento — tudo a partir de
 * METADADOS (datas e contagens); o conteúdo do diário nunca é lido aqui.
 *
 * E-mails e gatilhos (todos idempotentes, com travas anti-spam):
 *   resgate        — família criada há 3+ dias com diário zerado;
 *   reengajamento  — família que usava e está há 7+ dias sem registrar;
 *   resumo_mensal  — início do mês, para quem teve atividade no mês anterior;
 *   mesversario    — no dia em que o bebê completa cada mês (1º ao 24º);
 *   pre_consulta   — 2 dias antes do retorno marcado pelo pediatra.
 *
 * Travas: resgate/reengajamento no máximo 1 por semana e desistem após 3
 * tentativas sem reação; os demais são únicos por mês/consulta. Todo envio
 * fica em emails_enviados (relatório do Painel).
 */
final class ServicoEngajamento extends RepositorioSistema
{
    private const DIAS_CARENCIA_RESGATE = 3;
    private const DIAS_INATIVIDADE = 7;
    private const INTERVALO_REENVIO_DIAS = 7;
    private const MAXIMO_TENTATIVAS = 3;
    private const MESVERSARIO_MAXIMO_MESES = 24;

    // ── Dashboard "Saúde das famílias" ────────────────────────

    /**
     * @return array{tiles:array<string,int>, familias:array<int,array<string,mixed>>}
     */
    public function saudeFamilias(): array
    {
        $familias = $this->executar(
            "SELECT f.id, f.codigo_publico, f.nome, f.plano, f.status, f.criado_em,
                    (SELECT MAX(u.ultimo_login) FROM usuarios u WHERE u.familia_id = f.id) AS ultimo_acesso,
                    (SELECT MAX(r.criado_em) FROM registros r WHERE r.familia_id = f.id) AS ultimo_registro,
                    (SELECT COUNT(*) FROM registros r WHERE r.familia_id = f.id) AS total_registros,
                    (SELECT COUNT(*) FROM registros r WHERE r.familia_id = f.id
                        AND r.criado_em >= DATE_SUB(NOW(), INTERVAL 7 DAY)) AS registros_7d,
                    (SELECT COUNT(*) FROM registros r WHERE r.familia_id = f.id
                        AND r.criado_em >= DATE_SUB(NOW(), INTERVAL 30 DAY)) AS registros_30d,
                    (SELECT COUNT(*) FROM criancas c WHERE c.familia_id = f.id AND c.ativo = 1) AS total_criancas,
                    (SELECT COUNT(*) FROM usuarios u WHERE u.familia_id = f.id AND u.ativo = 1) AS total_usuarios
               FROM familias f
              WHERE f.plano <> 'plataforma'
              ORDER BY f.criado_em DESC"
        )->fetchAll();

        $lembretes = (new RepositorioEmails())->ultimosEngajamentosPorFamilia();
        $tiles = ['ativas' => 0, 'esfriando' => 0, 'inativas' => 0, 'nunca' => 0];
        foreach ($familias as &$familia) {
            $situacao = self::classificar($familia);
            $familia['situacao'] = $situacao['chave'];
            $familia['situacao_rotulo'] = $situacao['rotulo'];
            $familia['lembretes'] = $lembretes[(int)$familia['id']] ?? [];
            $tiles[$situacao['grupo']]++;
        }
        return ['tiles' => $tiles, 'familias' => $familias];
    }

    /** @return array{chave:string, rotulo:string, grupo:string} */
    private static function classificar(array $familia): array
    {
        if ((int)$familia['total_registros'] === 0) {
            return ['chave' => 'nunca', 'rotulo' => 'Nunca começou', 'grupo' => 'nunca'];
        }
        $dias = (int)floor((time() - strtotime((string)$familia['ultimo_registro'])) / 86400);
        if ($dias > self::DIAS_INATIVIDADE) {
            return ['chave' => 'inativa', 'rotulo' => 'Inativa há ' . $dias . ' dias', 'grupo' => 'inativas'];
        }
        if ($dias >= 3) {
            return ['chave' => 'esfriando', 'rotulo' => 'Esfriando (' . $dias . ' dias)', 'grupo' => 'esfriando'];
        }
        return ['chave' => 'ativa', 'rotulo' => 'Ativa', 'grupo' => 'ativas'];
    }

    // ── Tarefa diária (cron) ──────────────────────────────────

    /** @return array<string,mixed> resumo por tipo para o retorno do cron */
    public function executarDiario(): array
    {
        $resultado = ['resgate' => [], 'reengajamento' => [], 'resumo_mensal' => [],
                      'mesversario' => [], 'pre_consulta' => []];
        $emails = new RepositorioEmails();

        foreach ($this->saudeFamilias()['familias'] as $familia) {
            if ($familia['status'] !== 'ativa') {
                continue; // suspensa não recebe nada
            }
            $familiaId = (int)$familia['id'];

            if ($familia['situacao'] === 'nunca'
                && strtotime((string)$familia['criado_em']) <= strtotime('-' . self::DIAS_CARENCIA_RESGATE . ' days')
                && !$emails->enviadoRecentemente($familiaId, 'resgate', self::INTERVALO_REENVIO_DIAS)
                && $emails->contarDesde($familiaId, 'resgate') < self::MAXIMO_TENTATIVAS
                && $this->enviarResgate($familia)) {
                $resultado['resgate'][] = $familia['nome'];
            }

            if ($familia['situacao'] === 'inativa'
                && !$emails->enviadoRecentemente($familiaId, 'reengajamento', self::INTERVALO_REENVIO_DIAS)
                && $emails->contarDesde($familiaId, 'reengajamento', (string)$familia['ultimo_registro']) < self::MAXIMO_TENTATIVAS
                && $this->enviarReengajamento($familia)) {
                $resultado['reengajamento'][] = $familia['nome'];
            }

            if ((int)date('j') <= 3
                && !$emails->enviadoRecentemente($familiaId, 'resumo_mensal', 27)
                && $this->enviarResumoMensal($familia)) {
                $resultado['resumo_mensal'][] = $familia['nome'];
            }

            foreach ($this->mesversariosDeHoje($familiaId) as $crianca) {
                if (!$emails->existePorReferencia('mesversario', (int)$crianca['id'], date('Y-m-01 00:00:00'))
                    && $this->enviarMesversario($familia, $crianca)) {
                    $resultado['mesversario'][] = $crianca['nome'];
                }
            }

            foreach ($this->retornosEmDoisDias($familiaId) as $consulta) {
                if (!$emails->existePorReferencia('pre_consulta', (int)$consulta['id'])
                    && $this->enviarPreConsulta($familia, $consulta)) {
                    $resultado['pre_consulta'][] = $consulta['crianca_nome'];
                }
            }
        }
        return $resultado;
    }

    // ── E-mails (textos aprovados na Rodada 3) ────────────────

    private function enviarResgate(array $familia): bool
    {
        $conteudo = static fn(string $nome): string =>
            '<h2 style="margin:0 0 12px; font-size:19px;">O cantinho de vocês está pronto — só falta a primeira página 📖</h2>'
            . '<p style="margin:0 0 12px;">Olá, <strong>' . e($nome) . '</strong>!</p>'
            . '<p style="margin:0 0 12px;">A família <strong>' . e((string)$familia['nome']) . '</strong> já tem o seu espaço no '
            . 'Diário do Bebê, mas notamos que, desde o cadastro, o diário continua em branco — e não queremos que vocês '
            . 'percam o melhor dele.</p>'
            . '<p style="margin:0 0 12px;">Cada dia da primeira infância acontece <strong>uma única vez</strong>. As famílias '
            . 'que registram a rotina chegam à consulta do pediatra com as respostas prontas — quanto dormiu, quanto mamou, '
            . 'como o peso evoluiu — em vez de tentar lembrar de cabeça.</p>'
            . self::caixinha('<p style="margin:0 0 6px;"><strong>Começar leva menos de 2 minutos:</strong></p>'
                . '<p style="margin:0;">1. Abra o app e toque no botão <strong>+</strong> do Meu Dia;<br>'
                . '2. Registre a primeira atividade — uma mamada ou uma soneca já contam;<br>'
                . '3. Convide quem cuida do bebê em Ajustes → Usuários.</p>')
            . ServicoEmail::botao('Começar agora — guia passo a passo', url_absoluta('ajuda'))
            . '<p style="margin:12px 0 4px;">Ficou qualquer dúvida? É só nos chamar — <strong>respondemos pessoalmente</strong>:</p>'
            . self::botaoWhatsapp()
            . '<p style="margin:14px 0 0;">Vocês fazem parte das <strong>famílias fundadoras</strong>: o app é 100% gratuito para vocês.</p>'
            . self::notaLgpd();
        $texto = static fn(string $nome): string =>
            "Olá, {$nome}!\n\nA família {$familia['nome']} já tem o seu espaço no Diário do Bebê, mas o diário ainda "
            . "está em branco. Começar leva menos de 2 minutos — veja o guia: " . url_absoluta('ajuda')
            . "\n\nQualquer dúvida, chame a gente no WhatsApp — respondemos pessoalmente."
            . "\n\nTransparência: nós não lemos o diário; este aviso usa apenas o registro técnico de acessos.";
        return $this->enviarParaResponsaveis(
            (int)$familia['id'],
            'Ficou alguma dúvida para começar? A gente ajuda 💚',
            $conteudo,
            $texto,
            'resgate'
        );
    }

    private function enviarReengajamento(array $familia): bool
    {
        $dias = max(self::DIAS_INATIVIDADE + 1, (int)floor((time() - strtotime((string)$familia['ultimo_registro'])) / 86400));
        $total = (int)$familia['total_registros'];
        $bebe = $this->nomeDoBebe((int)$familia['id']);
        $deBebe = $bebe !== null ? 'de ' . $bebe : 'do bebê';

        $conteudo = static fn(string $nome): string =>
            '<h2 style="margin:0 0 12px; font-size:19px;">Já se passaram ' . $dias . ' dias sem novidades no diário</h2>'
            . '<p style="margin:0 0 12px;">Olá, <strong>' . e($nome) . '</strong>!</p>'
            . '<p style="margin:0 0 12px;">O último registro no diário ' . e($deBebe) . ' foi há <strong>' . $dias
            . ' dias</strong> — e, nessa fase, cada semana traz mudanças que não se repetem.</p>'
            . '<p style="margin:0 0 12px;">Vocês já guardaram <strong>' . $total . ' momentos</strong> por aqui. É essa '
            . 'constância que torna o diário valioso: com o registro em dia, o pediatra enxerga a evolução completa — sono, '
            . 'mamadas, crescimento, comportamento — e orienta com muito mais precisão. Um período em branco é um pedaço '
            . 'da história que ele não poderá ver.</p>'
            . self::caixinha('<p style="margin:0;"><strong>Dois minutos hoje já colocam tudo em dia:</strong><br>'
                . '• registre as atividades de agora — dá para marcar várias de uma vez;<br>'
                . '• aproveite e anote a última pesagem: cada medição vira um ponto na curva da OMS.</p>')
            . ServicoEmail::botao('Retomar o diário agora', url_absoluta('home'))
            . '<p style="margin:12px 0 4px;">Precisa de uma mão? <strong>Respondemos pessoalmente:</strong></p>'
            . self::botaoWhatsapp()
            . self::notaLgpd();
        $texto = static fn(string $nome): string =>
            "Olá, {$nome}!\n\nO último registro no diário {$deBebe} foi há {$dias} dias. Vocês já guardaram {$total} "
            . "momentos — dois minutos hoje colocam tudo em dia: " . url_absoluta('home')
            . "\n\nTransparência: nós não lemos o diário; este aviso usa apenas datas de acesso e contagens.";
        return $this->enviarParaResponsaveis(
            (int)$familia['id'],
            'O diário ' . $deBebe . ' está sentindo falta de vocês 🌱',
            $conteudo,
            $texto,
            'reengajamento'
        );
    }

    private function enviarResumoMensal(array $familia): bool
    {
        $inicioMes = date('Y-m-01 00:00:00', strtotime('first day of last month'));
        $fimMes = date('Y-m-01 00:00:00');
        $stats = $this->buscarUm(
            "SELECT COUNT(*) AS registros, COUNT(DISTINCT DATE(r.inicio)) AS dias,
                    SUM(CASE WHEN c.slug IN ('soneca','sono-noturno') AND r.fim IS NOT NULL
                        THEN TIMESTAMPDIFF(MINUTE, r.inicio, r.fim) ELSE 0 END) AS sono_min,
                    SUM(c.slug = 'marco-desenvolvimento') AS marcos
               FROM registros r JOIN categorias c ON c.id = r.categoria_id
              WHERE r.familia_id = :familia AND r.excluido_em IS NULL
                AND r.inicio >= :inicio AND r.inicio < :fim",
            ['familia' => (int)$familia['id'], 'inicio' => $inicioMes, 'fim' => $fimMes]
        ) ?? [];
        if ((int)($stats['registros'] ?? 0) === 0) {
            return false; // mês anterior sem atividade: sem resumo
        }
        $medicoes = $this->buscarUm(
            'SELECT COUNT(*) AS total FROM medicoes
              WHERE familia_id = :familia AND criado_em >= :inicio AND criado_em < :fim',
            ['familia' => (int)$familia['id'], 'inicio' => $inicioMes, 'fim' => $fimMes]
        );
        $nomeMes = self::nomeDoMes((int)date('n', strtotime($inicioMes)));
        $sonoSemana = (int)round(((int)($stats['sono_min'] ?? 0)) / 60 / 4.3);
        $bebe = $this->nomeDoBebe((int)$familia['id']);
        $deBebe = $bebe !== null ? 'de ' . $bebe : 'do bebê';

        $linhas = '📖 <strong>' . (int)$stats['registros'] . ' atividades</strong> registradas em <strong>'
            . (int)$stats['dias'] . ' dias</strong><br>';
        if ($sonoSemana > 0) {
            $linhas .= '😴 <strong>' . $sonoSemana . ' horas de sono</strong> acompanhadas por semana, em média<br>';
        }
        if ((int)($stats['marcos'] ?? 0) > 0) {
            $linhas .= '🎉 <strong>' . (int)$stats['marcos'] . ' marco(s) de desenvolvimento</strong> celebrado(s)<br>';
        }
        if ((int)($medicoes['total'] ?? 0) > 0) {
            $linhas .= '📈 <strong>' . (int)$medicoes['total'] . ' nova(s) medição(ões)</strong> na curva de crescimento<br>';
        }

        $conteudo = static fn(string $nome): string =>
            '<h2 style="margin:0 0 12px; font-size:19px;">Que mês, hein? Olha o que vocês construíram em ' . $nomeMes . ' 👏</h2>'
            . '<p style="margin:0 0 12px;">Olá, <strong>' . e($nome) . '</strong>! O diário ' . e($deBebe) . ' fechou o mês assim:</p>'
            . self::caixinha('<p style="margin:0; font-size:15px; line-height:1.9;">' . $linhas . '</p>')
            . '<p style="margin:0 0 12px;">Poucas famílias conseguem essa constância — e é exatamente ela que faz a '
            . 'diferença na consulta: o pediatra vê a história completa, não um retrato isolado.</p>'
            . ServicoEmail::botao('Ver os relatórios do mês', url_absoluta('relatorios.index'))
            . '<p style="margin:12px 0 0; font-size:12.5px; color:#A8A296;">Este resumo é gerado com as contagens do '
            . 'próprio diário de vocês e enviado apenas aos responsáveis da família.</p>';
        $texto = static fn(string $nome): string =>
            "Olá, {$nome}! O diário {$deBebe} em {$nomeMes}: {$stats['registros']} atividades em {$stats['dias']} dias."
            . "\nVeja os relatórios: " . url_absoluta('relatorios.index');
        return $this->enviarParaResponsaveis(
            (int)$familia['id'],
            'O mês ' . $deBebe . ' no Diário do Bebê 🗓️',
            $conteudo,
            $texto,
            'resumo_mensal'
        );
    }

    private function enviarMesversario(array $familia, array $crianca): bool
    {
        $meses = (int)$crianca['meses'];
        $nomeBebe = (string)($crianca['apelido'] ?: $crianca['nome']);
        $conteudo = static fn(string $nome): string =>
            '<h2 style="margin:0 0 12px; font-size:19px;">' . $meses . ' meses de ' . e($nomeBebe) . ' — parabéns, família! 🎉</h2>'
            . '<p style="margin:0 0 12px;">Olá, <strong>' . e($nome) . '</strong>! Hoje ' . e($nomeBebe) . ' completa <strong>'
            . $meses . ' ' . ($meses === 1 ? 'mês' : 'meses') . '</strong> — e cada mês novo é uma página que vale registrar.</p>'
            . '<p style="margin:0 0 12px;">O mêsversário é o momento ideal para a <strong>pesagem do mês</strong>: um ponto '
            . 'por mês desenha a curva de crescimento que o pediatra usa para acompanhar a evolução. Leva um minuto, e o '
            . 'percentil da OMS aparece na hora na ficha.</p>'
            . ServicoEmail::botao('Registrar a medição do mês', url_absoluta('crianca.medicoes', ['slug' => (string)$crianca['slug']]))
            . '<p style="margin:12px 0 0;">💡 <strong>Dica:</strong> se surgiu alguma novidade — rolou, sorriu, sustentou a '
            . 'cabeça — registre como <em>marco de desenvolvimento</em>: vira memória e chega ao pediatra na próxima ficha.</p>';
        $texto = static fn(string $nome): string =>
            "Olá, {$nome}! Hoje {$nomeBebe} completa {$meses} " . ($meses === 1 ? 'mês' : 'meses') . "!\n"
            . "Aproveite para registrar a pesagem do mês: "
            . url_absoluta('crianca.medicoes', ['slug' => (string)$crianca['slug']]);
        return $this->enviarParaResponsaveis(
            (int)$familia['id'],
            $nomeBebe . ' completa ' . $meses . ' ' . ($meses === 1 ? 'mês' : 'meses') . ' hoje! 🎂',
            $conteudo,
            $texto,
            'mesversario',
            (int)$crianca['id']
        );
    }

    private function enviarPreConsulta(array $familia, array $consulta): bool
    {
        $nomeBebe = (string)($consulta['crianca_apelido'] ?: $consulta['crianca_nome']);
        $dataRetorno = data_br((string)$consulta['retorno_em'] . ' 00:00:00', 'd/m');
        $diaSemana = self::nomeDoDia((int)date('w', strtotime((string)$consulta['retorno_em'])));
        $conteudo = static fn(string $nome): string =>
            '<h2 style="margin:0 0 12px; font-size:19px;">' . e(ucfirst($diaSemana)) . ' tem consulta — a ficha se prepara sozinha</h2>'
            . '<p style="margin:0 0 12px;">Olá, <strong>' . e($nome) . '</strong>! O retorno de <strong>' . e($nomeBebe)
            . '</strong> está marcado para <strong>' . e($diaSemana) . ', ' . e($dataRetorno) . '</strong>.</p>'
            . '<p style="margin:0 0 12px;">Antes de sair de casa, gere a <strong>ficha da consulta</strong>: o pediatra abre '
            . 'pelo QR code e vê as curvas de crescimento, as vacinas, o resumo dos últimos 30 dias e as novidades desde a '
            . 'última consulta — e ainda devolve as medidas do dia direto para o app, para vocês confirmarem.</p>'
            . self::caixinha('<p style="margin:0;">🔒 O link é de <strong>uso único</strong>, vale por 48 horas e não mostra '
                . 'fotos nem o dia a dia detalhado — só o que interessa à consulta.</p>')
            . ServicoEmail::botao('Gerar a ficha da consulta', url_absoluta('consulta.gerar', ['slug' => (string)$consulta['crianca_slug']]));
        $texto = static fn(string $nome): string =>
            "Olá, {$nome}! O retorno de {$nomeBebe} está marcado para {$diaSemana}, {$dataRetorno}.\n"
            . "Gere a ficha da consulta (QR code) antes de sair de casa: "
            . url_absoluta('consulta.gerar', ['slug' => (string)$consulta['crianca_slug']]);
        return $this->enviarParaResponsaveis(
            (int)$familia['id'],
            'Consulta de ' . $nomeBebe . ' chegando — leve a ficha pronta 🩺',
            $conteudo,
            $texto,
            'pre_consulta',
            (int)$consulta['id']
        );
    }

    // ── Consultas auxiliares ──────────────────────────────────

    /** @return array<int,array<string,mixed>> crianças que fazem mêsversário hoje */
    private function mesversariosDeHoje(int $familiaId): array
    {
        // Dia igual ao do nascimento OU último dia do mês quando o dia não existe
        // (bebê nascido dia 31 num mês de 30, por exemplo)
        $criancas = $this->executar(
            'SELECT id, nome, apelido, slug, data_nascimento,
                    TIMESTAMPDIFF(MONTH, data_nascimento, CURDATE()) AS meses
               FROM criancas
              WHERE familia_id = :familia AND ativo = 1 AND data_nascimento IS NOT NULL
                AND (DAY(data_nascimento) = DAY(CURDATE())
                     OR (DAY(CURDATE()) = DAY(LAST_DAY(CURDATE())) AND DAY(data_nascimento) > DAY(CURDATE())))',
            ['familia' => $familiaId]
        )->fetchAll();
        return array_values(array_filter(
            $criancas,
            static fn(array $c): bool => (int)$c['meses'] >= 1 && (int)$c['meses'] <= self::MESVERSARIO_MAXIMO_MESES
        ));
    }

    /** @return array<int,array<string,mixed>> consultas com retorno marcado para daqui a 2 dias */
    private function retornosEmDoisDias(int $familiaId): array
    {
        return $this->executar(
            'SELECT co.id, co.retorno_em, c.nome AS crianca_nome, c.apelido AS crianca_apelido, c.slug AS crianca_slug
               FROM consultas co
               JOIN criancas c ON c.id = co.crianca_id
              WHERE co.familia_id = :familia AND c.ativo = 1
                AND co.retorno_em = DATE_ADD(CURDATE(), INTERVAL 2 DAY)',
            ['familia' => $familiaId]
        )->fetchAll();
    }

    /** Nome (apelido) do bebê quando a família tem uma única criança ativa. */
    private function nomeDoBebe(int $familiaId): ?string
    {
        $criancas = $this->executar(
            'SELECT nome, apelido FROM criancas WHERE familia_id = :familia AND ativo = 1',
            ['familia' => $familiaId]
        )->fetchAll();
        if (count($criancas) !== 1) {
            return null;
        }
        $nome = (string)($criancas[0]['apelido'] ?: $criancas[0]['nome']);
        return trim(explode(' ', $nome)[0]) ?: null;
    }

    /**
     * Envia aos responsáveis ativos da família, personalizado pelo primeiro nome.
     * @param callable(string):string $conteudoHtml
     * @param callable(string):string $corpoTexto
     */
    private function enviarParaResponsaveis(
        int $familiaId,
        string $assunto,
        callable $conteudoHtml,
        callable $corpoTexto,
        string $tipo,
        ?int $referenciaId = null
    ): bool {
        $email = new ServicoEmail();
        $enviado = false;
        foreach ((new RepositorioUsuariosFamilia($familiaId))->responsaveisParaNotificar() as $responsavel) {
            if ((string)$responsavel['email'] === '') {
                continue;
            }
            $nome = mb_convert_case(trim(explode(' ', (string)$responsavel['nome'])[0]), MB_CASE_TITLE, 'UTF-8');
            if ($email->enviarHtml(
                (string)$responsavel['email'],
                $assunto,
                $conteudoHtml($nome),
                $corpoTexto($nome),
                $tipo,
                $referenciaId,
                $familiaId
            )) {
                $enviado = true;
            }
        }
        return $enviado;
    }

    // ── Pedaços visuais dos e-mails ───────────────────────────

    private static function caixinha(string $html): string
    {
        return '<div style="border:1px solid #EDE6DB; border-radius:13px; padding:12px 16px; '
            . 'margin:14px 0; background:#FAF6F0;">' . $html . '</div>';
    }

    /** Botão verde do WhatsApp de suporte — some sozinho se o número for apagado. */
    private static function botaoWhatsapp(): string
    {
        $link = (new RepositorioConfiguracoesPlataforma())
            ->linkWhatsappSuporte('Olá! Preciso de uma ajuda com o Diário do Bebê 💚');
        if ($link === null) {
            return '';
        }
        return '<p style="margin:6px 0 0; text-align:center;">'
            . '<a href="' . e($link) . '" style="display:inline-block; background-color:#25D366; color:#FFFFFF; '
            . 'text-decoration:none; font-weight:700; font-size:14px; padding:11px 26px; border-radius:999px;">'
            . '💬 Chamar no WhatsApp</a></p>';
    }

    private static function notaLgpd(): string
    {
        return '<p style="margin:16px 0 0; font-size:12.5px; color:#A8A296;">Transparência: nós '
            . '<strong>não lemos o diário da sua família</strong>. Este aviso usa apenas o registro técnico de '
            . 'acessos e contagens — o conteúdo é só de vocês.</p>';
    }

    private static function nomeDoMes(int $mes): string
    {
        return ['janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho',
                'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'][$mes - 1] ?? '';
    }

    private static function nomeDoDia(int $dia): string
    {
        return ['domingo', 'segunda-feira', 'terça-feira', 'quarta-feira',
                'quinta-feira', 'sexta-feira', 'sábado'][$dia] ?? '';
    }
}
