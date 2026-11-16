# PokeFish

## Sobre o Projeto

O **PokeFish** é um jogo de batalhas inspirado no universo Pokémon, com uma temática voltada para praias e elementos tropicais. O jogador poderá montar sua própria equipe de criaturas, participar de batalhas, conquistar recompensas, comprar itens na loja e evoluir seus Pokémon.

O projeto está sendo desenvolvido como parte de um trabalho acadêmico interdisciplinar, integrando os conhecimentos das disciplinas de Programação Orientada a Objetos I, Linguagens para a Web II e Banco de Dados II.

## Funcionalidades

* **Gerenciamento de usuários:** cadastro, login e gerenciamento de perfil.
* **Montagem de equipes:** seleção e organização dos Pokémon para as batalhas.
* **Sistema de batalhas:** combates por turnos contra outros jogadores ou adversários controlados pelo sistema.
* **Tipos de Pokémon:** sistema de vantagens e desvantagens entre os tipos Água, Fogo e Elétrico.
* **Loja:** compra de itens para recuperação, tratamento de condições e evolução dos Pokémon.
* **Inventário:** gerenciamento dos itens adquiridos pelo jogador.
* **Evolução:** possibilidade de evoluir Pokémon utilizando itens específicos.
* **Sistema de recompensas:** obtenção de Pokédollars após vencer batalhas.
* **Histórico de batalhas:** registro dos resultados e informações dos combates.
* **Consumo de API:** utilização de uma API externa para obtenção de dados.
* **Interface web:** telas para navegação, gerenciamento da equipe, loja e batalhas.

## Regras das Batalhas

As batalhas acontecem por turnos, permitindo que o jogador escolha entre atacar, utilizar um item ou trocar de Pokémon.

A ordem dos turnos é definida pelos seguintes critérios:

1. Pokémon com maior prioridade age primeiro.
2. Em caso de empate na prioridade, o Pokémon com maior velocidade age primeiro.
3. Se houver empate nos dois atributos, a ordem será definida aleatoriamente.

### Vantagens entre os Tipos

O sistema utiliza três tipos de Pokémon:

* **Água:** forte contra Fogo e fraco contra Elétrico.
* **Fogo:** forte contra Elétrico e fraco contra Água.
* **Elétrico:** forte contra Água e fraco contra Fogo.

Os ataques causam o dobro do dano quando possuem vantagem de tipo e metade do dano quando possuem desvantagem.

### Condições de Vitória

* O jogador perde quando todos os seus Pokémon são derrotados.
* As batalhas possuem um limite de 10 turnos.
* Caso nenhum jogador tenha todos os seus Pokémon derrotados ao final do décimo turno, vence aquele que possuir a maior quantidade total de HP restante na equipe.

## Tecnologias Utilizadas

* **PHP:** desenvolvimento do backend e aplicação dos conceitos de orientação a objetos.
* **JavaScript:** interatividade e funcionalidades dinâmicas da interface.
* **HTML5:** estrutura das páginas.
* **CSS3:** estilização e layout.
* **MySQL/MariaDB:** armazenamento e gerenciamento dos dados.
* **PDO:** conexão e comunicação com o banco de dados.
* **Fetch API:** realização de requisições assíncronas.
* **JSON:** troca e organização de dados.
* **API externa:** obtenção de informações para o jogo.
* **Git e GitHub:** versionamento e colaboração no desenvolvimento.

## Arquitetura do Projeto

O projeto utiliza o padrão arquitetural **MVC (Model-View-Controller)**, com o objetivo de organizar o código e separar as responsabilidades da aplicação.

* **Model:** representa os dados e as regras de negócio.
* **View:** responsável pela apresentação das informações ao usuário.
* **Controller:** recebe as requisições, coordena as ações e conecta os modelos às visualizações.

Também são aplicados conceitos de programação orientada a objetos, como encapsulamento, herança, polimorfismo, associação, agregação e composição.

## Banco de Dados

O banco armazena usuários, Pokémon, equipes, itens e batalhas. Para criar as tabelas sem editar arquivos JSON:

1. Inicie o **MySQL** no XAMPP.
2. Abra `database/schema.sql` no DBeaver e conecte-se ao servidor MySQL local.
3. Execute o **script completo** pela opção **Execute SQL Script** do DBeaver. Executar apenas a instrução atual não roda o arquivo inteiro. O script cria o banco `bancodedados_pokefish`, suas tabelas, os tipos `Agua`, `Fogo` e `Planta`, e os cinco Pokémon iniciais usados na escolha do time. Ele pode ser executado novamente para completar esses cadastros sem duplicá-los.
4. A conexão da aplicação está configurada em `config/config.php` para `localhost`, banco `bancodedados_pokefish`, usuário `root` e senha vazia. Ajuste esses dados caso sua instalação use outras credenciais.

O `bd.erd` é um diagrama visual de referência e não gera o banco automaticamente. Para a estrutura simplificada e oficial do projeto, use `database/schema.sql` no DBeaver. Os nomes de tabelas, colunas e tipos cadastrados no script não usam acentos.

### Decisões de jogo aprovadas

As regras abaixo foram decididas por Henrique e Isadora. Mantenha código e documentação alinhados com elas; não mude esses valores sem consultar os desenvolvedores. O documento de referência para regras do agente é `AGENTS.md`, seções 19.1, 19.4 e 19.6.

**Escolha do time inicial:** no primeiro login, o jogador escolhe três entre os cinco Pokémon com menor soma dos atributos base entre os nove nomes de `PokeApiService`:

