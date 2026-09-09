-- ============================================================
-- Diário do Bebê — Novidade da Rodada 2 (v0.7.0)
--
-- COMO USAR (phpMyAdmin, sem terminal):
--   1. cPanel → phpMyAdmin → clique no banco do Diário do Bebê (à esquerda)
--   2. Aba "SQL" → cole TODO este conteúdo → botão "Executar" (Go)
--   3. Saia do app e entre de novo: o aviso aparece para cada pessoa
--      no primeiro login dela
--
-- Pode rodar quantas vezes quiser: o INSERT IGNORE usa o slug único e
-- nunca duplica. O e-mail da novidade é disparado pelo Painel, quando
-- você quiser (botão "Enviar e-mail").
-- ============================================================

INSERT IGNORE INTO novidades (slug, titulo, resumo, detalhes, publicado, criado_por)
VALUES
(
  'acompanhamento-com-ia-e-eventos-importantes',
  'Acompanhamento com IA e avisos dos momentos importantes',
  'Uma inteligência artificial agora lê os últimos 14 dias do diário e devolve observações sobre padrões de mamadas, sono e cólicas. E cada marco, vacina, medição ou intercorrência chega na hora no seu e-mail — e na ficha do pediatra.',
  'Em Relatórios → Acompanhamento, toque em "Gerar a análise de agora": a IA analisa os últimos 14 dias do diário — mamadas, sono, cólicas, fraldas, medicações e marcos — e devolve de 2 a 4 observações, separadas em padrões da rotina, pontos que valem conversar com o pediatra, contexto da fase do bebê e conquistas para comemorar. Uma análise nova também sai sozinha a cada semana, com aviso por e-mail.

Privacidade em primeiro lugar: a análise recebe apenas a idade e o sexo do bebê — nunca o nome, fotos ou os textos da família. E as observações NUNCA são diagnóstico: elas apontam tendências, e quem avalia é sempre o pediatra.

Momentos importantes agora avisam na hora: quando um marco de desenvolvimento, uma vacina, uma medição confirmada ou uma intercorrência entra no diário, os responsáveis recebem um e-mail no mesmo instante.

E o pediatra fica por dentro sem esforço: a ficha da consulta (aquela do QR code) ganhou a seção "Novidades desde a última consulta", com tudo o que aconteceu de importante desde a visita anterior.',
  1,
  (SELECT id FROM usuarios ORDER BY (papel = 'super_admin') DESC, id LIMIT 1)
);

-- Confira o resultado (opcional): deve listar a novidade nova
SELECT slug, titulo, publicado, criado_em FROM novidades ORDER BY id;
