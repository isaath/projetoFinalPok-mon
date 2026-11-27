# PokeBagual

**Monte seu time, enfrente líderes de ginásio e conquiste as insígnias de Kanto.**

PokeBagual é um jogo web inspirado no universo Pokémon e criado como trabalho acadêmico interdisciplinar do Curso Técnico em Desenvolvimento de Sistemas do IFRS Campus Canoas.

---

## Apresentação

- **Projeto:** PokeBagual
- **Desenvolvedores:** Henrique Miranda da Silva e Isadora Amaral
- **Curso:** Técnico em Desenvolvimento de Sistemas
- **Turma:** 3º ano do Curso Técnico em Desenvolvimento de Sistemas
- **Instituição:** Instituto Federal do Rio Grande do Sul - Campus Canoas
- **Disciplinas:** Programação Orientada a Objetos I, Linguagens para a Web II e Banco de Dados II

## Sobre o jogo

PokeBagual reúne coleção de Pokémon, montagem de equipes, batalhas por turnos, capturas, recompensas e uma jornada pelos oito ginásios de Kanto.

A aventura começa com a escolha de três Pokémon iniciais. Depois, o jogador pode:

- organizar seu time ativo e seus Pokémon de reserva;
- enfrentar os líderes Brock, Misty, Lt. Surge, Erika, Koga, Sabrina, Blaine e Giovanni;
- conquistar insígnias e PokePila;
- capturar novos Pokémon após vencer batalhas;
- comprar itens no Bolicho;
- curar o time no Pokecenter;
- evoluir Pokémon com Item Evolutivo;
- explorar locais especiais do mapa.

## Como acessar o jogo

### Para quem vai testar no computador

O projeto foi preparado para o ambiente local XAMPP.

1. Instale e abra o **XAMPP**.
2. Inicie o módulo **Apache**.
3. Inicie o módulo **MySQL**.
4. Coloque a pasta do projeto em:

   `C:\xampp\htdocs\projetoFinalPok-mon`

5. Abra o **DBeaver**, o **phpMyAdmin** ou outro gerenciador compatível.
6. Importe ou execute o arquivo completo:

   [database/schema.sql](./database/schema.sql)

7. Confirme que o banco `bancodedados_pokefish` foi criado.
8. Abra o navegador e acesse:

   [http://localhost/projetoFinalPok-mon/public/index.php](http://localhost/projetoFinalPok-mon/public/index.php)

### Configuração padrão do banco

O projeto está configurado em `config/config.php` com:

| Informação | Valor |
|---|---|
| Servidor | `localhost` |
| Banco | `bancodedados_pokefish` |
| Usuário | `root` |
| Senha | vazia |

Caso o MySQL local use outra senha ou outro usuário, altere esses dados no arquivo [config/config.php](./config/config.php).

## Como usar

1. Acesse a página inicial.
2. Clique em **Registrar** e crie uma conta.
3. Entre com o e-mail e a senha cadastrados.
4. Escolha três dos cinco Pokémon iniciais disponíveis.
5. Abra o painel e clique em **Jogar** para visualizar o mapa.
6. Comece pelo primeiro ginásio disponível e derrote o líder.
7. Após uma vitória, acompanhe o resultado da captura na tela seguinte.
8. Use **Meu Time** para consultar ativos, reservas e HP.
9. Visite o Bolicho para comprar itens e o Pokecenter para restaurar o time.
10. Complete os oito níveis para se tornar campeão.

## Primeira experiência

Ao criar a conta, o jogador recebe um time inicial. Os Pokémon disponíveis para a escolha são:

- Vulpix
- Bellsprout
- Charmander
- Squirtle
- Bulbasaur

Os três Pokémon escolhidos ocupam as posições principais do time e ficam salvos na conta do usuário.

## Onde encontrar cada função

| Função | Onde acessar |
|---|---|
| Login e cadastro | Botões de entrada e registro na página inicial |
| Escolha do time | Tela mostrada na primeira entrada no jogo |
| Painel principal | Opções de jogar, perfil, time e sair |
| Mapa e ginásios | Botão **Jogar** |
| Pokémon ativos e reservas | Botão **Meu Time** |
| Loja de itens | Link **Bolicho** |
| Recuperação de HP | Link **Pokecenter** |
| Perfil e insígnias | Botão **Perfil** |

## Como funcionam as batalhas

As batalhas acontecem por turnos. Em cada turno, o jogador escolhe um movimento para seu Pokémon ativo.

- Ataques físicos usam atributos de Ataque e Defesa.
- Ataques especiais usam Ataque Especial e Defesa Especial.
- Velocidade e prioridade definem a ordem das ações.
- Movimentos podem causar dano, condições de status ou efeitos especiais.
- Quando um Pokémon desmaia, o próximo da equipe ativa entra automaticamente.
- Ao vencer, o jogador recebe PokePila e pode liberar uma tentativa de captura.

## Loja e recuperação

No Bolicho, o jogador pode comprar Potion, Super Potion, Hyper Potion, Max Potion, Full Restore, curas de status, Revive e Item Evolutivo.

Cada compra usa o saldo salvo na conta do usuário e adiciona o item ao inventário.

No Pokecenter, todos os Pokémon associados aos times do jogador podem ser curados. Se o time já estiver com HP completo, nenhuma cobrança é feita.

## Requisitos para executar

- Windows com XAMPP
- Apache
- PHP compatível com PDO
- MySQL ou MariaDB
- Navegador web atualizado
- Conexão com a internet para consultar informações e imagens da PokeAPI

## Estrutura geral

O sistema usa a arquitetura MVC:

- **Models:** regras do jogo e acesso ao banco de dados.
- **Views:** telas e apresentação para o usuário.
- **Controllers:** recebem as ações e coordenam os Models e Views.

A comunicação entre a interface e o servidor utiliza PHP, JavaScript, requisições assíncronas e JSON.

Para uma descrição técnica dos arquivos, banco de dados e requisitos acadêmicos, consulte:

- [AGENTS.md](./AGENTS.md)
- [database/schema.sql](./database/schema.sql)
- [database/modelo_relacional.mmd](./database/modelo_relacional.mmd)

## Problemas comuns

### A página não abre

- confirme que o Apache está iniciado;
- confira se a pasta está em `C:\xampp\htdocs`;
- use exatamente o endereço mostrado na seção **Como acessar o jogo**.

### Não foi possível conectar ao banco

- confirme que o MySQL está iniciado;
- execute o arquivo `database/schema.sql`;
- confira o nome do banco e os dados de acesso em `config/config.php`.

### A página mostra erro 404

Use a URL com o parâmetro `rota`, por exemplo:

`http://localhost/projetoFinalPok-mon/public/index.php?rota=login`

As rotas disponíveis incluem `login`, `registrar`, `jogar`, `mapa`, `meu_time`, `bolicho`, `centro` e `perfil`.

---

**PokeBagual - Monte seu time e conquiste as insígnias de Kanto!**
