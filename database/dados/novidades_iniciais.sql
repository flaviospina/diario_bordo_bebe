-- ============================================================
-- Diário do Bebê — Novidades iniciais (tudo que já foi implementado)
--
-- COMO USAR (phpMyAdmin, sem terminal):
--   1. cPanel → phpMyAdmin → clique no banco do Diário do Bebê (à esquerda)
--   2. Aba "SQL" → cole TODO este conteúdo → botão "Executar" (Go)
--   3. Saia do app e entre de novo: o aviso aparece com as 4 novidades
--
-- Pode rodar quantas vezes quiser: o INSERT IGNORE usa o slug único e
-- nunca duplica. O autor é atribuído ao super_admin (ou primeiro admin).
-- O e-mail de cada novidade continua sendo disparado pelo Painel, quando
-- você quiser (botão "Enviar e-mail").
-- ============================================================

INSERT IGNORE INTO novidades (slug, titulo, resumo, detalhes, publicado, criado_por)
VALUES
(
  'ficha-essencial-do-bebe',
  'Ficha essencial do bebê, com curvas de crescimento',
  'Cada criança agora tem uma ficha completa: foto, últimas medidas com percentil da OMS, alergias sempre à vista, caderneta de vacinas e histórico de consultas.',
  'Em Acompanhar, toque em "Dados de (nome do bebê)" para abrir a ficha essencial.

Ela reúne, num lugar só: a foto e a idade, as últimas medidas de peso, altura e perímetro cefálico com o percentil da OMS (a posição na curva de referência — quem interpreta é sempre o pediatra), a faixa de saúde com alergias, restrições e medicações, as curvas de crescimento, a caderneta de vacinas cruzada com o calendário do PNI e o histórico de consultas.

Para registrar uma medição feita em casa, use o botão "Registrar medição" — cada pesagem vira um ponto na curva, nada é sobrescrito.

Os dados de nascimento (semanas, parto, medidas ao nascer, convênio) são preenchidos em Ajustes → Crianças.',
  1,
  (SELECT id FROM usuarios ORDER BY (papel = 'super_admin') DESC, id LIMIT 1)
),
(
  'qr-code-para-o-pediatra',
  'QR code para a consulta do pediatra',
  'Na consulta, um QR code de uso único mostra a ficha ao pediatra — e ele devolve peso, altura e vacinas direto para o app, para você confirmar.',
  'Na ficha do bebê, toque em "Ficha para consulta" e gere o link. Ele vale por 48 horas e abre uma única vez, por segurança.

No consultório, toque em "Mostrar QR" e peça para o pediatra apontar a câmera. Ele vê as curvas de crescimento, vacinas, alergias e o resumo dos últimos 30 dias de rotina — sem fotos e sem o dia a dia detalhado da família.

Ao final, o próprio pediatra registra as medidas e vacinas da consulta, e tudo chega para você CONFIRMAR no app — só depois da sua confirmação os dados entram na curva de crescimento.',
  1,
  (SELECT id FROM usuarios ORDER BY (papel = 'super_admin') DESC, id LIMIT 1)
),
(
  'registre-varias-atividades-de-uma-vez',
  'Registre várias atividades de uma vez',
  'Agora dá para marcar mamadeira, fralda e soneca juntas, num só registro de horário.',
  'Na janela de registro do Meu Dia (botão +), ative a chavinha "Selecionar várias" e toque em tudo o que aconteceu naquele horário — cada atividade selecionada fica marcada em verde.

Depois, toque em "Registrar N atividades juntas": uma tela única mostra os campos de cada atividade. Preencha e salve tudo de uma vez — na linha do tempo, elas aparecem agrupadas no mesmo horário.

Para anexar foto ou marcar horário de fim, registre a atividade sozinha, como antes.',
  1,
  (SELECT id FROM usuarios ORDER BY (papel = 'super_admin') DESC, id LIMIT 1)
),
(
  'guia-de-uso-e-avisos-de-novidades',
  'Guia de uso sempre à mão — e avisos como este',
  'O tutorial completo do app agora está a um toque (o livrinho no topo da tela), e cada novidade nova chega num aviso como este, no seu próximo login.',
  'O botão redondo com o livrinho, no topo de qualquer tela, abre o guia de uso completo — com o passo a passo da babá, dos pais e dos avós, tela por tela. O mesmo guia fica no rodapé, em "Como usar".

E sempre que o app ganhar uma função nova, você fica sabendo assim: um aviso no primeiro login, com o resumo e o link dos detalhes. Confirmou com "Entendi", ele não aparece de novo.

Todas as novidades ficam guardadas na página "Novidades" (rodapé), para consultar quando quiser.',
  1,
  (SELECT id FROM usuarios ORDER BY (papel = 'super_admin') DESC, id LIMIT 1)
);

-- Confira o resultado (opcional): deve listar as 4 novidades
SELECT slug, titulo, publicado, criado_em FROM novidades ORDER BY id;
