<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Roteador próprio com rotas nomeadas, parâmetros ({codigo}, {data}...),
 * restrição por método HTTP e middlewares por rota.
 *
 * Regra de projeto: NENHUMA URL literal fora daqui. Links são sempre gerados
 * pelo helper url('nome.da.rota', [...]) — se o caminho base mudar, nada quebra.
 */
final class Rota
{
    /** @var string[] */
    public array $middlewares = [];

    public function __construct(
        public readonly string $metodo,
        public readonly string $padrao,
        public readonly string $acao,
        public string $nome = ''
    ) {
    }

    public function nome(string $nome): self
    {
        $this->nome = $nome;
        return $this;
    }

    /** Ex.: ->middleware('autenticado', 'papel:responsavel,admin_familia') */
    public function middleware(string ...$nomes): self
    {
        foreach ($nomes as $n) {
            $this->middlewares[] = $n;
        }
        return $this;
    }
}

final class Roteador
{
    /** @var Rota[] */
    private array $rotas = [];

    /** @var array<string,Rota> */
    private array $porNome = [];

    public function get(string $padrao, string $acao): Rota
    {
        return $this->adicionar('GET', $padrao, $acao);
    }

    public function post(string $padrao, string $acao): Rota
    {
        return $this->adicionar('POST', $padrao, $acao);
    }

    public function adicionar(string $metodo, string $padrao, string $acao): Rota
    {
        $rota = new Rota(strtoupper($metodo), $padrao, $acao);
        $this->rotas[] = $rota;
        return $rota;
    }

    /** Indexa as rotas por nome depois que config/rotas.php as definiu. */
    public function indexar(): void
    {
        foreach ($this->rotas as $rota) {
            if ($rota->nome !== '') {
                $this->porNome[$rota->nome] = $rota;
            }
        }
    }

    /**
     * Gera o caminho de uma rota nomeada, já com BASE_PATH.
     * Lança exceção em nome inexistente ou parâmetro faltante — erro de programação
     * deve aparecer cedo, não virar link quebrado em produção.
     */
    public function caminho(string $nome, array $parametros = []): string
    {
        $rota = $this->porNome[$nome] ?? null;
        if ($rota === null) {
            throw new \InvalidArgumentException("Rota nomeada inexistente: {$nome}");
        }
        $caminho = preg_replace_callback(
            '/\{(\w+)\}/',
            static function (array $m) use ($parametros, $nome): string {
                if (!array_key_exists($m[1], $parametros)) {
                    throw new \InvalidArgumentException("Parâmetro {{$m[1]}} ausente para a rota {$nome}");
                }
                return rawurlencode((string)$parametros[$m[1]]);
            },
            $rota->padrao
        );
        return (BASE_PATH . $caminho) ?: '/';
    }

    /** Mapa nome => padrão, exposto ao JavaScript (window.APP.rotas). */
    public function mapaParaJs(): array
    {
        $mapa = [];
        foreach ($this->porNome as $nome => $rota) {
            $mapa[$nome] = $rota->padrao;
        }
        return $mapa;
    }

    /**
     * Encontra a rota da requisição. Retorna [Rota, parametros] ou null (404).
     * Se o caminho não estiver na forma canônica (minúsculas, sem barra final),
     * responde 301 antes de casar — todos os identificadores públicos do sistema
     * (códigos, slugs, tokens) são gerados em minúsculas justamente para isso.
     */
    public function resolver(Requisicao $requisicao): ?array
    {
        $caminho = $requisicao->caminho;

        $canonico = mb_strtolower($caminho);
        if (strlen($canonico) > 1) {
            $canonico = rtrim($canonico, '/');
        }
        if ($canonico === '') {
            $canonico = '/';
        }
        if ($canonico !== $caminho && $requisicao->metodo === 'GET') {
            Resposta::redirecionarCanonico($canonico);
        }

        $metodoPermitido = false;
        foreach ($this->rotas as $rota) {
            $regex = $this->padraoParaRegex($rota->padrao);
            if (preg_match($regex, $canonico, $capturas) === 1) {
                if ($rota->metodo !== $requisicao->metodo) {
                    $metodoPermitido = true; // caminho existe, método não — 405
                    continue;
                }
                $parametros = array_filter($capturas, 'is_string', ARRAY_FILTER_USE_KEY);
                return [$rota, array_map('strval', $parametros)];
            }
        }

        if ($metodoPermitido) {
            http_response_code(405);
        }
        return null;
    }

    private function padraoParaRegex(string $padrao): string
    {
        $partes = preg_split('/(\{\w+\})/', $padrao, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [];
        $regex = '';
        foreach ($partes as $parte) {
            if (preg_match('/^\{(\w+)\}$/', $parte, $m) === 1) {
                $regex .= '(?P<' . $m[1] . '>[^/]+)';
            } else {
                $regex .= preg_quote($parte, '#');
            }
        }
        return '#^' . $regex . '$#u';
    }
}
