-- Esquema inicial do banco de dados do Pokebagual.
-- No DBeaver, abra este arquivo e execute o script com o servidor MySQL iniciado.

CREATE DATABASE IF NOT EXISTS bancodedados_pokefish
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE bancodedados_pokefish;

-- Contas dos jogadores. O e-mail identifica cada conta e a senha deve ser armazenada como hash.
CREATE TABLE IF NOT EXISTS usuarios (
    id_usuario INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nome VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    senha VARCHAR(255) NOT NULL,
    dinheiro INT UNSIGNED NOT NULL DEFAULT 0,
    data_cadastro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_usuario),
    UNIQUE KEY uq_usuarios_email (email)
) ENGINE=InnoDB;

-- Tipos elementais disponíveis no jogo.
CREATE TABLE IF NOT EXISTS tipos (
    id_tipo INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nome VARCHAR(40) NOT NULL,
    PRIMARY KEY (id_tipo),
    UNIQUE KEY uq_tipos_nome (nome)
) ENGINE=InnoDB;

-- Pokémon cadastrados para o jogo e seus atributos de combate.
-- id_tipo pode ficar vazio enquanto o cadastro ainda não estiver associado a um tipo.
CREATE TABLE IF NOT EXISTS pokemons (
    id_pokemon INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nivel INT UNSIGNED NOT NULL DEFAULT 1,
    nome VARCHAR(100) NOT NULL,
    hp INT UNSIGNED NOT NULL DEFAULT 0,
    hp_maximo INT UNSIGNED NOT NULL DEFAULT 0,
    forca_ataque INT UNSIGNED NOT NULL DEFAULT 0,
    defesa INT UNSIGNED NOT NULL DEFAULT 0,
    ataque_especial INT UNSIGNED NOT NULL DEFAULT 0,
    defesa_especial INT UNSIGNED NOT NULL DEFAULT 0,
    velocidade INT UNSIGNED NOT NULL DEFAULT 0,
    prioridade INT NOT NULL DEFAULT 0,
    experiencia INT UNSIGNED NOT NULL DEFAULT 0,
    id_tipo INT UNSIGNED NULL,
    PRIMARY KEY (id_pokemon),
    KEY idx_pokemons_tipo (id_tipo),
    CONSTRAINT fk_pokemons_tipo
        FOREIGN KEY (id_tipo) REFERENCES tipos (id_tipo)
        ON UPDATE CASCADE
        ON DELETE SET NULL
) ENGINE=InnoDB;

