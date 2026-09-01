/**
 * Ficha para consulta (Alteração 01).
 * - Página "gerar": mostra o QR code em tela cheia e copia o link.
 * - Página pública do pediatra: envia o formulário sem recarregar; o link é
 *   de uso único, então o retorno de sucesso troca o formulário pelo aviso.
 */
(function () {
    'use strict';

    // ── QR em tela cheia (página do responsável) ──────────────
    var tela = document.querySelector('[data-qr-tela]');
    if (tela && typeof QRCode !== 'undefined') {
        var destino = tela.querySelector('[data-qr-destino]');
        var nome = tela.querySelector('[data-qr-nome]');

        document.querySelectorAll('[data-mostrar-qr]').forEach(function (botao) {
            botao.addEventListener('click', function () {
                destino.innerHTML = '';
                new QRCode(destino, {
                    text: botao.dataset.url,
                    width: 240,
                    height: 240,
                    colorDark: '#33302B',
                    colorLight: '#FFFFFF',
                    correctLevel: QRCode.CorrectLevel.M
                });
                nome.textContent = 'Ficha de ' + botao.dataset.nome;
                tela.hidden = false;
            });
        });
        tela.addEventListener('click', function () { tela.hidden = true; });
    }

    document.querySelectorAll('[data-copiar-link]').forEach(function (botao) {
        botao.addEventListener('click', function () {
            var original = botao.textContent;
            var confirmar = function () {
                botao.textContent = 'Copiado!';
                setTimeout(function () { botao.textContent = original; }, 2000);
            };
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(botao.dataset.url).then(confirmar);
            } else {
                window.prompt('Copie o link:', botao.dataset.url);
            }
        });
    });

    // ── Envio do pediatra (página pública) ────────────────────
    var formulario = document.querySelector('[data-envio-consulta]');
    if (formulario) {
        formulario.addEventListener('submit', function (evento) {
            evento.preventDefault();
            var botao = formulario.querySelector('button[type="submit"]');
            var caixaErro = formulario.querySelector('[data-erro-envio]');
            caixaErro.hidden = true;
            botao.disabled = true;
            botao.textContent = 'Enviando…';

            fetch(formulario.dataset.endpoint, {
                method: 'POST',
                body: new FormData(formulario)
            }).then(function (resposta) {
                return resposta.json().then(function (dados) {
                    return { ok: resposta.ok, dados: dados };
                });
            }).then(function (resultado) {
                if (resultado.ok) {
                    formulario.hidden = true;
                    document.querySelector('[data-envio-ok]').hidden = false;
                    window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' });
                    return;
                }
                caixaErro.textContent = resultado.dados.erro || 'Não foi possível enviar. Tente novamente.';
                caixaErro.hidden = false;
                botao.disabled = false;
                botao.textContent = 'Enviar para a família';
            }).catch(function () {
                caixaErro.textContent = 'Sem conexão no momento. Verifique a internet e tente de novo.';
                caixaErro.hidden = false;
                botao.disabled = false;
                botao.textContent = 'Enviar para a família';
            });
        });
    }
})();
