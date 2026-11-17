-- Execute uma única vez em bancos criados antes do suporte à foto de perfil.
USE bancodedados_pokefish;

ALTER TABLE usuarios
    ADD COLUMN foto MEDIUMBLOB NULL,
    ADD COLUMN tipo_foto VARCHAR(20) NULL;