-- Times criados pelos usuários.
CREATE TABLE IF NOT EXISTS times (
    id_time INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nome VARCHAR(100) NOT NULL,
    id_usuario INT UNSIGNED NOT NULL,
    PRIMARY KEY (id_time),
    KEY idx_times_usuario (id_usuario),
    CONSTRAINT fk_times_usuario
        FOREIGN KEY (id_usuario) REFERENCES usuarios (id_usuario)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- Associação entre times e Pokémon, incluindo a posição de cada Pokémon no time.
CREATE TABLE IF NOT EXISTS time_pokemons (
    id_time_pokemon INT UNSIGNED NOT NULL AUTO_INCREMENT,
    posicao TINYINT UNSIGNED NOT NULL,
    id_time INT UNSIGNED NOT NULL,
    id_pokemon INT UNSIGNED NOT NULL,
    PRIMARY KEY (id_time_pokemon),
    UNIQUE KEY uq_time_posicao (id_time, posicao),
    UNIQUE KEY uq_time_pokemon (id_time, id_pokemon),
    KEY idx_time_pokemons_pokemon (id_pokemon),
    CONSTRAINT fk_time_pokemons_time
        FOREIGN KEY (id_time) REFERENCES times (id_time)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_time_pokemons_pokemon
        FOREIGN KEY (id_pokemon) REFERENCES pokemons (id_pokemon)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Itens que podem ser comprados na loja.
CREATE TABLE IF NOT EXISTS itens_loja (
    id_item INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nome VARCHAR(100) NOT NULL,
    tipo VARCHAR(40) NOT NULL,
    preco INT UNSIGNED NOT NULL DEFAULT 0,
    descricao VARCHAR(255) NULL,
    valor_efeito INT NOT NULL DEFAULT 0,
    PRIMARY KEY (id_item)
) ENGINE=InnoDB;

-- Itens atualmente pertencentes a cada usuário.
CREATE TABLE IF NOT EXISTS inventario (
    id_inventario INT UNSIGNED NOT NULL AUTO_INCREMENT,
    quantidade INT UNSIGNED NOT NULL DEFAULT 0,
    id_usuario INT UNSIGNED NOT NULL,
    id_item INT UNSIGNED NOT NULL,
    PRIMARY KEY (id_inventario),
    UNIQUE KEY uq_inventario_usuario_item (id_usuario, id_item),
    KEY idx_inventario_item (id_item),
    CONSTRAINT fk_inventario_usuario
        FOREIGN KEY (id_usuario) REFERENCES usuarios (id_usuario)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_inventario_item
        FOREIGN KEY (id_item) REFERENCES itens_loja (id_item)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Histórico de compras feitas pelos usuários.
CREATE TABLE IF NOT EXISTS compras (
    id_compra INT UNSIGNED NOT NULL AUTO_INCREMENT,
    quantidade INT UNSIGNED NOT NULL,
    valor_unitario INT UNSIGNED NOT NULL,
    data_hora DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    id_usuario INT UNSIGNED NOT NULL,
    id_item INT UNSIGNED NOT NULL,
    PRIMARY KEY (id_compra),
    KEY idx_compras_usuario (id_usuario),
    KEY idx_compras_item (id_item),
    CONSTRAINT fk_compras_usuario
        FOREIGN KEY (id_usuario) REFERENCES usuarios (id_usuario)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT fk_compras_item
        FOREIGN KEY (id_item) REFERENCES itens_loja (id_item)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Dados gerais de cada batalha.
CREATE TABLE IF NOT EXISTS batalhas (
    id_batalha INT UNSIGNED NOT NULL AUTO_INCREMENT,
    data_hora DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    turnos INT UNSIGNED NOT NULL DEFAULT 0,
    dinheiro_ganho INT UNSIGNED NOT NULL DEFAULT 0,
    resultado VARCHAR(40) NOT NULL,
    PRIMARY KEY (id_batalha)
) ENGINE=InnoDB;

-- Pokémon que participaram de uma batalha e o resultado individual de cada um.
-- Cada linha pertence a um usuário, uma batalha e um Pokémon.
CREATE TABLE IF NOT EXISTS batalha_pokemons (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    hp_final INT UNSIGNED NOT NULL DEFAULT 0,
    desmaiou BOOLEAN NOT NULL DEFAULT FALSE,
    id_usuario INT UNSIGNED NOT NULL,
    id_batalha INT UNSIGNED NOT NULL,
    id_pokemon INT UNSIGNED NOT NULL,
    PRIMARY KEY (id),
    KEY idx_batalha_pokemons_usuario (id_usuario),
    KEY idx_batalha_pokemons_batalha (id_batalha),
    KEY idx_batalha_pokemons_pokemon (id_pokemon),
    CONSTRAINT fk_batalha_pokemons_usuario
        FOREIGN KEY (id_usuario) REFERENCES usuarios (id_usuario)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT fk_batalha_pokemons_batalha
        FOREIGN KEY (id_batalha) REFERENCES batalhas (id_batalha)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_batalha_pokemons_pokemon
        FOREIGN KEY (id_pokemon) REFERENCES pokemons (id_pokemon)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Cria os três tipos básicos sem duplicá-los caso o script seja executado novamente.
INSERT IGNORE INTO tipos (nome)
VALUES ('Agua'), ('Fogo'), ('Planta');
