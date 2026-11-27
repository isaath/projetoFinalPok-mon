-- Na apresentação: migração pontual para bancos antigos; o schema.sql já cria
-- esta coluna em instalações novas. Faça backup e não execute se ela já existir.
USE bancodedados_pokefish;

-- Adiciona o caminho sem apagar as colunas antigas que podem conter fotos BLOB.
ALTER TABLE usuarios
    ADD COLUMN foto_caminho VARCHAR(255) NULL AFTER senha;
