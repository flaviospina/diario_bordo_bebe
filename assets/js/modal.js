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
        document.body.classList.add('folha-aberta');
        const fechar = folha.querySelector('.folha-fechar');
        if (fechar) fechar.focus();
    }

    function fechar() {
        folha.hidden = true;
        document.body.style.overflow = '';
        document.body.classList.remove('folha-aberta');
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

    // ── Multi-atividade: seleção de várias categorias de uma vez ──
    const alternador = folha.querySelector('[data-modo-multi]');
    const barra = folha.querySelector('[data-barra-multi]');
    const linkVarios = folha.querySelector('[data-registrar-varios]');
    const contagem = folha.querySelector('[data-contagem-multi]');
    if (alternador && barra && linkVarios) {
        let selecionadas = [];

        function atualizarBarra() {
            if (contagem) contagem.textContent = String(selecionadas.length);
            barra.hidden = selecionadas.length < 2;
            const parametros = new URLSearchParams();
            parametros.set('categorias', selecionadas.join(','));
            if (dia) parametros.set('data', dia);
            if (alvoHora) parametros.set('hora', alvoHora.textContent);
            linkVarios.href = linkVarios.dataset.base + '?' + parametros.toString();
        }

        function limparSelecao() {
            selecionadas = [];
            folha.querySelectorAll('.opcao-selecionada').forEach(function (item) {
                item.classList.remove('opcao-selecionada');
            });
            atualizarBarra();
        }

        alternador.addEventListener('change', limparSelecao);

        folha.querySelectorAll('[data-link-registro]').forEach(function (link) {
            link.addEventListener('click', function (evento) {
                if (!alternador.checked) return; // modo normal: navega direto
                evento.preventDefault();
                const slug = link.dataset.slug;
                const posicao = selecionadas.indexOf(slug);
                if (posicao >= 0) {
                    selecionadas.splice(posicao, 1);
                    link.classList.remove('opcao-selecionada');
                } else if (selecionadas.length < 6) {
                    selecionadas.push(slug);
                    link.classList.add('opcao-selecionada');
                }
                atualizarBarra();
            });
        });
    }
})();
