/**
 * Diário do Bebê — base do front-end.
 * Nenhuma URL literal no JavaScript: tudo passa por urlJs(), que usa o mapa
 * de rotas nomeadas exposto pelo PHP em window.APP (mesma fonte do helper url()).
 */
'use strict';

/**
 * Gera o caminho de uma rota nomeada no cliente.
 * Ex.: urlJs('registro.ver', { codigo: 'abc123def456' })
 */
function urlJs(nomeRota, parametros = {}) {
    const app = window.APP || { basePath: '', rotas: {} };
    const padrao = app.rotas[nomeRota];
    if (!padrao) {
        throw new Error('Rota nomeada inexistente no cliente: ' + nomeRota);
    }
    const caminho = padrao.replace(/\{(\w+)\}/g, (tudo, nome) => {
        if (!(nome in parametros)) {
            throw new Error('Parâmetro {' + nome + '} ausente para a rota ' + nomeRota);
        }
        return encodeURIComponent(String(parametros[nome]));
    });
    return (app.basePath + caminho) || '/';
}

window.urlJs = urlJs;

// PWA: registra o service worker (servido pelo PHP com o BASE_PATH correto)
if ('serviceWorker' in navigator && window.APP && window.APP.rotas && window.APP.rotas['pwa.sw']) {
    window.addEventListener('load', function () {
        navigator.serviceWorker
            .register(urlJs('pwa.sw'), { scope: (window.APP.basePath || '') + '/' })
            .catch(function (erro) { console.warn('Service worker não registrado:', erro); });
    });
}
