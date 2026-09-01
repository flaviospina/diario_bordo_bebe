/**
 * Janela de registro por horário (folha inferior no celular, central no desktop):
 * abre pelo botão flutuante ou pelo toque em um horário da grade, com a hora
 * escolhida já aplicada aos links das categorias.
 */
'use strict';

(function () {
    const folha = document.getElementById('folha-registro');
    if (!folha) return;

    const marcadorDia = document.querySelector('[data-dia-atual]');
    const dia = marcadorDia ? marcadorDia.dataset.diaAtual : '';
    const alvoHora = folha.querySelector('[data-folha-hora]');

    function abrir(hora) {
        if (alvoHora) alvoHora.textContent = hora;
        folha.querySelectorAll('[data-link-registro]').forEach(function (link) {
            const parametros = new URLSearchParams();
            if (dia) parametros.set('data', dia);
            parametros.set('hora', hora);
            link.href = link.dataset.base + '?' + parametros.toString();
        });
        folha.hidden = false;
        document.body.style.overflow = 'hidden';
        const fechar = folha.querySelector('.folha-fechar');
        if (fechar) fechar.focus();
    }

    function fechar() {
        folha.hidden = true;
        document.body.style.overflow = '';
    }

    document.querySelectorAll('[data-abrir-folha]').forEach(function (botao) {
        botao.addEventListener('click', function () {
            abrir(botao.dataset.hora || new Date().toTimeString().slice(0, 5));
        });
    });
    folha.querySelectorAll('[data-fechar-folha]').forEach(function (botao) {
        botao.addEventListener('click', fechar);
    });
    document.addEventListener('keydown', function (evento) {
        if (evento.key === 'Escape' && !folha.hidden) fechar();
    });
})();
