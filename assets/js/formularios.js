/**
 * Comportamento dos formulários de registro:
 *  - uuid_cliente gerado no cliente (idempotência da sincronização — regra 8.3);
 *  - justificativa aparece somente quando status = "não feito".
 */
'use strict';

(function () {
    // UUID v4 para idempotência (crypto.randomUUID tem fallback simples)
    document.querySelectorAll('[data-uuid-cliente]').forEach(function (campo) {
        if (campo.value) return;
        if (window.crypto && crypto.randomUUID) {
            campo.value = crypto.randomUUID();
        } else {
            campo.value = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
                const r = Math.random() * 16 | 0;
                return (c === 'x' ? r : (r & 0x3 | 0x8)).toString(16);
            });
        }
    });

    document.querySelectorAll('[data-form-registro]').forEach(function (form) {
        const blocoJustificativa = form.querySelector('[data-so-nao-feito]');
        if (!blocoJustificativa) return;
        const atualizar = function () {
            const status = form.querySelector('input[name="status"]:checked');
            blocoJustificativa.hidden = !(status && status.value === 'nao_feito');
        };
        form.querySelectorAll('input[name="status"]').forEach(function (radio) {
            radio.addEventListener('change', atualizar);
        });
        atualizar();
    });
})();
