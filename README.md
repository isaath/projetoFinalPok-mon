# PokeBagual

## Sobre o Projeto

O **PokeBagual** é um jogo web acadêmico inspirado no universo Pokémon, com uma jornada ambientada na região de Kanto e identidade visual ligada ao Rio Grande do Sul.

O projeto está sendo desenvolvido como parte de um trabalho acadêmico interdisciplinar, integrando os conhecimentos das disciplinas de Programação Orientada a Objetos I, Linguagens para a Web II e Banco de Dados II.

## Funcionalidades

* **Gerenciamento de usuários:** cadastro, login, saída protegida por CSRF e gerenciamento de perfil.
* **Perfil do jogador:** exibição das insígnias conquistadas por aquele usuário e do título “Mestre Pokémon” ao vencer os oito ginásios.
* **Montagem de equipes:** seleção e organização dos Pokémon para as batalhas.
* **Sistema de batalhas:** turnos, movimentos, dano, condições temporárias, troca automática e manual, uso de itens, fuga, HP persistente, progresso, recompensas e registro. Após uma vitória há uma tentativa de captura com chance fixa de 50%; espécies já possuídas são puladas, e uma captura bem-sucedida adiciona o Pokémon à reserva sem removê-lo da equipe do líder.
* **Tipos de Pokémon:** cada espécie é instanciada como uma subclasse de `Pokemon` correspondente ao tipo principal; tipos secundários continuam associados pelos dados de `pokemon_tipos`. As regras aprovadas usam a efetividade de tipos da Geração III como referência, mas mantêm as tipagens das espécies da Geração I.
* **Loja:** compra de itens para recuperação, tratamento de condições e evolução dos Pokémon.
* **Compras assíncronas:** o Bolicho envia as compras do navegador ao PHP em JSON e atualiza saldo e inventário sem recarregar a página; a validação de sessão, CSRF, preço e saldo continua no servidor.
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

Para deixar os golpes fortes mais arriscados sem torná-los imprevisíveis, ataques com dano acima de 40 de potência perdem 0,2 ponto percentual de precisão por ponto excedente, até 12 pontos. A penalidade não reduz a precisão-base abaixo de 70%; a precisão cadastrada e os estágios de precisão continuam valendo. Movimentos de status não recebem essa penalidade. O servidor aplica a regra aos dois lados; a chance final não é exibida nos botões de ataque.

### Fórmula de dano e status

O cálculo é intencionalmente curto para ser explicado na apresentação:

`dano = potência × ataque ÷ (4 × defesa) × STAB × efetividade`

O servidor arredonda o resultado, aplica mínimo de 1 e limita cada golpe a metade do HP máximo do alvo; uma imunidade continua causando 0. STAB vale 1,5, fraqueza 2, resistência 0,5 e tipos duplos multiplicam os fatores. Golpes físicos usam Ataque/Defesa; especiais usam Ataque Especial/Defesa Especial.

Queimadura reduz pela metade o dano físico e, assim como veneno, causa 1/8 do HP máximo ao final do turno. Toxic aumenta esse dano em 1/8 por turno. Paralisia reduz Velocidade à metade e tem 25% de chance de impedir a ação; sono bloqueia 1 ou 2 ações; congelamento tem 20% de chance de terminar em cada ação; confusão dura 2 ações e tem 50% de chance de causar 1/8 do HP máximo ao próprio Pokémon e impedir a ação. Os status terminam com a batalha. Buffs e reduções de atributos têm limite de dois estágios em cada direção.

Recover e Synthesis recuperam metade do HP máximo uma vez por Pokémon por batalha. Protect também pode ser usado uma vez por Pokémon por batalha; o projeto não controla PP dos movimentos.

O estado dos turnos fica na sessão PHP. O jogador pode escolher um movimento, trocar um Pokémon, usar um item compatível ou fugir. Trocas e itens consomem um turno e permitem a resposta do adversário; fugir encerra a batalha como derrota, sem recompensa nem tentativa de captura. No fim, o servidor salva o HP dos Pokémon do jogador e o resultado; vitórias, progresso e recompensas são gravados em transação. Após uma vitória, uma tentativa de captura é sorteada e exibida em uma tela pós-batalha, sempre com chance fixa de 50%, independentemente do número de tentativas. Uma falha permite tentar o mesmo Pokémon após vencer novamente o líder; a sequência avança após a captura bem-sucedida. Um token único por batalha e o registro persistente da tentativa impedem duplicar a recompensa ou sortear de novo ao recarregar a tela. No sucesso, uma cópia entra na reserva com HP completo; o Pokémon do treinador permanece na equipe adversária. Não se salva dano no cadastro global das espécies.

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

