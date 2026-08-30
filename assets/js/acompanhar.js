/**
 * Tela "Acompanhar": polling a cada 60 s no /api/dia — se a versão do dia
 * mudou (novo registro, edição, exclusão), recarrega a página.
 */
'use strict';

(function () {
    const painel = document.querySelector('[data-acompanhar]');
    if (!painel) return;

    const data = painel.dataset.data;
    const crianca = painel.dataset.crianca;
    let versaoAtual = painel.dataset.versao;

    function verificar() {
        if (!navigator.onLine) return;
        fetch(urlJs('api.dia', { data: data }) + '?crianca=' + encodeURIComponent(crianca), {
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json' }
        }).then(function (resposta) {
            if (!resposta.ok) throw new Error('HTTP ' + resposta.status);
            return resposta.json();
        }).then(function (corpo) {
            if (corpo && corpo.versao && corpo.versao !== versaoAtual) {
                window.location.reload();
            }
        }).catch(function () { /* tenta de novo no próximo ciclo */ });
    }

    setInterval(verificar, 60000);
})();
