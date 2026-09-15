-- ============================================================
-- Diário do Bebê — Novidade da Rodada 3 (v0.8.0)
--
-- COMO USAR (phpMyAdmin, sem terminal):
--   1. cPanel → phpMyAdmin → clique no banco do Diário do Bebê (à esquerda)
--   2. Aba "SQL" → cole TODO este conteúdo → botão "Executar" (Go)
--
-- Pode rodar quantas vezes quiser: o INSERT IGNORE usa o slug único e
-- nunca duplica. O e-mail da novidade é disparado pelo Painel, quando
-- você quiser (botão "Enviar e-mail").
-- ============================================================

INSERT IGNORE INTO novidades (slug, titulo, resumo, detalhes, publicado, criado_por)
VALUES
(
  'lembretes-inteligentes-por-email',
  'Lembretes inteligentes por e-mail',
  'O app agora acompanha vocês por e-mail: resumo do mês com as conquistas do bebê, parabéns a cada mêsversário com lembrete da pesagem, e aviso 2 dias antes da consulta para levar a ficha do QR code pronta.',
  'O Diário do Bebê ganhou uma rotina de e-mails pensada para ajudar sem incomodar:

Resumo do mês: no início de cada mês, os responsáveis recebem um retrato do mês anterior — quantas atividades foram registradas, horas de sono acompanhadas, marcos celebrados e medições na curva de crescimento.

Mêsversário: no dia em que o bebê completa cada mês, chega um parabéns com o lembrete da pesagem mensal — um ponto por mês desenha a curva de crescimento que o pediatra acompanha.

Consulta chegando: quando o pediatra marca retorno, 2 dias antes chega um lembrete para gerar a ficha da consulta (a do QR code), que já vai com curvas, vacinas e as novidades desde a última consulta.

E, se o diário ficar parado por um tempo, mandamos no máximo um toque gentil por semana — com um botão de WhatsApp para falar direto com a gente.

Transparência sempre: esses avisos usam apenas datas e contagens. Ninguém da plataforma lê o conteúdo do diário — ele é só da família.',
  1,
  (SELECT id FROM usuarios ORDER BY (papel = 'super_admin') DESC, id LIMIT 1)
);

-- Confira o resultado (opcional): deve listar a novidade nova
SELECT slug, titulo, publicado, criado_em FROM novidades ORDER BY id;