## Restrições técnicas definidas pelo professor

É estritamente proibido utilizar `mysql_connect`, `require_once`, `include` ou `include_once`. A conexão com o banco deve continuar usando PDO. Para carregar classes, use o autoload existente; para arquivos PHP obrigatórios que não sejam carregados pelo autoload, use `require` simples e controle para que não sejam carregados repetidamente.

## Arquitetura do Projeto

O projeto utiliza o padrão arquitetural **MVC (Model-View-Controller)**, com o objetivo de organizar o código e separar as responsabilidades da aplicação.

* **Model:** representa os dados e as regras de negócio.
* **View:** responsável pela apresentação das informações ao usuário.
* **Controller:** recebe as requisições, coordena as ações e conecta os modelos às visualizações.

O código demonstra encapsulamento nos atributos privados de `Pokemon`, herança nas subclasses dos tipos principais e polimorfismo no método `getTipo()` usado na listagem do catálogo. Controllers também se associam a Models para coordenar as operações. A engine de batalha trabalha principalmente com arrays; por isso, composição e agregação entre objetos de domínio ainda não estão demonstradas com clareza e não devem ser apresentadas como requisitos concluídos.

## Banco de Dados

O banco armazena usuários, Pokémon, equipes, itens e batalhas. Para criar as tabelas sem editar arquivos JSON:

1. Inicie o **MySQL** no XAMPP.
2. Abra `database/schema.sql` no DBeaver e conecte-se ao servidor MySQL local.
3. Execute o **script completo** pela opção **Execute SQL Script** do DBeaver. Executar apenas a instrução atual não roda o arquivo inteiro. Este é o único script necessário para uma instalação nova: cria o banco `bancodedados_pokefish`, as tabelas (incluindo o caminho da foto de perfil), os tipos, os Pokémon e evoluções cadastrados e as equipes dos oito ginásios de Kanto.
4. A conexão da aplicação está configurada em `config/config.php` para `localhost`, banco `bancodedados_pokefish`, usuário `root` e senha vazia. Ajuste esses dados caso sua instalação use outras credenciais.

Em bancos já existentes, faça backup e execute o `database/schema.sql` completo para criar tabelas e Views que ainda não existam. `CREATE TABLE IF NOT EXISTS` não altera a estrutura de tabelas já criadas. Antes de testar a aplicação, confirme também que o banco contém `batalha_treinadores`, usada no registro das batalhas, e a coluna `foto_caminho`, usada no cadastro e no perfil. Se o banco ainda não tiver `foto_caminho`, execute também `database/migrar_foto_perfil.sql`; para adicionar as restrições de nomes únicos em um banco anterior a esta versão, confira primeiro as duplicatas e execute uma vez `database/migrar_chaves_unicas.sql`. Se as consultas de duplicatas retornarem linhas, resolva-as antes da migração. Não execute essa migração em uma instalação criada pelo schema atual, pois os índices já estarão presentes.

A migração da foto preserva colunas antigas e fotos BLOB existentes; novas imagens são gravadas em arquivo e a aplicação usa o caminho salvo em `foto_caminho`. Fotos antigas continuam sendo exibidas como alternativa.

O `bd.erd` é uma versão antiga e não representa todas as tabelas atuais. Consulte `database/modelo_relacional.mmd` para o diagrama relacional atualizado e use `database/schema.sql` como fonte executável do banco. Os nomes de tabelas, colunas e tipos cadastrados no script não usam acentos.

### Decisões de jogo aprovadas

As regras abaixo foram decididas por Henrique e Isadora. Mantenha código e documentação alinhados com elas; não mude esses valores sem consultar os desenvolvedores. O documento de referência para regras do agente é `AGENTS.md`, seções 19.1, 19.4, 19.5 (regras de batalha) e 19.6. A distribuição dos eventos de exploração descrita separadamente ainda aguarda confirmação.

**Escolha do time inicial:** no primeiro login, o jogador escolhe três entre as cinco opções aprovadas, definidas após comparar os atributos base de nove candidatos:

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

**Capturas dos líderes:** após cada vitória, o jogador tenta capturar o próximo Pokémon da ordem do líder, do mais fraco ao mais forte. Cada tentativa tem chance fixa de 50%. Uma falha repete a tentativa contra o mesmo Pokémon na próxima vitória; só após o sucesso a sequência avança. O sorteio aparece em uma tela própria depois da batalha. No sucesso, uma cópia vai para a reserva e o Pokémon do líder continua disponível em sua equipe.

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
| Item Evolutivo | 300 |

