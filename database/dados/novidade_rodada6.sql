-- ============================================================
-- Diário do Bebê — Novidade da Rodada 6 (v0.11.0)
--
-- COMO USAR (phpMyAdmin, sem terminal):
--   cPanel → phpMyAdmin → banco do Diário do Bebê → aba "SQL" → cole tudo → Executar.
-- Pode rodar quantas vezes quiser (INSERT IGNORE por slug). Depois, no
-- Painel → Novidades → "Enviar e-mail" para avisar todas as famílias.
-- ============================================================

INSERT IGNORE INTO novidades (slug, titulo, resumo, detalhes, publicado, criado_por)
VALUES
(
  'cardapio-semanal-compras-e-almoco-com-foto',
  'Cardápio da semana montado pelos pais, lista de compras no celular e almoço registrado com foto',
  'Os pais montam o cardápio de cada dia (fruta da manhã, almoço, fruta da tarde, jantar) e a cuidadora é avisada na hora. A lista de compras da semana fica no app, por categoria, para marcar "no carrinho" direto no mercado. E o almoço e o jantar passam a ser registrados com foto do prato.',
  'Cardápio da semana: no menu Compras, os responsáveis carregam a semana-modelo de introdução alimentar (28 itens em 6 categorias e o cardápio dos 7 dias) e escrevem, para cada dia, o que a cuidadora deve preparar — pode ser diferente do modelo. Ao salvar, a cuidadora recebe aviso no app, notificação no celular e e-mail.

Cardápio de hoje no Meu Dia: a cuidadora vê o que preparar em cada refeição e toca em "Registrar almoço": a refeição e os alimentos do cardápio já vêm marcados, bastando ajustar o que o bebê realmente comeu.

Almoço e jantar com foto obrigatória: para salvar o registro do almoço ou do jantar da introdução alimentar é preciso tirar a foto do prato. Os pais veem a foto no registro.

Lista de compras no celular: itens por categoria com quantidade e nota, marcação "no carrinho" salva na hora para toda a família, barra de progresso, filtros Pendentes / No carrinho, impressão, "Desmarcar tudo" para reaproveitar a lista e inclusão/remoção de itens. Novas semanas podem ser criadas a partir do modelo, sem duplicar, e as marcações feitas no HTML antigo da lista podem ser importadas com segurança (o app lê só o estado salvo, sem executar nada do arquivo).

Comprar não é consumir: marcar um item no carrinho não registra refeição nenhuma — o que o bebê comeu continua sendo registrado no diário.',
  1,
  (SELECT id FROM usuarios ORDER BY (papel = 'super_admin') DESC, id LIMIT 1)
);

SELECT slug, titulo, publicado, criado_em FROM novidades ORDER BY id;
