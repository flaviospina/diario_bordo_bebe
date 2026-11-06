-- ============================================================
-- Diário do Bebê — Novidade da Rodada 7 (v0.12.0)
--
-- COMO USAR (phpMyAdmin, sem terminal):
--   cPanel → phpMyAdmin → banco do Diário do Bebê → aba "SQL" → cole tudo → Executar.
-- Pode rodar quantas vezes quiser (INSERT IGNORE por slug). Depois, no
-- Painel → Novidades → "Enviar e-mail" para avisar todas as famílias.
-- ============================================================

INSERT IGNORE INTO novidades (slug, titulo, resumo, detalhes, publicado, criado_por)
VALUES
(
  'portal-da-colaboradora-e-registro-direto-dos-pais',
  'Portal da Colaboradora: contracheques e documentos da babá no mesmo app, e registro direto pelos pais',
  'A babá consulta contracheques, vale-transporte, horas extras, férias/13º e documentos trabalhistas numa área separada do diário, com o mesmo login. O empregador importa os PDFs do eSocial, confere os valores, publica e acompanha o histórico de acessos — registrado automaticamente, sem botão de ciência. E os pais ganharam o botão "Registrar atividade" no Acompanhar.',
  'Portal da Colaboradora (para a babá): no Meu Dia, o cartão "Meus pagamentos" abre uma área separada do diário do bebê — contracheques (com valores resumidos), vale-transporte antecipado por mês de referência, horas extras, férias e 13º, e todos os documentos para visualizar e baixar em PDF. Há sempre o botão "Voltar ao Diário do Bebê". Nada precisa ser confirmado ou aceito.

Gestão da Colaboradora (para o empregador): em Configurações → Gestão da Colaboradora, vincule o usuário da babá, importe os PDFs obtidos no eSocial (validados e guardados em área privada), confira os valores (alguns são lidos do PDF como sugestão), publique — a babá recebe aviso no app, no celular e por e-mail —, substitua documentos incorretos com versionamento, revogue quando necessário e consulte o histórico de acessos (primeiro e último acesso, aberturas e downloads por versão). O eSocial continua sendo a fonte oficial: o app não calcula tributos nem acessa o Gov.br.

Registro direto pelos pais: o Acompanhar ganhou o botão "Registrar atividade" e as ações rápidas, iguais aos da cuidadora, além do "+" que já existia em todas as telas.',
  1,
  (SELECT id FROM usuarios ORDER BY (papel = 'super_admin') DESC, id LIMIT 1)
);

SELECT slug, titulo, publicado, criado_em FROM novidades ORDER BY id;
