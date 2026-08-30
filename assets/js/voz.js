/**
 * Entrada por voz (Web Speech API) com fallback silencioso para texto:
 * o botão 🎤 só aparece quando o navegador suporta reconhecimento.
 */
'use strict';

(function () {
    const Reconhecimento = window.SpeechRecognition || window.webkitSpeechRecognition;
    if (!Reconhecimento) {
        return; // sem suporte: o campo de texto continua funcionando normalmente
    }

    document.querySelectorAll('[data-voz-para]').forEach(function (botao) {
        const alvo = document.getElementById(botao.dataset.vozPara);
        if (!alvo) return;
        botao.hidden = false;

        let ouvindo = false;
        const reconhecedor = new Reconhecimento();
        reconhecedor.lang = 'pt-BR';
        reconhecedor.interimResults = false;
        reconhecedor.maxAlternatives = 1;

        reconhecedor.onresult = function (evento) {
            const texto = evento.results[0][0].transcript;
            alvo.value = (alvo.value ? alvo.value + ' ' : '') + texto;
        };
        reconhecedor.onend = function () {
            ouvindo = false;
            botao.textContent = '🎤 Falar';
        };
        reconhecedor.onerror = reconhecedor.onend;

        botao.addEventListener('click', function () {
            if (ouvindo) {
                reconhecedor.stop();
                return;
            }
            ouvindo = true;
            botao.textContent = '⏹ Ouvindo...';
            reconhecedor.start();
        });
    });
})();
