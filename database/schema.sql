-- Esquema inicial do banco de dados do PokeBagual.
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
    foto MEDIUMBLOB NULL,
    tipo_foto VARCHAR(20) NULL,
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

-- Mantém todos os tipos de cada espécie. id_tipo em pokemons continua como tipo principal
-- para compatibilidade com as telas e consultas já existentes.
CREATE TABLE IF NOT EXISTS pokemon_tipos (
    id_pokemon INT UNSIGNED NOT NULL,
    id_tipo INT UNSIGNED NOT NULL,
    ordem TINYINT UNSIGNED NOT NULL,
    PRIMARY KEY (id_pokemon, id_tipo),
    UNIQUE KEY uq_pokemon_tipos_ordem (id_pokemon, ordem),
    CONSTRAINT fk_pokemon_tipos_pokemon
        FOREIGN KEY (id_pokemon) REFERENCES pokemons (id_pokemon)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_pokemon_tipos_tipo
        FOREIGN KEY (id_tipo) REFERENCES tipos (id_tipo)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Catálogo de movimentos usados no combate. A categoria física/especial segue
-- a divisão por tipo usada em FireRed/LeafGreen (Geração III).
CREATE TABLE IF NOT EXISTS movimentos (
    id_movimento INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nome VARCHAR(60) NOT NULL,
    id_tipo INT UNSIGNED NOT NULL,
    categoria VARCHAR(20) NOT NULL,
    potencia SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    precisao TINYINT UNSIGNED NOT NULL DEFAULT 100,
    prioridade TINYINT NOT NULL DEFAULT 0,
    efeito VARCHAR(30) NOT NULL DEFAULT 'nenhum',
    chance_efeito TINYINT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (id_movimento),
    UNIQUE KEY uq_movimentos_nome (nome),
    KEY idx_movimentos_tipo (id_tipo),
    CONSTRAINT fk_movimentos_tipo
        FOREIGN KEY (id_tipo) REFERENCES tipos (id_tipo)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Cada espécie tem até quatro movimentos, apresentados na ordem da lista.
CREATE TABLE IF NOT EXISTS pokemon_movimentos (
    id_pokemon INT UNSIGNED NOT NULL,
    ordem TINYINT UNSIGNED NOT NULL,
    id_movimento INT UNSIGNED NOT NULL,
    PRIMARY KEY (id_pokemon, ordem),
    UNIQUE KEY uq_pokemon_movimento (id_pokemon, id_movimento),
    CONSTRAINT fk_pokemon_movimentos_pokemon
        FOREIGN KEY (id_pokemon) REFERENCES pokemons (id_pokemon)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_pokemon_movimentos_movimento
        FOREIGN KEY (id_movimento) REFERENCES movimentos (id_movimento)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
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

-- Pokémon pertencentes ao usuário: posições 1 a 3 são ativas; a partir de 4 ficam na reserva.
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

-- Guarda o HP por Pokémon associado a um time, sem alterar o cadastro global do Pokémon.
CREATE TABLE IF NOT EXISTS time_pokemons_estado (
    id_time_pokemon INT UNSIGNED NOT NULL,
    hp_atual INT UNSIGNED NOT NULL,
    PRIMARY KEY (id_time_pokemon),
    CONSTRAINT fk_time_pokemons_estado_time_pokemon
        FOREIGN KEY (id_time_pokemon) REFERENCES time_pokemons (id_time_pokemon)
        ON UPDATE CASCADE
        ON DELETE CASCADE
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

-- Preços aprovados para os itens obrigatórios. A consulta evita duplicá-los ao reexecutar o script.
INSERT INTO itens_loja (nome, tipo, preco, descricao, valor_efeito)
SELECT 'Potion', 'cura_hp', 25, 'Recupera 20 HP.', 20
WHERE NOT EXISTS (SELECT 1 FROM itens_loja WHERE nome = 'Potion');

INSERT INTO itens_loja (nome, tipo, preco, descricao, valor_efeito)
SELECT 'Super Potion', 'cura_hp', 50, 'Recupera 50 HP.', 50
WHERE NOT EXISTS (SELECT 1 FROM itens_loja WHERE nome = 'Super Potion');

INSERT INTO itens_loja (nome, tipo, preco, descricao, valor_efeito)
SELECT 'Hyper Potion', 'cura_hp', 100, 'Recupera 200 HP.', 200
WHERE NOT EXISTS (SELECT 1 FROM itens_loja WHERE nome = 'Hyper Potion');

INSERT INTO itens_loja (nome, tipo, preco, descricao, valor_efeito)
SELECT 'Max Potion', 'cura_hp', 180, 'Recupera 100% do HP.', 100
WHERE NOT EXISTS (SELECT 1 FROM itens_loja WHERE nome = 'Max Potion');

INSERT INTO itens_loja (nome, tipo, preco, descricao, valor_efeito)
SELECT 'Full Restore', 'cura_hp_status', 220, 'Recupera 100% do HP e cura todos os status.', 100
WHERE NOT EXISTS (SELECT 1 FROM itens_loja WHERE nome = 'Full Restore');

INSERT INTO itens_loja (nome, tipo, preco, descricao, valor_efeito)
SELECT 'Antidote', 'cura_status', 15, 'Cura envenenamento.', 0
WHERE NOT EXISTS (SELECT 1 FROM itens_loja WHERE nome = 'Antidote');

INSERT INTO itens_loja (nome, tipo, preco, descricao, valor_efeito)
SELECT 'Burn Heal', 'cura_status', 15, 'Cura queimadura.', 0
WHERE NOT EXISTS (SELECT 1 FROM itens_loja WHERE nome = 'Burn Heal');

INSERT INTO itens_loja (nome, tipo, preco, descricao, valor_efeito)
SELECT 'Ice Heal', 'cura_status', 15, 'Cura congelamento.', 0
WHERE NOT EXISTS (SELECT 1 FROM itens_loja WHERE nome = 'Ice Heal');

INSERT INTO itens_loja (nome, tipo, preco, descricao, valor_efeito)
SELECT 'Awakening', 'cura_status', 15, 'Cura sono.', 0
WHERE NOT EXISTS (SELECT 1 FROM itens_loja WHERE nome = 'Awakening');

INSERT INTO itens_loja (nome, tipo, preco, descricao, valor_efeito)
SELECT 'Paralyze Heal', 'cura_status', 15, 'Cura paralisia.', 0
WHERE NOT EXISTS (SELECT 1 FROM itens_loja WHERE nome = 'Paralyze Heal');

INSERT INTO itens_loja (nome, tipo, preco, descricao, valor_efeito)
SELECT 'Full Heal', 'cura_status', 30, 'Cura todas as condições de status.', 0
WHERE NOT EXISTS (SELECT 1 FROM itens_loja WHERE nome = 'Full Heal');

INSERT INTO itens_loja (nome, tipo, preco, descricao, valor_efeito)
SELECT 'Revive', 'revive', 70, 'Revive um Pokémon desmaiado com metade da vida.', 50
WHERE NOT EXISTS (SELECT 1 FROM itens_loja WHERE nome = 'Revive');

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

-- Cria os tipos usados pelo time inicial e pelos ginásios da jornada sem duplicá-los.
INSERT IGNORE INTO tipos (nome)
VALUES
    ('Agua'), ('Fogo'), ('Planta'), ('Eletrico'), ('Pedra'), ('Terra'),
    ('Veneno'), ('Psiquico'), ('Voador'), ('Inseto'), ('Normal');

-- Tipos adicionais necessários para representar o gráfico de FireRed/LeafGreen.
INSERT IGNORE INTO tipos (nome)
VALUES ('Lutador'), ('Gelo'), ('Fantasma'), ('Dragao'), ('Aco'), ('Sombrio');

-- Movimentos e efeitos simplificados para o combate acadêmico.
INSERT INTO movimentos
    (nome, id_tipo, categoria, potencia, precisao, prioridade, efeito, chance_efeito)
SELECT catalogo.nome, tipo.id_tipo, catalogo.categoria, catalogo.potencia,
       catalogo.precisao, catalogo.prioridade, catalogo.efeito, catalogo.chance_efeito
FROM (
    SELECT 'Tackle' AS nome, 'Normal' AS tipo, 'fisico' AS categoria, 35 AS potencia, 95 AS precisao, 0 AS prioridade, 'nenhum' AS efeito, 0 AS chance_efeito
    UNION ALL SELECT 'Vine Whip', 'Planta', 'especial', 35, 100, 0, 'nenhum', 0
    UNION ALL SELECT 'Wrap', 'Normal', 'fisico', 15, 90, 0, 'nenhum', 0
    UNION ALL SELECT 'Scratch', 'Normal', 'fisico', 40, 100, 0, 'nenhum', 0
    UNION ALL SELECT 'Ember', 'Fogo', 'especial', 40, 100, 0, 'queimadura', 10
    UNION ALL SELECT 'Rock Throw', 'Pedra', 'fisico', 50, 90, 0, 'nenhum', 0
    UNION ALL SELECT 'Bite', 'Sombrio', 'especial', 60, 100, 0, 'flinch', 30
    UNION ALL SELECT 'Smog', 'Veneno', 'fisico', 20, 70, 0, 'veneno', 40
    UNION ALL SELECT 'Thunder Shock', 'Eletrico', 'especial', 40, 100, 0, 'paralisia', 10
    UNION ALL SELECT 'Thunder Wave', 'Eletrico', 'status', 0, 100, 0, 'paralisia', 100
    UNION ALL SELECT 'Stomp', 'Normal', 'fisico', 65, 100, 0, 'flinch', 30
    UNION ALL SELECT 'Water Pulse', 'Agua', 'especial', 60, 100, 0, 'confusao', 20
    UNION ALL SELECT 'Bubble Beam', 'Agua', 'especial', 65, 100, 0, 'nenhum', 0
    UNION ALL SELECT 'Confusion', 'Psiquico', 'especial', 50, 100, 0, 'confusao', 10
    UNION ALL SELECT 'Horn Attack', 'Normal', 'fisico', 65, 100, 0, 'nenhum', 0
    UNION ALL SELECT 'Rock Blast', 'Pedra', 'fisico', 50, 80, 0, 'nenhum', 0
    UNION ALL SELECT 'Water Gun', 'Agua', 'especial', 40, 100, 0, 'nenhum', 0
    UNION ALL SELECT 'Poison Sting', 'Veneno', 'fisico', 15, 100, 0, 'veneno', 30
    UNION ALL SELECT 'Spark', 'Eletrico', 'especial', 65, 100, 0, 'paralisia', 30
    UNION ALL SELECT 'Screech', 'Normal', 'status', 0, 85, 0, 'defesa_down', 100
    UNION ALL SELECT 'Metal Claw', 'Aco', 'fisico', 50, 95, 0, 'ataque_up', 10
    UNION ALL SELECT 'Smokescreen', 'Normal', 'status', 0, 100, 0, 'precisao_down', 100
    UNION ALL SELECT 'Petal Dance', 'Planta', 'especial', 70, 100, 0, 'nenhum', 0
    UNION ALL SELECT 'Acid', 'Veneno', 'fisico', 40, 100, 0, 'defesa_down', 10
    UNION ALL SELECT 'Sleep Powder', 'Planta', 'status', 0, 75, 0, 'sono', 100
    UNION ALL SELECT 'Wing Attack', 'Voador', 'fisico', 60, 100, 0, 'nenhum', 0
    UNION ALL SELECT 'Poison Fang', 'Veneno', 'fisico', 50, 100, 0, 'veneno', 30
    UNION ALL SELECT 'Razor Leaf', 'Planta', 'especial', 55, 95, 0, 'nenhum', 0
    UNION ALL SELECT 'Psybeam', 'Psiquico', 'especial', 65, 100, 0, 'confusao', 10
    UNION ALL SELECT 'Recover', 'Normal', 'status', 0, 100, 0, 'curar', 100
    UNION ALL SELECT 'Magical Leaf', 'Planta', 'especial', 60, 100, 0, 'nenhum', 0
    UNION ALL SELECT 'Barrier', 'Psiquico', 'status', 0, 100, 0, 'defesa_up', 100
    UNION ALL SELECT 'Dig', 'Terra', 'fisico', 60, 100, 0, 'nenhum', 0
    UNION ALL SELECT 'Mega Drain', 'Planta', 'especial', 40, 100, 0, 'cura_dano', 100
    UNION ALL SELECT 'Giga Drain', 'Planta', 'especial', 60, 100, 0, 'cura_dano', 100
    UNION ALL SELECT 'Leech Life', 'Inseto', 'fisico', 20, 100, 0, 'cura_dano', 100
    UNION ALL SELECT 'Bind', 'Normal', 'fisico', 15, 75, 0, 'nenhum', 0
    UNION ALL SELECT 'Protect', 'Normal', 'status', 0, 100, 4, 'proteger', 100
    UNION ALL SELECT 'Psychic', 'Psiquico', 'especial', 90, 100, 0, 'defesa_especial_down', 10
    UNION ALL SELECT 'Flamethrower', 'Fogo', 'especial', 95, 100, 0, 'queimadura', 10
    UNION ALL SELECT 'Extreme Speed', 'Normal', 'fisico', 80, 100, 1, 'nenhum', 0
    UNION ALL SELECT 'Quick Attack', 'Normal', 'fisico', 40, 100, 1, 'nenhum', 0
    UNION ALL SELECT 'Surf', 'Agua', 'especial', 95, 100, 0, 'nenhum', 0
    UNION ALL SELECT 'Ice Beam', 'Gelo', 'especial', 95, 100, 0, 'congelamento', 10
    UNION ALL SELECT 'Slash', 'Normal', 'fisico', 70, 100, 0, 'nenhum', 0
    UNION ALL SELECT 'Rapid Spin', 'Normal', 'fisico', 20, 100, 0, 'nenhum', 0
    UNION ALL SELECT 'Thunderbolt', 'Eletrico', 'especial', 95, 100, 0, 'paralisia', 10
    UNION ALL SELECT 'Swift', 'Normal', 'fisico', 60, 100, 0, 'nenhum', 0
    UNION ALL SELECT 'Light Screen', 'Psiquico', 'status', 0, 100, 0, 'defesa_especial_up', 100
    UNION ALL SELECT 'Earthquake', 'Terra', 'fisico', 100, 100, 0, 'nenhum', 0
    UNION ALL SELECT 'Strength', 'Normal', 'fisico', 80, 100, 0, 'nenhum', 0
    UNION ALL SELECT 'Defense Curl', 'Normal', 'status', 0, 100, 0, 'defesa_up', 100
    UNION ALL SELECT 'Headbutt', 'Normal', 'fisico', 70, 100, 0, 'flinch', 30
    UNION ALL SELECT 'Hypnosis', 'Psiquico', 'status', 0, 60, 0, 'sono', 100
    UNION ALL SELECT 'Sludge Bomb', 'Veneno', 'fisico', 90, 100, 0, 'veneno', 30
    UNION ALL SELECT 'Body Slam', 'Normal', 'fisico', 85, 100, 0, 'paralisia', 30
    UNION ALL SELECT 'Acid Armor', 'Veneno', 'status', 0, 100, 0, 'defesa_up', 100
    UNION ALL SELECT 'Double Kick', 'Lutador', 'fisico', 60, 100, 0, 'nenhum', 0
    UNION ALL SELECT 'Toxic', 'Veneno', 'status', 0, 85, 0, 'toxico', 100
    UNION ALL SELECT 'Confuse Ray', 'Fantasma', 'status', 0, 100, 0, 'confusao', 100
    UNION ALL SELECT 'Fire Spin', 'Fogo', 'especial', 15, 70, 0, 'nenhum', 0
    UNION ALL SELECT 'Stun Spore', 'Planta', 'status', 0, 75, 0, 'paralisia', 100
    UNION ALL SELECT 'Swords Dance', 'Normal', 'status', 0, 100, 0, 'ataque_up', 100
    UNION ALL SELECT 'Bounce', 'Voador', 'fisico', 85, 85, 0, 'paralisia', 30
    UNION ALL SELECT 'Agility', 'Psiquico', 'status', 0, 100, 0, 'velocidade_up', 100
    UNION ALL SELECT 'Synthesis', 'Planta', 'status', 0, 100, 0, 'curar', 100
    UNION ALL SELECT 'Scary Face', 'Normal', 'status', 0, 100, 0, 'velocidade_down', 100
    UNION ALL SELECT 'Twister', 'Dragao', 'especial', 40, 100, 0, 'flinch', 20
) AS catalogo
INNER JOIN tipos AS tipo
    ON tipo.nome = catalogo.tipo
WHERE NOT EXISTS (
    SELECT 1 FROM movimentos AS existente WHERE existente.nome = catalogo.nome
);

-- Cinco opções iniciais aprovadas: são os menores totais de atributos base entre os nove nomes do PokeApiService.
INSERT INTO pokemons
    (nivel, nome, hp, hp_maximo, forca_ataque, defesa, ataque_especial, defesa_especial, velocidade, id_tipo)
SELECT 1, 'Vulpix', 38, 38, 41, 40, 50, 65, 65, tipos.id_tipo
FROM tipos
WHERE tipos.nome = 'Fogo'
  AND NOT EXISTS (SELECT 1 FROM pokemons WHERE LOWER(nome) = 'vulpix')
LIMIT 1;

INSERT INTO pokemons
    (nivel, nome, hp, hp_maximo, forca_ataque, defesa, ataque_especial, defesa_especial, velocidade, id_tipo)
SELECT 1, 'Bellsprout', 50, 50, 75, 35, 70, 30, 40, tipos.id_tipo
FROM tipos
WHERE tipos.nome = 'Planta'
  AND NOT EXISTS (SELECT 1 FROM pokemons WHERE LOWER(nome) = 'bellsprout')
LIMIT 1;

INSERT INTO pokemons
    (nivel, nome, hp, hp_maximo, forca_ataque, defesa, ataque_especial, defesa_especial, velocidade, id_tipo)
SELECT 1, 'Charmander', 39, 39, 52, 43, 60, 50, 65, tipos.id_tipo
FROM tipos
WHERE tipos.nome = 'Fogo'
  AND NOT EXISTS (SELECT 1 FROM pokemons WHERE LOWER(nome) = 'charmander')
LIMIT 1;

INSERT INTO pokemons
    (nivel, nome, hp, hp_maximo, forca_ataque, defesa, ataque_especial, defesa_especial, velocidade, id_tipo)
SELECT 1, 'Squirtle', 44, 44, 48, 65, 50, 64, 43, tipos.id_tipo
FROM tipos
WHERE tipos.nome = 'Agua'
  AND NOT EXISTS (SELECT 1 FROM pokemons WHERE LOWER(nome) = 'squirtle')
LIMIT 1;

INSERT INTO pokemons
    (nivel, nome, hp, hp_maximo, forca_ataque, defesa, ataque_especial, defesa_especial, velocidade, id_tipo)
SELECT 1, 'Bulbasaur', 45, 45, 49, 49, 65, 65, 45, tipos.id_tipo
FROM tipos
WHERE tipos.nome = 'Planta'
  AND NOT EXISTS (SELECT 1 FROM pokemons WHERE LOWER(nome) = 'bulbasaur')
LIMIT 1;

-- Evoluções dos cinco Pokémon iniciais, com atributos base consultados na PokeAPI.
-- O nível começa em 1 como os demais cadastros; a regra de evolução por itens é separada.
-- O tipo principal fica em pokemons.id_tipo; pokemon_tipos também registra os tipos secundários.
INSERT INTO pokemons
    (nivel, nome, hp, hp_maximo, forca_ataque, defesa, ataque_especial, defesa_especial, velocidade, id_tipo)
SELECT 1, 'Ivysaur', 60, 60, 62, 63, 80, 80, 60, tipos.id_tipo
FROM tipos
WHERE tipos.nome = 'Planta'
  AND NOT EXISTS (SELECT 1 FROM pokemons WHERE LOWER(nome) = 'ivysaur')
LIMIT 1;

INSERT INTO pokemons
    (nivel, nome, hp, hp_maximo, forca_ataque, defesa, ataque_especial, defesa_especial, velocidade, id_tipo)
SELECT 1, 'Venusaur', 80, 80, 82, 83, 100, 100, 80, tipos.id_tipo
FROM tipos
WHERE tipos.nome = 'Planta'
  AND NOT EXISTS (SELECT 1 FROM pokemons WHERE LOWER(nome) = 'venusaur')
LIMIT 1;

INSERT INTO pokemons
    (nivel, nome, hp, hp_maximo, forca_ataque, defesa, ataque_especial, defesa_especial, velocidade, id_tipo)
SELECT 1, 'Charmeleon', 58, 58, 64, 58, 80, 65, 80, tipos.id_tipo
FROM tipos
WHERE tipos.nome = 'Fogo'
  AND NOT EXISTS (SELECT 1 FROM pokemons WHERE LOWER(nome) = 'charmeleon')
LIMIT 1;

INSERT INTO pokemons
    (nivel, nome, hp, hp_maximo, forca_ataque, defesa, ataque_especial, defesa_especial, velocidade, id_tipo)
SELECT 1, 'Charizard', 78, 78, 84, 78, 109, 85, 100, tipos.id_tipo
FROM tipos
WHERE tipos.nome = 'Fogo'
  AND NOT EXISTS (SELECT 1 FROM pokemons WHERE LOWER(nome) = 'charizard')
LIMIT 1;

INSERT INTO pokemons
    (nivel, nome, hp, hp_maximo, forca_ataque, defesa, ataque_especial, defesa_especial, velocidade, id_tipo)
SELECT 1, 'Wartortle', 59, 59, 63, 80, 65, 80, 58, tipos.id_tipo
FROM tipos
WHERE tipos.nome = 'Agua'
  AND NOT EXISTS (SELECT 1 FROM pokemons WHERE LOWER(nome) = 'wartortle')
LIMIT 1;

INSERT INTO pokemons
    (nivel, nome, hp, hp_maximo, forca_ataque, defesa, ataque_especial, defesa_especial, velocidade, id_tipo)
SELECT 1, 'Blastoise', 79, 79, 83, 100, 85, 105, 78, tipos.id_tipo
FROM tipos
WHERE tipos.nome = 'Agua'
  AND NOT EXISTS (SELECT 1 FROM pokemons WHERE LOWER(nome) = 'blastoise')
LIMIT 1;

INSERT INTO pokemons
    (nivel, nome, hp, hp_maximo, forca_ataque, defesa, ataque_especial, defesa_especial, velocidade, id_tipo)
SELECT 1, 'Weepinbell', 65, 65, 90, 50, 85, 45, 55, tipos.id_tipo
FROM tipos
WHERE tipos.nome = 'Planta'
  AND NOT EXISTS (SELECT 1 FROM pokemons WHERE LOWER(nome) = 'weepinbell')
LIMIT 1;

INSERT INTO pokemons
    (nivel, nome, hp, hp_maximo, forca_ataque, defesa, ataque_especial, defesa_especial, velocidade, id_tipo)
SELECT 1, 'Victreebel', 80, 80, 105, 65, 100, 70, 70, tipos.id_tipo
FROM tipos
WHERE tipos.nome = 'Planta'
  AND NOT EXISTS (SELECT 1 FROM pokemons WHERE LOWER(nome) = 'victreebel')
LIMIT 1;

INSERT INTO pokemons
    (nivel, nome, hp, hp_maximo, forca_ataque, defesa, ataque_especial, defesa_especial, velocidade, id_tipo)
SELECT 1, 'Ninetales', 73, 73, 76, 75, 81, 100, 100, tipos.id_tipo
FROM tipos
WHERE tipos.nome = 'Fogo'
  AND NOT EXISTS (SELECT 1 FROM pokemons WHERE LOWER(nome) = 'ninetales')
LIMIT 1;

-- Pokémon da Geração I usados pelos oito líderes de ginásio.
-- Os atributos são os valores base consultados na PokeAPI; espécies/tipos foram
-- ajustados para a tipagem existente na Geração I (por exemplo, sem Aço ou Fada).
INSERT INTO pokemons
    (nivel, nome, hp, hp_maximo, forca_ataque, defesa, ataque_especial, defesa_especial, velocidade, id_tipo)
SELECT
    1,
    catalogo.nome,
    catalogo.hp,
    catalogo.hp,
    catalogo.ataque,
    catalogo.defesa,
    catalogo.ataque_especial,
    catalogo.defesa_especial,
    catalogo.velocidade,
    tipo_principal.id_tipo
FROM (
    SELECT 'Geodude' AS nome, 40 AS hp, 80 AS ataque, 100 AS defesa, 30 AS ataque_especial, 30 AS defesa_especial, 20 AS velocidade, 'Pedra' AS tipo_principal
    UNION ALL SELECT 'Rhyhorn', 80, 85, 95, 30, 30, 25, 'Terra'
    UNION ALL SELECT 'Onix', 35, 45, 160, 30, 45, 70, 'Pedra'
    UNION ALL SELECT 'Psyduck', 50, 52, 48, 65, 50, 55, 'Agua'
    UNION ALL SELECT 'Tentacool', 40, 40, 35, 50, 100, 70, 'Agua'
    UNION ALL SELECT 'Seadra', 55, 65, 95, 95, 45, 85, 'Agua'
    UNION ALL SELECT 'Magnemite', 25, 35, 70, 95, 55, 45, 'Eletrico'
    UNION ALL SELECT 'Voltorb', 40, 30, 50, 55, 55, 100, 'Eletrico'
    UNION ALL SELECT 'Electrode', 60, 50, 70, 80, 80, 150, 'Eletrico'
    UNION ALL SELECT 'Gloom', 60, 65, 70, 85, 75, 40, 'Planta'
    UNION ALL SELECT 'Tangela', 65, 55, 115, 100, 40, 60, 'Planta'
    UNION ALL SELECT 'Parasect', 60, 95, 80, 60, 80, 30, 'Inseto'
    UNION ALL SELECT 'Koffing', 40, 65, 95, 60, 45, 35, 'Veneno'
    UNION ALL SELECT 'Golbat', 75, 80, 70, 65, 75, 90, 'Veneno'
    UNION ALL SELECT 'Muk', 105, 105, 75, 65, 100, 50, 'Veneno'
    UNION ALL SELECT 'Kadabra', 40, 35, 30, 120, 70, 105, 'Psiquico'
    UNION ALL SELECT 'Hypno', 85, 73, 70, 73, 115, 67, 'Psiquico'
    UNION ALL SELECT 'Mr. Mime', 40, 45, 65, 100, 120, 90, 'Psiquico'
    UNION ALL SELECT 'Nidoking', 81, 102, 77, 85, 75, 85, 'Veneno'
    UNION ALL SELECT 'Rhydon', 105, 130, 120, 45, 45, 40, 'Terra'
    UNION ALL SELECT 'Golem', 80, 120, 130, 55, 65, 45, 'Pedra'
    UNION ALL SELECT 'Weezing', 65, 90, 120, 85, 70, 60, 'Veneno'
    UNION ALL SELECT 'Alakazam', 55, 50, 45, 135, 95, 120, 'Psiquico'
    UNION ALL SELECT 'Growlithe', 55, 70, 45, 70, 50, 60, 'Fogo'
    UNION ALL SELECT 'Ponyta', 50, 85, 55, 65, 65, 90, 'Fogo'
    UNION ALL SELECT 'Rapidash', 65, 100, 70, 80, 80, 105, 'Fogo'
    UNION ALL SELECT 'Arcanine', 90, 110, 80, 100, 80, 95, 'Fogo'
) AS catalogo
INNER JOIN tipos AS tipo_principal
    ON tipo_principal.nome = catalogo.tipo_principal
WHERE NOT EXISTS (
    SELECT 1
    FROM pokemons AS existente
    WHERE LOWER(existente.nome) = LOWER(catalogo.nome)
);

-- Preenche o tipo principal na relação completa sem alterar os registros existentes.
INSERT IGNORE INTO pokemon_tipos (id_pokemon, id_tipo, ordem)
SELECT pokemons.id_pokemon, pokemons.id_tipo, 1
FROM pokemons
WHERE pokemons.id_tipo IS NOT NULL;

-- Tipos secundários de espécies existentes e dos Pokémon dos ginásios.
-- As relações são adicionadas somente quando ambos os cadastros existem.
INSERT IGNORE INTO pokemon_tipos (id_pokemon, id_tipo, ordem)
SELECT pokemon.id_pokemon, tipo.id_tipo, 2
FROM (
    SELECT 'Bulbasaur' AS nome_pokemon, 'Veneno' AS nome_tipo
    UNION ALL SELECT 'Ivysaur', 'Veneno'
    UNION ALL SELECT 'Venusaur', 'Veneno'
    UNION ALL SELECT 'Bellsprout', 'Veneno'
    UNION ALL SELECT 'Weepinbell', 'Veneno'
    UNION ALL SELECT 'Victreebel', 'Veneno'
    UNION ALL SELECT 'Charizard', 'Voador'
    UNION ALL SELECT 'Geodude', 'Terra'
    UNION ALL SELECT 'Rhyhorn', 'Pedra'
    UNION ALL SELECT 'Onix', 'Terra'
    UNION ALL SELECT 'Tentacool', 'Veneno'
    UNION ALL SELECT 'Gloom', 'Veneno'
    UNION ALL SELECT 'Parasect', 'Planta'
    UNION ALL SELECT 'Golbat', 'Voador'
    UNION ALL SELECT 'Nidoking', 'Terra'
    UNION ALL SELECT 'Rhydon', 'Pedra'
    UNION ALL SELECT 'Golem', 'Terra'
) AS tipos_secundarios
INNER JOIN pokemons AS pokemon
    ON LOWER(pokemon.nome) = LOWER(tipos_secundarios.nome_pokemon)
INNER JOIN tipos AS tipo
    ON tipo.nome = tipos_secundarios.nome_tipo;

-- Atribui os movimentos acordados para cada espécie, na ordem exibida durante a luta.
INSERT IGNORE INTO pokemon_movimentos (id_pokemon, ordem, id_movimento)
SELECT pokemon.id_pokemon, catalogo.ordem, movimento.id_movimento
FROM (
    SELECT 'Bellsprout' AS nome_pokemon, 1 AS ordem, 'Vine Whip' AS nome_movimento
    UNION ALL SELECT 'Bellsprout', 2, 'Wrap'
    UNION ALL SELECT 'Bulbasaur', 1, 'Tackle'
    UNION ALL SELECT 'Bulbasaur', 2, 'Vine Whip'
    UNION ALL SELECT 'Charmander', 1, 'Scratch'
    UNION ALL SELECT 'Charmander', 2, 'Ember'
    UNION ALL SELECT 'Geodude', 1, 'Rock Throw'
    UNION ALL SELECT 'Geodude', 2, 'Tackle'
    UNION ALL SELECT 'Growlithe', 1, 'Ember'
    UNION ALL SELECT 'Growlithe', 2, 'Bite'
    UNION ALL SELECT 'Koffing', 1, 'Smog'
    UNION ALL SELECT 'Koffing', 2, 'Tackle'
    UNION ALL SELECT 'Magnemite', 1, 'Thunder Shock'
    UNION ALL SELECT 'Magnemite', 2, 'Thunder Wave'
    UNION ALL SELECT 'Ponyta', 1, 'Ember'
    UNION ALL SELECT 'Ponyta', 2, 'Stomp'
    UNION ALL SELECT 'Psyduck', 1, 'Water Pulse'
    UNION ALL SELECT 'Psyduck', 2, 'Confusion'
    UNION ALL SELECT 'Rhyhorn', 1, 'Horn Attack'
    UNION ALL SELECT 'Rhyhorn', 2, 'Rock Blast'
    UNION ALL SELECT 'Squirtle', 1, 'Tackle'
    UNION ALL SELECT 'Squirtle', 2, 'Water Gun'
    UNION ALL SELECT 'Tentacool', 1, 'Bubble Beam'
    UNION ALL SELECT 'Tentacool', 2, 'Poison Sting'
    UNION ALL SELECT 'Voltorb', 1, 'Spark'
    UNION ALL SELECT 'Voltorb', 2, 'Screech'
    UNION ALL SELECT 'Vulpix', 1, 'Ember'
    UNION ALL SELECT 'Vulpix', 2, 'Quick Attack'
    UNION ALL SELECT 'Charmeleon', 1, 'Ember'
    UNION ALL SELECT 'Charmeleon', 2, 'Metal Claw'
    UNION ALL SELECT 'Charmeleon', 3, 'Smokescreen'
    UNION ALL SELECT 'Gloom', 1, 'Petal Dance'
    UNION ALL SELECT 'Gloom', 2, 'Acid'
    UNION ALL SELECT 'Gloom', 3, 'Sleep Powder'
    UNION ALL SELECT 'Golbat', 1, 'Wing Attack'
    UNION ALL SELECT 'Golbat', 2, 'Bite'
    UNION ALL SELECT 'Golbat', 3, 'Poison Fang'
    UNION ALL SELECT 'Ivysaur', 1, 'Vine Whip'
    UNION ALL SELECT 'Ivysaur', 2, 'Razor Leaf'
    UNION ALL SELECT 'Ivysaur', 3, 'Sleep Powder'
    UNION ALL SELECT 'Kadabra', 1, 'Confusion'
    UNION ALL SELECT 'Kadabra', 2, 'Psybeam'
    UNION ALL SELECT 'Kadabra', 3, 'Recover'
    UNION ALL SELECT 'Mr. Mime', 1, 'Confusion'
    UNION ALL SELECT 'Mr. Mime', 2, 'Magical Leaf'
    UNION ALL SELECT 'Mr. Mime', 3, 'Barrier'
    UNION ALL SELECT 'Onix', 1, 'Rock Throw'
    UNION ALL SELECT 'Onix', 2, 'Dig'
    UNION ALL SELECT 'Onix', 3, 'Screech'
    UNION ALL SELECT 'Tangela', 1, 'Mega Drain'
    UNION ALL SELECT 'Tangela', 2, 'Bind'
    UNION ALL SELECT 'Tangela', 3, 'Sleep Powder'
    UNION ALL SELECT 'Wartortle', 1, 'Water Gun'
    UNION ALL SELECT 'Wartortle', 2, 'Bite'
    UNION ALL SELECT 'Wartortle', 3, 'Protect'
    UNION ALL SELECT 'Weepinbell', 1, 'Razor Leaf'
    UNION ALL SELECT 'Weepinbell', 2, 'Acid'
    UNION ALL SELECT 'Weepinbell', 3, 'Sleep Powder'
    UNION ALL SELECT 'Alakazam', 1, 'Psychic'
    UNION ALL SELECT 'Alakazam', 2, 'Psybeam'
    UNION ALL SELECT 'Alakazam', 3, 'Recover'
    UNION ALL SELECT 'Alakazam', 4, 'Thunder Wave'
    UNION ALL SELECT 'Arcanine', 1, 'Flamethrower'
    UNION ALL SELECT 'Arcanine', 2, 'Extreme Speed'
    UNION ALL SELECT 'Arcanine', 3, 'Dig'
    UNION ALL SELECT 'Arcanine', 4, 'Bite'
    UNION ALL SELECT 'Blastoise', 1, 'Surf'
    UNION ALL SELECT 'Blastoise', 2, 'Ice Beam'
    UNION ALL SELECT 'Blastoise', 3, 'Bite'
    UNION ALL SELECT 'Blastoise', 4, 'Rapid Spin'
    UNION ALL SELECT 'Charizard', 1, 'Flamethrower'
    UNION ALL SELECT 'Charizard', 2, 'Wing Attack'
    UNION ALL SELECT 'Charizard', 3, 'Slash'
    UNION ALL SELECT 'Charizard', 4, 'Dig'
    UNION ALL SELECT 'Electrode', 1, 'Thunderbolt'
    UNION ALL SELECT 'Electrode', 2, 'Swift'
    UNION ALL SELECT 'Electrode', 3, 'Light Screen'
    UNION ALL SELECT 'Electrode', 4, 'Screech'
    UNION ALL SELECT 'Golem', 1, 'Earthquake'
    UNION ALL SELECT 'Golem', 2, 'Rock Blast'
    UNION ALL SELECT 'Golem', 3, 'Strength'
    UNION ALL SELECT 'Golem', 4, 'Defense Curl'
    UNION ALL SELECT 'Hypno', 1, 'Confusion'
    UNION ALL SELECT 'Hypno', 2, 'Headbutt'
    UNION ALL SELECT 'Hypno', 3, 'Hypnosis'
    UNION ALL SELECT 'Hypno', 4, 'Psychic'
    UNION ALL SELECT 'Muk', 1, 'Sludge Bomb'
    UNION ALL SELECT 'Muk', 2, 'Body Slam'
    UNION ALL SELECT 'Muk', 3, 'Acid Armor'
    UNION ALL SELECT 'Muk', 4, 'Thunderbolt'
    UNION ALL SELECT 'Nidoking', 1, 'Earthquake'
    UNION ALL SELECT 'Nidoking', 2, 'Double Kick'
    UNION ALL SELECT 'Nidoking', 3, 'Ice Beam'
    UNION ALL SELECT 'Nidoking', 4, 'Toxic'
    UNION ALL SELECT 'Ninetales', 1, 'Flamethrower'
    UNION ALL SELECT 'Ninetales', 2, 'Confuse Ray'
    UNION ALL SELECT 'Ninetales', 3, 'Fire Spin'
    UNION ALL SELECT 'Ninetales', 4, 'Dig'
    UNION ALL SELECT 'Parasect', 1, 'Slash'
    UNION ALL SELECT 'Parasect', 2, 'Giga Drain'
    UNION ALL SELECT 'Parasect', 3, 'Stun Spore'
    UNION ALL SELECT 'Parasect', 4, 'Leech Life'
    UNION ALL SELECT 'Rapidash', 1, 'Flamethrower'
    UNION ALL SELECT 'Rapidash', 2, 'Bounce'
    UNION ALL SELECT 'Rapidash', 3, 'Stomp'
    UNION ALL SELECT 'Rapidash', 4, 'Agility'
    UNION ALL SELECT 'Rhydon', 1, 'Earthquake'
    UNION ALL SELECT 'Rhydon', 2, 'Rock Blast'
    UNION ALL SELECT 'Rhydon', 3, 'Horn Attack'
    UNION ALL SELECT 'Rhydon', 4, 'Scary Face'
    UNION ALL SELECT 'Seadra', 1, 'Water Gun'
    UNION ALL SELECT 'Seadra', 2, 'Twister'
    UNION ALL SELECT 'Seadra', 3, 'Ice Beam'
    UNION ALL SELECT 'Seadra', 4, 'Agility'
    UNION ALL SELECT 'Venusaur', 1, 'Razor Leaf'
    UNION ALL SELECT 'Venusaur', 2, 'Sludge Bomb'
    UNION ALL SELECT 'Venusaur', 3, 'Sleep Powder'
    UNION ALL SELECT 'Venusaur', 4, 'Synthesis'
    UNION ALL SELECT 'Victreebel', 1, 'Razor Leaf'
    UNION ALL SELECT 'Victreebel', 2, 'Sludge Bomb'
    UNION ALL SELECT 'Victreebel', 3, 'Sleep Powder'
    UNION ALL SELECT 'Victreebel', 4, 'Swords Dance'
    UNION ALL SELECT 'Weezing', 1, 'Sludge Bomb'
    UNION ALL SELECT 'Weezing', 2, 'Flamethrower'
    UNION ALL SELECT 'Weezing', 3, 'Smokescreen'
    UNION ALL SELECT 'Weezing', 4, 'Toxic'
) AS catalogo
INNER JOIN pokemons AS pokemon
    ON LOWER(pokemon.nome) = LOWER(catalogo.nome_pokemon)
INNER JOIN movimentos AS movimento
    ON movimento.nome = catalogo.nome_movimento;

-- Líderes, ginásios e insígnias. O progresso de vitória é individual por usuário
-- e fica em treinadores_progresso, não nesta tabela de cadastro.
CREATE TABLE IF NOT EXISTS treinadores (
    id_treinador INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nivel_jornada TINYINT UNSIGNED NOT NULL,
    nome VARCHAR(100) NOT NULL,
    nome_insignia VARCHAR(100) NOT NULL,
    descricao VARCHAR(255) NOT NULL,
    id_tipo_especialidade INT UNSIGNED NOT NULL,
    PRIMARY KEY (id_treinador),
    UNIQUE KEY uq_treinadores_nivel (nivel_jornada),
    CONSTRAINT fk_treinadores_tipo
        FOREIGN KEY (id_tipo_especialidade) REFERENCES tipos (id_tipo)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Guarda os três Pokémon de cada líder, sua posição na batalha e a ordem de
-- liberação para captura (do menor ao maior total de atributos base).
CREATE TABLE IF NOT EXISTS treinador_pokemons (
    id_treinador INT UNSIGNED NOT NULL,
    id_pokemon INT UNSIGNED NOT NULL,
    ordem_batalha TINYINT UNSIGNED NOT NULL,
    ordem_captura TINYINT UNSIGNED NOT NULL,
    PRIMARY KEY (id_treinador, id_pokemon),
    UNIQUE KEY uq_treinador_ordem_batalha (id_treinador, ordem_batalha),
    UNIQUE KEY uq_treinador_ordem_captura (id_treinador, ordem_captura),
    CONSTRAINT fk_treinador_pokemons_treinador
        FOREIGN KEY (id_treinador) REFERENCES treinadores (id_treinador)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_treinador_pokemons_pokemon
        FOREIGN KEY (id_pokemon) REFERENCES pokemons (id_pokemon)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Migra a posição de Giovanni da antiga jornada de sete níveis para o nível 8.
-- O nível 7 fica disponível para o ginásio de Blaine.
UPDATE treinadores
SET nivel_jornada = 8
WHERE nome = 'Giovanni'
  AND nivel_jornada = 7;

-- Cada usuário tem seu próprio número de vitórias e capturas liberadas por líder.
-- vitorias > 0 indica que o treinador já foi vencido ao menos uma vez.
CREATE TABLE IF NOT EXISTS treinadores_progresso (
    id_usuario INT UNSIGNED NOT NULL,
    id_treinador INT UNSIGNED NOT NULL,
    vitorias INT UNSIGNED NOT NULL DEFAULT 0,
    capturas_desbloqueadas TINYINT UNSIGNED NOT NULL DEFAULT 0,
    data_primeira_vitoria DATETIME NULL,
    data_ultima_vitoria DATETIME NULL,
    PRIMARY KEY (id_usuario, id_treinador),
    CONSTRAINT fk_treinadores_progresso_usuario
        FOREIGN KEY (id_usuario) REFERENCES usuarios (id_usuario)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_treinadores_progresso_treinador
        FOREIGN KEY (id_treinador) REFERENCES treinadores (id_treinador)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- Histórico de Pokémon liberados e efetivamente capturados por usuário.
-- A restrição única impede reivindicar duas vezes a mesma recompensa.
CREATE TABLE IF NOT EXISTS capturas_treinadores (
    id_captura INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_usuario INT UNSIGNED NOT NULL,
    id_treinador INT UNSIGNED NOT NULL,
    id_pokemon INT UNSIGNED NOT NULL,
    id_time_pokemon INT UNSIGNED NULL,
    ordem_captura TINYINT UNSIGNED NOT NULL,
    data_captura DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_captura),
    UNIQUE KEY uq_captura_usuario_treinador_ordem (id_usuario, id_treinador, ordem_captura),
    UNIQUE KEY uq_captura_time_pokemon (id_time_pokemon),
    CONSTRAINT fk_capturas_treinadores_usuario
        FOREIGN KEY (id_usuario) REFERENCES usuarios (id_usuario)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_capturas_treinadores_treinador
        FOREIGN KEY (id_treinador) REFERENCES treinadores (id_treinador)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_capturas_treinadores_pokemon
        FOREIGN KEY (id_pokemon) REFERENCES pokemons (id_pokemon)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT fk_capturas_treinadores_time_pokemon
        FOREIGN KEY (id_time_pokemon) REFERENCES time_pokemons (id_time_pokemon)
        ON UPDATE CASCADE
        ON DELETE SET NULL
) ENGINE=InnoDB;

-- Relaciona cada registro de batalha já existente ao líder desafiado.
CREATE TABLE IF NOT EXISTS batalha_treinadores (
    id_batalha INT UNSIGNED NOT NULL,
    id_treinador INT UNSIGNED NOT NULL,
    PRIMARY KEY (id_batalha),
    CONSTRAINT fk_batalha_treinadores_batalha
        FOREIGN KEY (id_batalha) REFERENCES batalhas (id_batalha)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_batalha_treinadores_treinador
        FOREIGN KEY (id_treinador) REFERENCES treinadores (id_treinador)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Cadastra os oito líderes clássicos da região de Kanto sem duplicar níveis.
INSERT INTO treinadores (nivel_jornada, nome, nome_insignia, descricao, id_tipo_especialidade)
SELECT 1, 'Brock', 'Insígnia Rocha', 'Líder do ginásio especializado em Pokémon de Pedra.', tipos.id_tipo
FROM tipos
WHERE tipos.nome = 'Pedra'
  AND NOT EXISTS (SELECT 1 FROM treinadores WHERE nivel_jornada = 1)
LIMIT 1;

INSERT INTO treinadores (nivel_jornada, nome, nome_insignia, descricao, id_tipo_especialidade)
SELECT 2, 'Misty', 'Insígnia Cascata', 'Líder do ginásio especializado em Pokémon de Água.', tipos.id_tipo
FROM tipos
WHERE tipos.nome = 'Agua'
  AND NOT EXISTS (SELECT 1 FROM treinadores WHERE nivel_jornada = 2)
LIMIT 1;

INSERT INTO treinadores (nivel_jornada, nome, nome_insignia, descricao, id_tipo_especialidade)
SELECT 3, 'Lt. Surge', 'Insígnia Trovão', 'Líder do ginásio especializado em Pokémon Elétricos.', tipos.id_tipo
FROM tipos
WHERE tipos.nome = 'Eletrico'
  AND NOT EXISTS (SELECT 1 FROM treinadores WHERE nivel_jornada = 3)
LIMIT 1;

INSERT INTO treinadores (nivel_jornada, nome, nome_insignia, descricao, id_tipo_especialidade)
SELECT 4, 'Erika', 'Insígnia Arco-íris', 'Líder do ginásio especializado em Pokémon de Planta.', tipos.id_tipo
FROM tipos
WHERE tipos.nome = 'Planta'
  AND NOT EXISTS (SELECT 1 FROM treinadores WHERE nivel_jornada = 4)
LIMIT 1;

INSERT INTO treinadores (nivel_jornada, nome, nome_insignia, descricao, id_tipo_especialidade)
SELECT 5, 'Koga', 'Insígnia Alma', 'Líder do ginásio especializado em Pokémon Venenosos.', tipos.id_tipo
FROM tipos
WHERE tipos.nome = 'Veneno'
  AND NOT EXISTS (SELECT 1 FROM treinadores WHERE nivel_jornada = 5)
LIMIT 1;

INSERT INTO treinadores (nivel_jornada, nome, nome_insignia, descricao, id_tipo_especialidade)
SELECT 6, 'Sabrina', 'Insígnia Pântano', 'Líder do ginásio especializado em Pokémon Psíquicos.', tipos.id_tipo
FROM tipos
WHERE tipos.nome = 'Psiquico'
  AND NOT EXISTS (SELECT 1 FROM treinadores WHERE nivel_jornada = 6)
LIMIT 1;

INSERT INTO treinadores (nivel_jornada, nome, nome_insignia, descricao, id_tipo_especialidade)
SELECT 7, 'Blaine', 'Insígnia Vulcão', 'Líder do ginásio especializado em Pokémon de Fogo.', tipos.id_tipo
FROM tipos
WHERE tipos.nome = 'Fogo'
  AND NOT EXISTS (SELECT 1 FROM treinadores WHERE nivel_jornada = 7)
LIMIT 1;

INSERT INTO treinadores (nivel_jornada, nome, nome_insignia, descricao, id_tipo_especialidade)
SELECT 8, 'Giovanni', 'Insígnia Terra', 'Líder do ginásio especializado em Pokémon de Terra.', tipos.id_tipo
FROM tipos
WHERE tipos.nome = 'Terra'
  AND NOT EXISTS (SELECT 1 FROM treinadores WHERE nivel_jornada = 8)
LIMIT 1;

-- Desloca temporariamente as ordens dos níveis 5 a 8 para permitir a inclusão
-- e reordenação idempotente das equipes maiores sem colidir com as chaves únicas.
UPDATE treinador_pokemons AS equipe
INNER JOIN treinadores
    ON treinadores.id_treinador = equipe.id_treinador
SET equipe.ordem_batalha = equipe.ordem_batalha + 10,
    equipe.ordem_captura = equipe.ordem_captura + 10
WHERE treinadores.nivel_jornada BETWEEN 5 AND 8;

-- As equipes começam pelo Pokémon mais fraco e terminam pelo mais forte.
-- A ordem de captura também acompanha o total dos atributos base, do menor ao maior.
INSERT IGNORE INTO treinador_pokemons (id_treinador, id_pokemon, ordem_batalha, ordem_captura)
SELECT treinadores.id_treinador, pokemons.id_pokemon, equipe.ordem_batalha, equipe.ordem_captura
FROM (
    SELECT 1 AS nivel_jornada, 'Geodude' AS nome_pokemon, 1 AS ordem_batalha, 1 AS ordem_captura
    UNION ALL SELECT 1, 'Rhyhorn', 2, 2
    UNION ALL SELECT 1, 'Onix', 3, 3
    UNION ALL SELECT 2, 'Psyduck', 1, 1
    UNION ALL SELECT 2, 'Tentacool', 2, 2
    UNION ALL SELECT 2, 'Seadra', 3, 3
    UNION ALL SELECT 3, 'Magnemite', 1, 1
    UNION ALL SELECT 3, 'Voltorb', 2, 2
    UNION ALL SELECT 3, 'Electrode', 3, 3
    UNION ALL SELECT 4, 'Gloom', 1, 1
    UNION ALL SELECT 4, 'Tangela', 3, 3
    UNION ALL SELECT 4, 'Parasect', 2, 2
    UNION ALL SELECT 5, 'Koffing', 1, 1
    UNION ALL SELECT 5, 'Golbat', 2, 2
    UNION ALL SELECT 5, 'Weezing', 3, 3
    UNION ALL SELECT 5, 'Muk', 4, 4
    UNION ALL SELECT 6, 'Kadabra', 1, 1
    UNION ALL SELECT 6, 'Mr. Mime', 2, 2
    UNION ALL SELECT 6, 'Hypno', 3, 3
    UNION ALL SELECT 6, 'Alakazam', 4, 4
    UNION ALL SELECT 7, 'Growlithe', 1, 1
    UNION ALL SELECT 7, 'Ponyta', 2, 2
    UNION ALL SELECT 7, 'Rapidash', 3, 3
    UNION ALL SELECT 7, 'Arcanine', 4, 4
    UNION ALL SELECT 8, 'Rhyhorn', 1, 1
    UNION ALL SELECT 8, 'Rhydon', 2, 2
    UNION ALL SELECT 8, 'Golem', 3, 3
    UNION ALL SELECT 8, 'Nidoking', 4, 4
) AS equipe
INNER JOIN treinadores
    ON treinadores.nivel_jornada = equipe.nivel_jornada
INNER JOIN pokemons
    ON LOWER(pokemons.nome) = LOWER(equipe.nome_pokemon);

-- Reaplica as ordens finais da batalha e da captura após a migração temporária.
UPDATE treinador_pokemons AS treinador_pokemon
INNER JOIN treinadores
    ON treinadores.id_treinador = treinador_pokemon.id_treinador
INNER JOIN (
    SELECT 5 AS nivel_jornada, 'Koffing' AS nome_pokemon, 1 AS ordem
    UNION ALL SELECT 5, 'Golbat', 2
    UNION ALL SELECT 5, 'Weezing', 3
    UNION ALL SELECT 5, 'Muk', 4
    UNION ALL SELECT 6, 'Kadabra', 1
    UNION ALL SELECT 6, 'Mr. Mime', 2
    UNION ALL SELECT 6, 'Hypno', 3
    UNION ALL SELECT 6, 'Alakazam', 4
    UNION ALL SELECT 7, 'Growlithe', 1
    UNION ALL SELECT 7, 'Ponyta', 2
    UNION ALL SELECT 7, 'Rapidash', 3
    UNION ALL SELECT 7, 'Arcanine', 4
    UNION ALL SELECT 8, 'Rhyhorn', 1
    UNION ALL SELECT 8, 'Rhydon', 2
    UNION ALL SELECT 8, 'Golem', 3
    UNION ALL SELECT 8, 'Nidoking', 4
) AS ordem_final
    ON ordem_final.nivel_jornada = treinadores.nivel_jornada
INNER JOIN pokemons
    ON LOWER(pokemons.nome) = LOWER(ordem_final.nome_pokemon)
   AND pokemons.id_pokemon = treinador_pokemon.id_pokemon
SET treinador_pokemon.ordem_batalha = ordem_final.ordem,
    treinador_pokemon.ordem_captura = ordem_final.ordem
WHERE treinadores.nivel_jornada BETWEEN 5 AND 8;
