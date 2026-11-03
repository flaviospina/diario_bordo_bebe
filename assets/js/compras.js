/**
 * Lista de compras semanal: marca/desmarca "no carrinho" salvando no servidor
 * (fonte de verdade), com atualização otimista, reversão em falha, contadores
 * por categoria, filtros e confirmação de ações destrutivas.
 */
'use strict';

(function () {
    const bloco = document.querySelector('[data-compras]');

    // Confirmação para formulários marcados (desmarcar tudo, remover item)
    document.querySelectorAll('form[data-confirmar]').forEach(function (form) {
        form.addEventListener('submit', function (evento) {
            if (!window.confirm(form.dataset.confirmar)) evento.preventDefault();
        });
    });

    if (!bloco) return;
    const podeMarcar = bloco.dataset.podeMarcar === '1';
    const estado = bloco.querySelector('[data-estado-salvar]');
    const comprados = bloco.querySelector('[data-comprados]');
    const porcentagem = bloco.querySelector('[data-porcentagem]');
    const barra = bloco.querySelector('[data-barra]');

    function avisar(texto, erro) {
        if (!estado) return;
        estado.textContent = texto;
        estado.style.color = erro ? 'var(--pessego-escuro)' : '';
        if (!erro && texto) setTimeout(function () { if (estado.textContent === texto) estado.textContent = ''; }, 2500);
    }

    function aplicarResposta(dados) {
        if (comprados) comprados.textContent = dados.comprados;
        if (porcentagem) porcentagem.textContent = dados.porcentagem;
        if (barra) {
            barra.style.width = dados.porcentagem + '%';
            barra.parentElement.setAttribute('aria-valuenow', dados.porcentagem);
        }
        Object.keys(dados.categorias || {}).forEach(function (nome) {
            const cat = bloco.querySelector('[data-categoria="' + CSS.escape(nome) + '"] [data-cat-comprados]');
            if (cat) cat.textContent = dados.categorias[nome].comprados;
        });
        aplicarFiltro();
    }

    bloco.querySelectorAll('.item-check').forEach(function (caixa) {
        caixa.addEventListener('change', function () {
            if (!podeMarcar) { caixa.checked = !caixa.checked; return; }
            const li = caixa.closest('.item-compra');
            const novo = caixa.checked;
            li.classList.toggle('item-comprado', novo);
            li.dataset.comprado = novo ? '1' : '0';
            caixa.disabled = true;
            avisar('Salvando…', false);
            fetch(urlJs('api.compras.marcar'), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF': (window.APP && window.APP.csrf) || '' },
                body: JSON.stringify({ item_id: Number(li.dataset.item), comprado: novo })
            }).then(function (r) { return r.json(); }).then(function (dados) {
                caixa.disabled = false;
                if (!dados || !dados.ok) throw new Error((dados && dados.erro) || 'resposta inválida');
                aplicarResposta(dados);
                avisar('Salvo ✓', false);
            }).catch(function () {
                // Reverte: o servidor é a fonte de verdade
                caixa.disabled = false;
                caixa.checked = !novo;
                li.classList.toggle('item-comprado', !novo);
                li.dataset.comprado = !novo ? '1' : '0';
                avisar('Sem conexão — a marcação não foi salva. Tente de novo.', true);
            });
        });
    });

    // Filtros Todos / Pendentes / No carrinho
    let filtro = 'todos';
    function aplicarFiltro() {
        bloco.querySelectorAll('.item-compra').forEach(function (li) {
            const marcado = li.dataset.comprado === '1';
            li.hidden = (filtro === 'pendentes' && marcado) || (filtro === 'marcados' && !marcado);
        });
        bloco.querySelectorAll('.categoria-compras').forEach(function (cat) {
            cat.hidden = cat.querySelectorAll('.item-compra:not([hidden])').length === 0;
        });
    }
    bloco.querySelectorAll('[data-filtro]').forEach(function (botao) {
        botao.addEventListener('click', function () {
            filtro = botao.dataset.filtro;
            bloco.querySelectorAll('[data-filtro]').forEach(function (b) { b.classList.toggle('chip-ativo', b === botao); });
            aplicarFiltro();
        });
    });
})();
