/**
 * Notificações push no aparelho (Web Push): ativar, testar e desativar.
 * A chave pública VAPID vem no data-vapid do cartão; a inscrição é enviada
 * para a API com o header X-CSRF, como as outras chamadas do app.
 */
'use strict';

(function () {
    const cartao = document.querySelector('[data-push-cartao]');
    if (!cartao) return;

    const botaoAtivar = cartao.querySelector('[data-push-ativar]');
    const botaoTestar = cartao.querySelector('[data-push-testar]');
    const botaoDesativar = cartao.querySelector('[data-push-desativar]');
    const texto = cartao.querySelector('[data-push-texto]');
    const notaIos = cartao.querySelector('[data-push-ios]');

    const ehIos = /iphone|ipad|ipod/i.test(navigator.userAgent);
    const instalado = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
    const suportado = 'serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window;

    function mostrar(mensagem, estado) {
        if (texto) texto.textContent = mensagem;
        if (botaoAtivar) botaoAtivar.hidden = estado === 'ativo' || estado === 'indisponivel';
        if (botaoTestar) botaoTestar.hidden = estado !== 'ativo';
        if (botaoDesativar) botaoDesativar.hidden = estado !== 'ativo';
    }

    function chamarApi(rota, corpo) {
        return fetch(urlJs(rota), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF': (window.APP && window.APP.csrf) || '' },
            body: JSON.stringify(corpo || {})
        }).then(function (resposta) { return resposta.json(); });
    }

    function base64ParaBytes(texto) {
        const normal = (texto + '='.repeat((4 - texto.length % 4) % 4)).replace(/-/g, '+').replace(/_/g, '/');
        const bruto = atob(normal);
        return Uint8Array.from(bruto, function (c) { return c.charCodeAt(0); });
    }

    if (!suportado || (ehIos && !instalado)) {
        if (ehIos && notaIos) notaIos.hidden = false;
        mostrar(ehIos
            ? 'Para receber notificações no iPhone, adicione o app à tela de início e abra por lá.'
            : 'Este navegador não suporta notificações push. Tente o Chrome ou instale o app na tela inicial.', 'indisponivel');
        return;
    }

    if (Notification.permission === 'denied') {
        mostrar('As notificações estão bloqueadas nas configurações do navegador para este site. Libere-as e volte aqui.', 'indisponivel');
        return;
    }

    navigator.serviceWorker.ready.then(function (registro) {
        return registro.pushManager.getSubscription();
    }).then(function (inscricao) {
        if (inscricao) {
            // Garante que o servidor conhece este aparelho (ex.: após troca de conta)
            chamarApi('api.push.inscrever', inscricao.toJSON()).catch(function () {});
            mostrar('Notificações ativas neste aparelho. ✅', 'ativo');
        } else {
            mostrar('Receba os avisos na hora, mesmo com o app fechado — como uma mensagem no celular.', 'inativo');
        }
    }).catch(function () {
        mostrar('Não foi possível verificar as notificações agora.', 'inativo');
    });

    if (botaoAtivar) {
        botaoAtivar.addEventListener('click', function () {
            botaoAtivar.disabled = true;
            Notification.requestPermission().then(function (permissao) {
                if (permissao !== 'granted') {
                    botaoAtivar.disabled = false;
                    mostrar('Sem a permissão do navegador não dá para avisar. Tente de novo e toque em "Permitir".', 'inativo');
                    return;
                }
                return navigator.serviceWorker.ready.then(function (registro) {
                    return registro.pushManager.subscribe({
                        userVisibleOnly: true,
                        applicationServerKey: base64ParaBytes(cartao.dataset.vapid || '')
                    });
                }).then(function (inscricao) {
                    return chamarApi('api.push.inscrever', inscricao.toJSON());
                }).then(function (resposta) {
                    botaoAtivar.disabled = false;
                    if (resposta && resposta.ok) {
                        mostrar('Notificações ativas neste aparelho. ✅ Toque em "Enviar um teste" para conferir.', 'ativo');
                    } else {
                        mostrar('O aparelho aceitou, mas o servidor não gravou a inscrição. Tente de novo.', 'inativo');
                    }
                });
            }).catch(function (erro) {
                botaoAtivar.disabled = false;
                mostrar('Não foi possível ativar: ' + (erro && erro.message ? erro.message : 'erro desconhecido'), 'inativo');
            });
        });
    }

    if (botaoTestar) {
        botaoTestar.addEventListener('click', function () {
            botaoTestar.disabled = true;
            chamarApi('api.push.testar').then(function (resposta) {
                botaoTestar.disabled = false;
                mostrar(resposta && resposta.ok
                    ? 'Teste enviado! A notificação deve aparecer em instantes.'
                    : 'O serviço de push não aceitou o envio (' + (resposta ? resposta.falhas : '?') + ' falha). Desative e ative de novo.', 'ativo');
            }).catch(function () { botaoTestar.disabled = false; });
        });
    }

    if (botaoDesativar) {
        botaoDesativar.addEventListener('click', function () {
            navigator.serviceWorker.ready.then(function (registro) {
                return registro.pushManager.getSubscription();
            }).then(function (inscricao) {
                if (!inscricao) return null;
                const endpoint = inscricao.endpoint;
                return inscricao.unsubscribe().then(function () {
                    return chamarApi('api.push.remover', { endpoint: endpoint });
                });
            }).then(function () {
                mostrar('Notificações desativadas neste aparelho.', 'inativo');
            });
        });
    }
})();
