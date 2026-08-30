<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Requisicao;
use App\Core\Visao;

/**
 * PWA: manifest e service worker são GERADOS pelo PHP para embutir o
 * BASE_PATH — assim o escopo e o cache funcionam tanto em subpasta
 * (itthrive.com.br/diariobebe) quanto em domínio próprio, sem editar nada.
 */
final class PwaController
{
    public function manifest(Requisicao $requisicao): void
    {
        header('Content-Type: application/manifest+json; charset=utf-8');
        header('Cache-Control: public, max-age=86400');
        echo json_encode([
            'name' => APP_NOME,
            'short_name' => 'Diário',
            'description' => 'Controle da rotina diária do bebê, preenchido pelo cuidador e acompanhado pelos pais.',
            'lang' => 'pt-BR',
            'start_url' => (BASE_PATH ?: '') . '/',
            'scope' => (BASE_PATH ?: '') . '/',
            'display' => 'standalone',
            'orientation' => 'portrait',
            'theme_color' => '#2f6f6a',
            'background_color' => '#f5f3ee',
            'icons' => [
                ['src' => BASE_PATH . '/assets/img/icones/icone-192.png', 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => BASE_PATH . '/assets/img/icones/icone-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any maskable'],
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function serviceWorker(Requisicao $requisicao): void
    {
        header('Content-Type: application/javascript; charset=utf-8');
        // O SW é servido pela raiz do BASE_PATH, então já controla todo o app
        header('Service-Worker-Allowed: ' . ((BASE_PATH ?: '') . '/'));
        header('Cache-Control: no-cache');

        $base = BASE_PATH;
        $versao = APP_VERSAO;
        $precache = json_encode([
            $base . '/assets/css/app.css?v=' . $versao,
            $base . '/assets/js/base.js?v=' . $versao,
            $base . '/assets/js/grade.js?v=' . $versao,
            $base . '/assets/js/formularios.js?v=' . $versao,
            $base . '/assets/js/voz.js?v=' . $versao,
            $base . '/assets/js/offline.js?v=' . $versao,
            $base . '/assets/img/icones/icone-192.png',
            $base . '/assets/img/icones/icone-512.png',
            $base . '/offline',
        ], JSON_UNESCAPED_SLASHES);

        echo <<<JS
/* Service worker do Diário do Bebê — gerado pelo servidor com BASE_PATH. */
'use strict';
const CACHE = 'diariobebe-v{$versao}';
const BASE = '{$base}';
const PRECACHE = {$precache};

self.addEventListener('install', (evento) => {
    evento.waitUntil(caches.open(CACHE).then((cache) => cache.addAll(PRECACHE)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', (evento) => {
    evento.waitUntil(
        caches.keys()
            .then((chaves) => Promise.all(chaves.filter((c) => c !== CACHE).map((c) => caches.delete(c))))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (evento) => {
    const requisicao = evento.request;
    if (requisicao.method !== 'GET') {
        return; // POSTs offline são enfileirados pelo app (IndexedDB), não pelo SW
    }
    const url = new URL(requisicao.url);
    if (url.origin !== self.location.origin) {
        return;
    }

    // Navegação: rede primeiro (conteúdo sempre fresco), cache como reserva
    if (requisicao.mode === 'navigate') {
        evento.respondWith(
            fetch(requisicao)
                .then((resposta) => {
                    const copia = resposta.clone();
                    caches.open(CACHE).then((cache) => cache.put(requisicao, copia));
                    return resposta;
                })
                .catch(() => caches.match(requisicao).then((em) => em || caches.match(BASE + '/offline')))
        );
        return;
    }

    // Assets: cache primeiro, rede como complemento
    evento.respondWith(
        caches.match(requisicao).then((emCache) => {
            if (emCache) return emCache;
            return fetch(requisicao).then((resposta) => {
                if (resposta.ok && url.pathname.startsWith(BASE + '/assets/')) {
                    const copia = resposta.clone();
                    caches.open(CACHE).then((cache) => cache.put(requisicao, copia));
                }
                return resposta;
            });
        })
    );
});
JS;
        exit;
    }

    /** Página exibida quando não há rede nem cache da rota pedida. */
    public function offline(Requisicao $requisicao): void
    {
        Visao::exibir('pwa/offline', ['titulo' => 'Sem conexão'], 'autenticacao');
    }
}