| Pokémon | Soma dos atributos base |
|---|---:|
| Vulpix | 299 |
| Bellsprout | 300 |
| Charmander | 309 |
| Squirtle | 314 |
| Bulbasaur | 318 |

A seleção é persistida nas posições 1, 2 e 3. Depois de salvar, o jogador vai ao painel; nos próximos acessos, também segue diretamente ao painel.

O botão **Time** mostra os três Pokémon ativos e os demais na reserva. É possível reorganizar a prioridade da equipe ou colocar uma reserva no lugar do terceiro Pokémon ativo; o Pokémon substituído passa para a reserva.

**Recompensas por batalha:**

| Nível | Primeira vitória | Cada repetição |
|---|---:|---:|
| 1 | 50 | 20 |
| 2 | 70 | 25 |
| 3 | 90 | 30 |
| 4 | 110 | 35 |
| 5 | 130 | 40 |
| 6 | 150 | 45 |
| 7 | 170 | 50 |

A primeira campanha completa rende 770 Pokédollars em recompensas de primeira vitória. A repetição de um nível não pode alterar a recompensa inédita de outro nível.

**Pokecenter:** restaura o HP de todos os Pokémon associados aos times do jogador, inclusive os desmaiados, até o máximo. Cobra 40 Pokédollars uma única vez por visita, somente se houver HP a recuperar. O HP individual deve ser persistido sem alterar o cadastro global do Pokémon.

**Preços aprovados da loja:**

| Item | Preço |
|---|---:|
| Potion | 25 |
| Super Potion | 50 |
| Hyper Potion | 100 |
| Max Potion | 180 |
| Full Restore | 220 |
| Antidote | 15 |
| Burn Heal | 15 |
| Ice Heal | 15 |
| Awakening | 15 |
| Paralyze Heal | 15 |
| Full Heal | 30 |
| Revive | 70 |

Esses itens são cadastrados por `database/schema.sql`. Os itens de evolução ainda não têm lista ou preços aprovados.

**Ainda pendente de decisão:** fórmulas e condições detalhadas das batalhas por turno, regra de captura, eventos aleatórios e lista/preço/efeitos dos itens de evolução. Não assuma valores para esses pontos sem consultar os desenvolvedores.

## Objetivos Acadêmicos

O desenvolvimento do PokeFish tem como objetivo aplicar, de forma integrada, os conhecimentos adquiridos nas disciplinas envolvidas, incluindo:

* Programação orientada a objetos em PHP e JavaScript.
* Organização de aplicações utilizando MVC.
* Persistência de dados com PDO.
* Modelagem e implementação de banco de dados relacional.
* Utilização de operações SQL para manipulação e consulta de dados.
* Comunicação assíncrona com Fetch API e JSON.
* Integração com APIs externas.
* Desenvolvimento de interfaces web interativas.
* Utilização do Git e GitHub para controle de versão.

## Desenvolvedores

* Henrique Miranda da Silva
* Isadora Amaral

**Instituição:** IFRS — Campus Canoas

## Status do Projeto

O projeto está em desenvolvimento. As funcionalidades descritas representam os objetivos planejados e poderão ser atualizadas conforme a implementação avançar.

## Apoio do agente personalizado Professor MONSTROBOLSO

Este projeto conta com o agente personalizado **Professor MONSTROBOLSO**, que auxilia desenvolvedores e demais pessoas interessadas em compreender o sistema, avaliar o que já está implementado e colaborar no desenvolvimento do que ainda falta. Ele deve orientar de forma didática, considerar as regras registradas em `AGENTS.md` e verificar o código existente antes de considerar uma funcionalidade concluída.

Para começar uma conversa, selecione o agente **Professor MONSTROBOLSO** e envie a mensagem abaixo. Ela ajudará o agente a entender o estado atual do projeto e a combinar com você um próximo passo de desenvolvimento.

### Mensagem para iniciar

```text
Olá, Professor MONSTROBOLSO! Quero compreender e ajudar a desenvolver este projeto.

Leia as instruções do AGENTS.md. Antes de analisar o código, sugerir tarefas ou propor alterações, comece explicando didaticamente a lógica completa do jogo que está registrada no documento. Apresente o fluxo do usuário desde o login e a escolha inicial do time até o mapa, níveis, batalhas por turno, eventos aleatórios, recompensas, insígnias, Meu time, Pokecenter, loja e conclusão da campanha. Diferencie claramente as regras já confirmadas das decisões ainda pendentes, para que eu compreenda o trabalho antes de começarmos qualquer outra atividade.

Depois dessa explicação, analise o código e a estrutura atuais. Compare o que realmente está implementado com a lógica do jogo e os requisitos acadêmicos; não considere uma funcionalidade concluída apenas porque ela está descrita ou existe um arquivo relacionado. Apresente, em linguagem clara:
1. O que já está implementado e funcionando, com as evidências encontradas no código.
2. O que está parcialmente implementado ou precisa de validação.
3. O que ainda está pendente, incluindo requisitos acadêmicos relevantes.
4. Quais são os próximos passos recomendados e como podemos escolher uma tarefa adequada para começar.

Explique como os arquivos e as camadas MVC envolvidos se relacionam. Se houver decisões de regra de negócio ainda não definidas, identifique-as e peça minha orientação antes de tratá-las como requisitos.

Depois da análise, ajude-me a desenvolver a tarefa que escolhermos em etapas: explique o objetivo e os conceitos, preserve alterações existentes, implemente somente o escopo combinado, indique como testar e resuma o que foi alterado. Não faça commits nem pushes.
```

---

**PokeFish — Prepare seu time, encare as ondas e torne-se uma lenda das batalhas!**
