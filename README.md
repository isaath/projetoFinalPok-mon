# PokeBagual

## Sobre o Projeto

O **PokeBagual** é um jogo web acadêmico inspirado no universo Pokémon, com uma jornada ambientada na região de Kanto e identidade visual ligada ao Rio Grande do Sul.

O projeto está sendo desenvolvido como parte de um trabalho acadêmico interdisciplinar, integrando os conhecimentos das disciplinas de Programação Orientada a Objetos I, Linguagens para a Web II e Banco de Dados II.

## Funcionalidades

* **Gerenciamento de usuários:** cadastro, login e gerenciamento de perfil.
* **Montagem de equipes:** seleção e organização dos Pokémon para as batalhas.
* **Sistema de batalhas:** turnos, escolha de movimentos, dano, condições temporárias, troca automática dos Pokémon desmaiados, HP persistente, progresso, recompensas e registro. Após uma vitória há uma tentativa de captura com chance inicial de 30%, que aumenta 20 pontos percentuais após cada falha até 90%; espécies já possuídas são puladas, e uma captura bem-sucedida adiciona o Pokémon à reserva sem removê-lo da equipe do líder.
* **Tipos de Pokémon:** as regras aprovadas usam a efetividade de tipos da Geração III como referência, mas mantêm as tipagens de espécies da Geração I.
* **Loja:** compra de itens para recuperação, tratamento de condições e evolução dos Pokémon.
* **Inventário:** gerenciamento dos itens adquiridos pelo jogador.
* **Evolução:** possibilidade de evoluir Pokémon utilizando itens específicos.
* **Sistema de recompensas:** obtenção de PokePila após vencer batalhas.
* **Histórico de batalhas:** registro dos resultados e informações dos combates.
* **Consumo de API:** obtenção dos sprites dos Pokémon pela PokeAPI; o catálogo e os atributos exibidos vêm do banco de dados do projeto.
* **Interface web:** telas para navegação, gerenciamento da equipe, loja e batalhas.

## Regras de batalha aprovadas

O combate é uma adaptação acadêmica de FireRed/LeafGreen (Geração III), sem tentar reproduzir todos os sistemas do jogo original. A equipe do líder é controlada pelo servidor; o jogador escolhe um movimento em cada turno.

### Ordem das ações

1. A ação com maior prioridade ocorre primeiro, mesmo que o Pokémon seja mais lento.
2. Entre ações com a mesma prioridade, o Pokémon com maior Velocidade age primeiro.
3. Se a prioridade e a Velocidade empatarem, a ordem é sorteada.

### Efetividade dos tipos

A efetividade altera o dano, não a ordem de ação. Será usado o gráfico de tipos das Gerações II a V, aplicável a FireRed/LeafGreen: 17 tipos e nenhum tipo Fada. Ataques super efetivos têm multiplicador 2; ataques pouco efetivos, 0,5; imunidades, 0. Contra um Pokémon de dois tipos, os multiplicadores defensivos são multiplicados. Um movimento do mesmo tipo do atacante recebe STAB de 1,5.

As espécies do projeto mantêm as tipagens da Geração I, por decisão dos desenvolvedores. Por exemplo, Magnemite permanece Elétrico e Mr. Mime permanece Psíquico; não se aplicam os tipos Aço ou Fada adicionados posteriormente.

As espécies mantêm os tipos da Geração I. Os golpes usam a categoria física/especial da Geração III e movimentos, potência, precisão e efeitos ficam cadastrados no banco.

### Fórmula de dano e status

O cálculo é intencionalmente curto para ser explicado na apresentação:

`dano = potência × ataque ÷ (4 × defesa) × STAB × efetividade`

O servidor arredonda o resultado, aplica mínimo de 1 e limita cada golpe a metade do HP máximo do alvo; uma imunidade continua causando 0. STAB vale 1,5, fraqueza 2, resistência 0,5 e tipos duplos multiplicam os fatores. Golpes físicos usam Ataque/Defesa; especiais usam Ataque Especial/Defesa Especial.

Queimadura reduz pela metade o dano físico e, assim como veneno, causa 1/8 do HP máximo ao final do turno. Toxic aumenta esse dano em 1/8 por turno. Paralisia reduz Velocidade à metade e tem 25% de chance de impedir a ação; sono bloqueia 1 ou 2 ações; congelamento tem 20% de chance de terminar em cada ação; confusão dura 2 ações e tem 50% de chance de causar 1/8 do HP máximo ao próprio Pokémon e impedir a ação. Os status terminam com a batalha. Buffs e reduções de atributos têm limite de dois estágios em cada direção.

Recover e Synthesis recuperam metade do HP máximo uma vez por Pokémon por batalha. Protect também pode ser usado uma vez por Pokémon por batalha; o projeto não controla PP dos movimentos.

