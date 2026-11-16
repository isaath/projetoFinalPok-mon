-- Execute uma única vez para adicionar fotos à tabela de usuários já existente.
USE bancodedados_pokefish;

ALTER TABLE usuarios
    ADD COLUMN foto MEDIUMBLOB NULL,
    ADD COLUMN tipo_foto VARCHAR(20) NULL;
