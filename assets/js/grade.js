/**
 * Tela "Meu Dia": indicador permanente de conexão.
 * O contador de fila offline é atualizado por offline.js (Fase 4).
 */
'use strict';

(function () {
    const indicador = document.getElementById('indicador-conexao');
    if (!indicador) return;

    window.atualizarIndicadorConexao = function (pendentes) {
        const online = navigator.onLine;
        indicador.dataset.estado = online ? 'online' : 'offline';
        if (!online) {
            indicador.textContent = pendentes > 0
                ? 'Offline — ' + pendentes + ' registro(s) na fila'
                : 'Offline';
        } else {
            indicador.textContent = pendentes > 0
                ? 'Sincronizando ' + pendentes + '...'
                : 'Online';
        }
    };

    window.addEventListener('online', function () { window.atualizarIndicadorConexao(window.__filaPendentes || 0); });
    window.addEventListener('offline', function () { window.atualizarIndicadorConexao(window.__filaPendentes || 0); });
    window.atualizarIndicadorConexao(0);
})();
