-- ============================================================
-- Diário do Bebê — Novidade da Rodada 5 (v0.10.0)
--
-- COMO USAR (phpMyAdmin, sem terminal):
--   cPanel → phpMyAdmin → banco do Diário do Bebê → aba "SQL" → cole tudo → Executar.
-- Pode rodar quantas vezes quiser (INSERT IGNORE por slug). Depois, no
-- Painel → Novidades → "Enviar e-mail" para avisar todas as famílias.
-- ============================================================

INSERT IGNORE INTO novidades (slug, titulo, resumo, detalhes, publicado, criado_por)
VALUES
(
  'introducao-alimentar-fotos-e-emails',
  'Introdução alimentar por idade, foto em qualquer registro e e-mails dos momentos que importam',
  'A partir dos 6 meses, registre o que o bebê comeu numa lista filtrada pela idade (frutas, legumes, carnes, ovo…), com guia completo das fases. Toda atividade aceita foto. E os pais recebem e-mail na hora de pedidos de compra, febre, medicação, sintomas e reações a alimentos.',
  'Introdução alimentar por idade: na janela de registro, em Alimentação → Introdução alimentar, escolha a refeição e marque o que o bebê comeu. A lista mostra só o que é indicado para a idade dele — frutas, legumes e verduras, cereais e tubérculos, leguminosas, carnes/ovo/peixe, laticínios e pastas de oleaginosas — e separa o que ainda não é indicado, com a idade certa. Alergênicos (⚠️) e alimentos com risco de engasgo (✂️) vêm sinalizados. Se houver qualquer reação, os responsáveis recebem um e-mail imediatamente.

Guia completo: a página "Introdução alimentar por idade" (link no tutorial e no formulário) traz as fases segundo o Ministério da Saúde, a Sociedade Brasileira de Pediatria e a OMS, mais de 100 alimentos com observações de preparo e a lista do que evitar — e a ficha do bebê (inclusive a do pediatra) passa a mostrar tudo o que ele já experimentou.

Foto em qualquer registro: o campo "Foto" agora está também no registro de várias atividades juntas e ao editar um registro.

Pedidos de compra que chegam: quando a babá pede um item (em Itens ou em "Pedido de suprimento"), ele entra na lista e os responsáveis recebem e-mail na hora.

E-mail dos momentos que importam: além de marcos, intercorrências, medições e vacinas, agora também sintoma, febre, medicação administrada, vômito e reação alimentar avisam os pais por e-mail assim que são registrados.',
  1,
  (SELECT id FROM usuarios ORDER BY (papel = 'super_admin') DESC, id LIMIT 1)
);

SELECT slug, titulo, publicado, criado_em FROM novidades ORDER BY id;
