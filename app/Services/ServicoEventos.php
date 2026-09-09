<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\RepositorioCriancas;
use App\Repositories\RepositorioEventos;
use App\Repositories\RepositorioUsuariosFamilia;

/**
 * Eventos importantes do bebê (Rodada 2): marco de desenvolvimento,
 * intercorrência, medição confirmada e vacina aplicada viram uma linha em
 * eventos_importantes e um e-mail imediato aos responsáveis. Na próxima
 * consulta, a ficha do pediatra mostra tudo em "novidades desde a última
 * consulta" — o pediatra fica sabendo sem depender da memória de ninguém.
 *
 * Nunca lança exceção para o fluxo que o chamou: registrar o evento é um
 * efeito colateral — falhar aqui não pode derrubar o registro original.
 */
final class ServicoEventos
{
    private const ROTULOS = [
        'marco' => 'Marco de desenvolvimento',
        'intercorrencia' => 'Intercorrência',
        'medicao' => 'Nova medição confirmada',
        'vacina' => 'Vacina aplicada',
    ];

    public function __construct(private readonly ?int $familiaId = null)
    {
    }

    /**
     * Registra o evento (idempotente por referência) e envia o e-mail.
     * @param bool $enviarEmail false quando outro aviso equivalente já saiu
     */
    public function registrar(
        int $criancaId,
        string $tipo,
        string $titulo,
        ?string $descricao,
        ?string $referenciaTabela,
        ?int $referenciaId,
        string $ocorridoEm,
        bool $enviarEmail = true
    ): void {
        try {
            $eventos = new RepositorioEventos($this->familiaId);
            if ($referenciaTabela !== null && $referenciaId !== null
                && $eventos->existePorReferencia($tipo, $referenciaTabela, $referenciaId)) {
                return; // já registrado (confirmação manual + automática, reenvio etc.)
            }
            $eventoId = $eventos->criar($criancaId, $tipo, $titulo, $descricao, $referenciaTabela, $referenciaId, $ocorridoEm);
            if ($enviarEmail && $this->enviarEmail($criancaId, $tipo, $titulo, $descricao, $ocorridoEm, $eventoId)) {
                $eventos->marcarEmailEnviado($eventoId);
            }
        } catch (\Throwable $excecao) {
            error_log('Falha ao registrar evento importante: ' . $excecao->getMessage());
        }
    }

    private function enviarEmail(int $criancaId, string $tipo, string $titulo, ?string $descricao, string $ocorridoEm, int $eventoId): bool
    {
        $familiaId = $this->familiaId ?? \App\Core\Autenticacao::familiaId();
        $crianca = null;
        foreach ((new RepositorioCriancas($familiaId))->listar(false) as $linha) {
            if ((int)$linha['id'] === $criancaId) {
                $crianca = $linha;
            }
        }
        if ($crianca === null) {
            return false;
        }
        $nome = (string)($crianca['apelido'] ?: $crianca['nome']);
        $rotulo = self::ROTULOS[$tipo] ?? 'Evento importante';
        $quando = data_br($ocorridoEm, 'd/m/Y \à\s H:i');
        $url = url_absoluta('crianca.ver', ['slug' => (string)$crianca['slug']]);

        $conteudo = '<h2 style="margin:0 0 12px; font-size:19px;">' . e($rotulo) . ' — ' . e($nome) . '</h2>'
            . '<p style="margin:0 0 10px;"><strong>' . e($titulo) . '</strong></p>'
            . ($descricao !== null && $descricao !== ''
                ? '<p style="margin:0 0 10px; white-space:pre-line;">' . e($descricao) . '</p>' : '')
            . '<p style="margin:0 0 4px; font-size:13px; color:#A8A296;">Registrado em ' . e($quando) . '.</p>'
            . ServicoEmail::botao('Abrir a ficha de ' . $nome, $url)
            . '<p style="margin:12px 0 0; font-size:13px; color:#A8A296;">Este evento também aparecerá para o pediatra '
            . 'na próxima ficha de consulta, em "novidades desde a última consulta".</p>';
        $texto = "{$rotulo} — {$nome}\n\n{$titulo}\n"
            . ($descricao !== null && $descricao !== '' ? $descricao . "\n" : '')
            . "Registrado em {$quando}.\nVeja em: {$url}";

        $email = new ServicoEmail();
        $enviado = false;
        foreach ((new RepositorioUsuariosFamilia($familiaId))->responsaveisParaNotificar() as $responsavel) {
            if ((string)$responsavel['email'] === '') {
                continue;
            }
            if ($email->enviarHtml(
                (string)$responsavel['email'],
                $rotulo . ' de ' . $nome . ' — Diário do Bebê',
                $conteudo,
                $texto,
                'evento_importante',
                $eventoId,
                $familiaId
            )) {
                $enviado = true;
            }
        }
        return $enviado;
    }
}