O Item Evolutivo é o item mais caro da loja. Ele evolui o Pokémon em um estágio direto cadastrado no banco; atualmente há 15 relações possíveis entre as espécies do catálogo. Ao usar o item, o sistema consulta os atributos e tipos da espécie evoluída na PokeAPI, atualiza os dados dessa espécie e restaura o HP individual do Pokémon ao novo máximo. A operação consome uma unidade do item e é persistida em transação. Se o time já possuir a espécie de destino, a evolução é recusada sem consumir o item.

Esses itens são cadastrados por `database/schema.sql`.

### Eventos de exploração do mapa — implementação atual

O código atual implementa cinco pontos `?`, cada um utilizável uma vez por jogador e liberado após vencer um líder específico: Misty, Lt. Surge, Erika, Koga e Sabrina, respectivamente. O servidor valida o desbloqueio. O código sorteia um Pokémon ainda não possuído com 10% de chance e cada um dos outros seis resultados com 15%: item comum, ganhar 15 PokePila, perder até 15 PokePila, ataque selvagem, cura parcial ou local vazio. Itens encontrados são consumíveis comuns, em quantidade de 1 ou 2; o Item Evolutivo não é sorteado. O ataque reduz 10% do HP máximo sem desmaiar a equipe; a cura recupera 15% dos Pokémon vivos. Um Pokémon encontrado entra na reserva com HP completo.

**Atenção:** a lista, as probabilidades e a distribuição desses resultados ainda aguardam confirmação dos desenvolvedores. Os números acima descrevem o código atual, não uma regra de jogo aprovada; não os altere nem os trate como definitivos sem essa confirmação.

O evento e seu efeito são gravados juntos em uma transação. Assim, recarregar ou reenviar a solicitação não sorteia outro resultado nem repete a recompensa. Para bancos já instalados, aplique o DDL de `mapa_eventos` descrito acima. Ainda é necessário testar o equilíbrio geral da jornada com capturas e evoluções.

## Requisitos acadêmicos — situação verificada

O projeto usa PHP orientado a objetos, MVC, PDO, prepared statements, autoload, SQL relacional, Fetch/JSON e a PokeAPI. O JavaScript orientado a objetos é opcional no enunciado; o requisito de orientação a objetos se aplica obrigatoriamente ao PHP.

| Requisito | Situação observada no código |
|---|---|
| Login, sessão e proteção de rotas | Implementados. Cadastro e autenticação usam hash de senha; o logout é por POST e valida CSRF. |
| Troca de perfis/usuários | Parcial. É possível sair e entrar com outra conta, mas não foi encontrado um fluxo dedicado de troca de perfil ou papéis de usuário. |
| Team Builder, batalha, loja e Pokecenter | Implementados nos fluxos descritos acima; a validação completa depende de executar os testes com o banco atualizado. |
| Encapsulamento, herança e polimorfismo | Demonstrados em `Pokemon` e nas subclasses de tipos. O polimorfismo de tipo aparece no catálogo; a engine de batalha usa arrays, não uma hierarquia polimórfica de Pokémon. |
| Associação, agregação e composição | Associação entre Controllers e Models é visível. Composição e agregação entre objetos do domínio ainda não estão demonstradas de modo claro. |
| Exceções | Há várias exceções nativas lançadas e tratadas, além de `SaldoInsuficienteException`. Se a avaliação exigir três classes personalizadas, isso ainda precisa ser confirmado e atendido. |
| Sobrecarga de métodos | Não foi identificada uma demonstração explícita no código. A necessidade consta nas instruções acadêmicas do projeto; PHP não sobrecarrega métodos por assinatura como Java. |
| Banco de dados | O schema define tabelas, chaves, relações e três Views. As operações de várias etapas usam transações PDO; triggers são opcionais no enunciado. |
| Testes | `tests/BatalhaEngineTest.php` cobre dano mínimo, limite de dano e imunidade. Isso não substitui testes completos de cadastro, batalha, compras e evolução. |

O diagrama atualizado em `database/modelo_relacional.mmd` descreve o modelo relacional; `bd.erd` é antigo. O enunciado fornecido não exige um DER conceitual, mas o diagrama antigo não deve ser apresentado como representação atual do banco.

## Desenvolvedores

* Henrique Miranda da Silva
* Isadora Amaral

**Instituição:** IFRS — Campus Canoas

## Status do Projeto

O código contém os fluxos centrais de jornada, batalhas, capturas, loja, evolução, equipe e exploração. Ainda há requisitos acadêmicos a demonstrar — especialmente troca de perfis, composição/agregação e sobrecarga — e o equilíbrio da campanha precisa de testes. Em uma revisão do banco local, foram encontradas diferenças em relação ao schema atual; siga as instruções de migração acima antes de validar o jogo nesse banco. A existência de código ou de uma tabela, isoladamente, não comprova que todo o fluxo está testado.

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