O estado dos turnos fica na sessão PHP. No fim, o servidor salva o HP dos Pokémon do jogador, o log, as vitórias e a recompensa em uma transação. Após uma vitória, uma tentativa de captura é sorteada e exibida em uma tela pós-batalha. A chance começa em 30%, aumenta 20 pontos percentuais após cada falha e para em 90%; vencer novamente tenta o mesmo Pokémon até o sucesso. A sequência avança apenas depois de uma captura bem-sucedida, e a chance reinicia para o próximo Pokémon. Um token único por batalha e o registro persistente da tentativa impedem duplicar a recompensa ou sortear de novo ao recarregar a tela. No sucesso, uma cópia entra na reserva com HP completo; o Pokémon do treinador permanece na equipe adversária. Não se salva dano no cadastro global das espécies. Também não há troca manual nem uso de itens durante os turnos.

### Movimentos por estágio de evolução

Cada Pokémon terá dois movimentos no estágio inicial, três no segundo estágio e quatro no estágio final. Para espécies sem evolução ou com menos estágios, a quantidade e a seleção serão decididas caso a caso. Os movimentos disponíveis em FireRed/LeafGreen servirão como referência, mas a seleção poderá ser ajustada para manter a jornada desafiadora e equilibrada.

Referências: [gráfico de tipos Gerações II–V e jogos aplicáveis](https://pokemondb.net/type/old), [prioridade de movimentos](https://pokemondb.net/move/quick-attack) e [cálculo de dano e STAB](https://www.serebii.net/games/damage.shtml).

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
3. Execute o **script completo** pela opção **Execute SQL Script** do DBeaver. Executar apenas a instrução atual não roda o arquivo inteiro. Este é o único script necessário para uma instalação nova: cria o banco `bancodedados_pokefish`, as tabelas (incluindo os campos de foto de perfil), os tipos, os Pokémon e evoluções cadastrados e as equipes dos oito ginásios de Kanto. Não há migração adicional para executar em um banco novo.
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
| 8 | 190 | 55 |

A primeira campanha completa rende 960 PokePila em recompensas de primeira vitória. O nível 7 mantém a recompensa aprovada de 170/50 para Blaine; o nível 8 concede 190/55 a Giovanni. A repetição de um nível não pode alterar a recompensa inédita de outro nível.

**Jornada de ginásios:** os níveis seguem Brock (Rocha), Misty (Cascata), Lt. Surge (Trovão), Erika (Arco-íris), Koga (Alma), Sabrina (Pântano), Blaine (Vulcão) e Giovanni (Terra). Os quatro primeiros líderes têm três Pokémon; de Koga em diante, cada líder tem quatro Pokémon da Geração I. As espécies adversárias são cadastradas separadamente do time inicial do jogador. As equipes começam com o membro mais fraco e terminam com o mais forte; insígnias e vitórias são registradas por usuário. A PokeAPI fornece os sprites dos Pokémon, mas não possui retratos de treinadores.

O botão **Jogar** abre o mapa dos oito ginásios. O jogador escolhe um nível desbloqueado e a batalha carrega a equipe correspondente. Inicia o primeiro Pokémon ativo da equipe que ainda tenha HP, respeitando a ordem das posições, contra o primeiro Pokémon do líder.

**Capturas dos líderes:** após cada vitória, o jogador tenta capturar o próximo Pokémon da ordem do líder, do mais fraco ao mais forte. A chance começa em 30%, sobe 20 pontos após cada falha e tem limite de 90%. Uma falha repete a tentativa contra o mesmo Pokémon na próxima vitória; só após o sucesso a sequência avança. A chance reinicia em 30% para cada novo Pokémon. O sorteio aparece em uma tela própria depois da batalha. No sucesso, uma cópia vai para a reserva e o Pokémon do líder continua disponível em sua equipe.

**Pokecenter:** restaura o HP de todos os Pokémon associados aos times do jogador, inclusive os desmaiados, até o máximo. Cobra 40 PokePila uma única vez por visita, somente se houver HP a recuperar. O HP individual deve ser persistido sem alterar o cadastro global do Pokémon.

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

**Ainda pendente:** eventos aleatórios; itens, preços e efeitos de evolução; loja funcional; e testar o equilíbrio da jornada com as capturas aleatórias e evoluções funcionando. A fórmula, os efeitos dos status, a lista de movimentos por espécie, a ordem de ação, as recompensas e as tentativas de captura pós-batalha estão implementados conforme as regras acima.

## Objetivos Acadêmicos

O desenvolvimento do PokeBagual tem como objetivo aplicar, de forma integrada, os conhecimentos adquiridos nas disciplinas envolvidas, incluindo:

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

## Apoio do agente personalizado Professor PokeBagual

Este projeto conta com o agente personalizado **Professor PokeBagual**, que auxilia desenvolvedores e demais pessoas interessadas em compreender o sistema, avaliar o que já está implementado e colaborar no desenvolvimento do que ainda falta. Ele deve orientar de forma didática, considerar as regras registradas em `AGENTS.md` e verificar o código existente antes de considerar uma funcionalidade concluída.

Para começar uma conversa, selecione o agente **Professor PokeBagual** e envie a mensagem abaixo. Ela ajudará o agente a entender o estado atual do projeto e a combinar com você um próximo passo de desenvolvimento.

### Mensagem para iniciar

```text
Olá, Professor PokeBagual! Quero compreender e ajudar a desenvolver este projeto.

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

**PokeBagual — Monte seu time e conquiste as insígnias de Kanto!**
