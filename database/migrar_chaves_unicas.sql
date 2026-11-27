-- Execute uma única vez em bancos criados antes das restrições uq_pokemons_nome
-- e uq_itens_loja_nome. Faça backup antes de alterar o banco.
USE bancodedados_pokefish;

-- Se qualquer consulta retornar linhas, pare a migração e resolva as duplicatas
-- antes de criar os índices únicos.
SELECT LOWER(nome) AS nome_normalizado, COUNT(*) AS quantidade
FROM pokemons
GROUP BY LOWER(nome)
HAVING COUNT(*) > 1;

SELECT LOWER(nome) AS nome_normalizado, COUNT(*) AS quantidade
FROM itens_loja
GROUP BY LOWER(nome)
HAVING COUNT(*) > 1;

ALTER TABLE pokemons
    ADD UNIQUE KEY uq_pokemons_nome (nome);

ALTER TABLE itens_loja
    ADD UNIQUE KEY uq_itens_loja_nome (nome);
