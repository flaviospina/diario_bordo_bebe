/**
 * Funcionamento offline (Fase 4):
 *  - formulários de registro são interceptados quando não há rede e guardados
 *    em uma fila no IndexedDB (com uuid_cliente para idempotência — regra 8.3);
 *  - quando a conexão volta, a fila é enviada a /api/sincronizar;
 *  - edições carregam base_atualizado_em: se o registro mudou no servidor,
 *    o servidor NÃO sobrescreve — cria solicitação de revisão (regra 8.4).
 */
'use strict';

(function () {
    const NOME_BD = 'diariobebe';
    const STORE = 'fila';

    function abrirBd() {
        return new Promise(function (resolver, rejeitar) {
            const pedido = indexedDB.open(NOME_BD, 1);
            pedido.onupgradeneeded = function () {
                if (!pedido.result.objectStoreNames.contains(STORE)) {
                    pedido.result.createObjectStore(STORE, { keyPath: 'chave', autoIncrement: true });
                }
            };
            pedido.onsuccess = function () { resolver(pedido.result); };
            pedido.onerror = function () { rejeitar(pedido.error); };
        });
    }

    function transacao(modo, executa) {
        return abrirBd().then(function (bd) {
            return new Promise(function (resolver, rejeitar) {
                const tx = bd.transaction(STORE, modo);
                const resultado = executa(tx.objectStore(STORE));
                tx.oncomplete = function () { resolver(resultado && resultado.result); };
                tx.onerror = function () { rejeitar(tx.error); };
            });
        });
    }

    function listarFila() {
        return transacao('readonly', function (store) { return store.getAll(); });
    }

    function guardarNaFila(item) {
        return transacao('readwrite', function (store) { store.add(item); });
    }

    function removerDaFila(chave) {
        return transacao('readwrite', function (store) { store.delete(chave); });
    }

    function atualizarContador() {
        return listarFila().then(function (itens) {
            const total = (itens || []).length;
            window.__filaPendentes = total;
            if (window.atualizarIndicadorConexao) {
                window.atualizarIndicadorConexao(total);
            }
            return total;
        }).catch(function () { return 0; });
    }

    // ── Captura dos formulários quando offline ────────────────

    function formularioParaItem(form) {
        const dadosForm = new FormData(form);
        const dados = {};
        dadosForm.forEach(function (valor, nome) {
            if (nome.indexOf('c_') === 0 && typeof valor === 'string' && valor !== '') {
                dados[nome.substring(2)] = valor;
            }
        });
        const inicioData = dadosForm.get('inicio_data');
        const inicioHora = dadosForm.get('inicio_hora');
        const fimHora = dadosForm.get('fim_hora');
        const item = {
            dados: dados,
            inicio: inicioData && inicioHora ? inicioData + ' ' + inicioHora + ':00' : null,
            fim: fimHora ? (dadosForm.get('fim_data') || inicioData) + ' ' + fimHora + ':00' : null,
            status: dadosForm.get('status') || 'feito',
            observacao: dadosForm.get('observacao') || '',
            justificativa: dadosForm.get('justificativa') || ''
        };
        if (form.dataset.categoria) {
            item.tipo = 'criacao';
            item.categoria = form.dataset.categoria;
            item.crianca = dadosForm.get('crianca');
            item.bloco = dadosForm.get('bloco') || 0;
            item.uuid_cliente = dadosForm.get('uuid_cliente');
        } else if (form.dataset.codigo) {
            item.tipo = 'edicao';
            item.codigo = form.dataset.codigo;
            item.base_atualizado_em = form.dataset.atualizadoEm || '';
        } else {
            return null;
        }
        return item;
    }

    document.querySelectorAll('[data-form-registro]').forEach(function (form) {
        form.addEventListener('submit', function (evento) {
            if (navigator.onLine) {
                return; // com rede, o fluxo normal (POST + redirect) segue
            }
            const item = formularioParaItem(form);
            if (!item) {
                return; // formulário sem metadados de fila (ex.: solicitação): exige rede
            }
            evento.preventDefault();
            guardarNaFila(item).then(atualizarContador).then(function (total) {
                alert('Sem conexão: registro guardado no aparelho (' + total + ' na fila). ' +
                    'Ele será enviado automaticamente quando a internet voltar.');
                window.location.href = urlJs('cuidador.dia');
            }).catch(function () {
                alert('Não foi possível guardar offline neste navegador. Tente quando a conexão voltar.');
            });
        });
    });

    // ── Sincronização ─────────────────────────────────────────

    let sincronizando = false;

    function sincronizar() {
        if (sincronizando || !navigator.onLine) {
            return;
        }
        listarFila().then(function (itens) {
            if (!itens || itens.length === 0) {
                return;
            }
            sincronizando = true;
            fetch(urlJs('api.sincronizar'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF': (window.APP && window.APP.csrf) || ''
                },
                credentials: 'same-origin',
                body: JSON.stringify({ itens: itens })
            }).then(function (resposta) {
                if (!resposta.ok) { throw new Error('HTTP ' + resposta.status); }
                return resposta.json();
            }).then(function (corpo) {
                const resultados = (corpo && corpo.resultados) || [];
                const avisos = [];
                const remocoes = itens.map(function (item, indice) {
                    const resultado = resultados[indice];
                    if (!resultado) { return Promise.resolve(); }
                    if (resultado.resultado === 'conflito' || resultado.resultado === 'solicitacao') {
                        avisos.push(resultado.mensagem);
                    }
                    if (resultado.resultado === 'erro') {
                        console.warn('Item da fila rejeitado:', resultado.mensagem, item);
                    }
                    // Tudo que o servidor respondeu sai da fila (idempotência protege reenvio)
                    return removerDaFila(item.chave);
                });
                return Promise.all(remocoes).then(function () {
                    if (avisos.length > 0) {
                        alert(avisos.join('\n'));
                    }
                });
            }).catch(function (erro) {
                console.warn('Sincronização adiada:', erro);
            }).finally(function () {
                sincronizando = false;
                atualizarContador();
            });
        });
    }

    window.addEventListener('online', sincronizar);
    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) { sincronizar(); }
    });
    atualizarContador().then(sincronizar);
})();
