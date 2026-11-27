# Relatório de revisão do projeto

**Data da revisão inicial:** 17/11/2026
**Escopo:** este arquivo registra o diagnóstico inicial e as atualizações posteriores; os itens descritos como corrigidos refletem o estado atualizado do projeto.

## Resumo

O diagnóstico inicial identificou conflitos de documentação, uma incompatibilidade na rota da loja e requisitos acadêmicos pendentes. A rota e as operações da loja foram corrigidas. Os eventos aleatórios estão implementados no código, mas a lista e sua distribuição ainda aguardam confirmação dos desenvolvedores. Permanecem pendências de equilíbrio e de demonstração de alguns conceitos acadêmicos.

## Conflitos e problemas prioritários

1. **Batalha — núcleo implementado; equilíbrio ainda em teste.** Os movimentos estão cadastrados por espécie; a aplicação resolve prioridade/Velocidade, efetividade, STAB, dano, status temporários, troca automática, HP, resultado, progresso e recompensas. O jogador também pode trocar Pokémon, usar itens compatíveis ou fugir: troca e item consomem um turno; fuga encerra como derrota. A finalização persiste os dados em transação e usa um token único para evitar duplicação de log, recompensa ou sorteio de captura. A chance de captura atual é fixa em 50%; uma espécie já possuída é pulada sem sorteio. Traits não são requisito obrigatório.

2. **Loja — compras e uso de itens implementados.** O Model [Loja.php](./app/Models/Loja.php) valida o saldo e persiste débito, inventário e histórico de compra em transação. As compras do Bolicho também usam `fetch` com JSON; o Controller valida sessão e token CSRF e retorna saldo e quantidade atualizados para a tela.

3. **Listagem de Pokémon — corrigido nesta atualização.** A rota lê o catálogo pelo [PokemonController.php](./app/Controllers/PokemonController.php) e [Pokemon.php](./app/Models/Pokemon.php); [PokemonTipoFactory.php](./app/Models/PokemonTipoFactory.php) cria a subclasse de cada tipo principal. Tipos secundários continuam em `pokemon_tipos`, e a PokeAPI é usada no navegador para sprites. O catálogo local contém 41 espécies.

4. **Links diretos para Views — corrigido nesta atualização.** A navegação usa rotas e Controllers, e [app/.htaccess](./app/.htaccess) bloqueia requisições HTTP diretas à pasta interna `app`.

5. **Foto de perfil — armazenamento em arquivo.** Novas imagens são salvas em `public/uploads/perfil`; `foto_caminho` guarda o caminho relativo. `database/migrar_foto_perfil.sql` adiciona a coluna em bancos existentes sem apagar os antigos BLOBs.

6. **Nome do projeto — corrigido nesta atualização.** O nome oficial é PokeBagual; a documentação e as telas foram padronizadas com essa identidade.

## Estado das funcionalidades

| Área | Situação observada |
|---|---|
| Cadastro e login | Implementados com hash de senha e sessão. Troca de perfis não encontrada; não há papéis de usuário no banco nem fluxo para alterná-los. |
| Escolha inicial | Implementada: seleção de três entre cinco Pokémon, com validação e persistência. |
| Meu time | Exibe ativos e reservas, reorganiza prioridades e troca um ativo pela reserva. Capturas bem-sucedidas entram na reserva. |
| Pokecenter | Cura individual por jogador, cobra 40 PokePila quando necessário e usa transação no banco. |
| Ginásios | Oito líderes e suas equipes estão cadastrados. O mapa mostra níveis liberados/bloqueados conforme o progresso. |
| Batalha | Turnos, movimentos, efetividade, status, HP individual, troca automática e manual, uso de itens, fuga, log, recompensa e progressão estão implementados. Trocas e itens consomem turno; fugir encerra como derrota. |
| Captura | Tentativa aleatória após vitória, tela própria com resultado e chance fixa de 50%. Uma falha mantém o alvo; espécies que o jogador já possui são puladas. Capturas bem-sucedidas entram na reserva, sem remover o Pokémon da equipe do líder. |
| Loja | Os 12 itens obrigatórios e o Item Evolutivo estão cadastrados. Compra, saldo e inventário são persistidos; itens podem ser usados conforme os fluxos implementados. |
| Evolução | O Item Evolutivo custa 300 PokePila e evolui um estágio direto cadastrado. A PokeAPI fornece atributos e tipos da nova espécie, e o HP individual é restaurado ao máximo. |
| Listagem de Pokémon | O catálogo consulta o banco e instancia subclasses para os nove tipos principais presentes nas espécies; tipos secundários vêm de `pokemon_tipos` e a PokeAPI fornece os sprites. |
| Comunicação assíncrona | Compras do Bolicho usam `fetch` com corpo e resposta JSON entre JavaScript e PHP. Saldo e inventário são atualizados sem recarregar a página; as imagens de itens e Pokémon continuam sendo consultadas na PokeAPI. |

## Requisitos acadêmicos

**Presentes ou parcialmente demonstrados:**

- MVC básico, autoload e consultas PDO.
- A classe Pokémon possui onze atributos privados e subclasses por tipo principal; o catálogo usa uma fábrica para instanciar as subclasses dos nove tipos principais presentes. Tipos secundários continuam nos dados de `pokemon_tipos`. Traits existem no código, mas não são requisito obrigatório.
- Há transações PDO em operações com várias etapas, incluindo batalha, compra, eventos do mapa, evolução, gerenciamento de time e Pokecenter.
- O encerramento de cada batalha grava o resultado e os Pokémon do jogador envolvidos no log.
- Três Views SQL estão definidas no schema e uma exceção customizada trata saldo insuficiente na loja.
- `Pokemon` demonstra encapsulamento; as subclasses de tipo e `getTipo()` demonstram herança e polimorfismo no catálogo. A engine de batalha trabalha com arrays, então não há polimorfismo de combate demonstrado.
- Controllers e Models se relacionam por associação/uso. Composição e agregação entre objetos de domínio não foram identificadas de forma clara.
- Além da exceção personalizada, o código lança e trata exceções nativas como `InvalidArgumentException`, `RuntimeException`, `UnexpectedValueException` e `DomainException`. Confirmar se a avaliação conta tipos nativos ou exige três classes personalizadas.

**Não encontrados ou ainda não demonstrados:**

- Demonstração explícita de sobrecarga de métodos.
- Demonstração clara de composição e agregação entre objetos do domínio.
- Fluxo dedicado para troca de perfil ou alternância de papéis de usuário.

## Ajustes realizados após a auditoria

- O dano mínimo agora é 1; imunidades continuam causando zero e o limite de metade do HP máximo permanece. Há um teste executável para esses três casos.
- A documentação da batalha foi alinhada ao código: troca, itens e fuga estão disponíveis, e seus efeitos no turno estão descritos.
- O formulário de cadastro permite alcançar o seletor de foto pelo teclado; os campos do login mantêm foco visível e texto maior.
- A música só começa após o jogador acionar o botão. Alterar o volume não inicia a reprodução.
- Erros de conexão com o banco são registrados no log do servidor; a página recebe uma mensagem genérica.
- Foram removidos arquivos de depuração expostos e métodos/utilitários sem uso confirmado. A PokeAPI reutiliza respostas repetidas na mesma requisição PHP.

## Validações realizadas

- O lint passou nos arquivos PHP verificados, usando PHP 8.2.
- `git diff --check` passou.
- Banco local: **8 líderes**, equipes com os tamanhos esperados, **41 espécies**, **13 itens** e **3 Views**. Triggers são opcionais no enunciado oficial.
- Esta atualização acrescenta `tests/BatalhaEngineTest.php`, um teste executável sem dependências extras para o dano mínimo, o limite de metade do HP e imunidades.
- O schema completo foi executado em um banco temporário para validar a criação e a carga dos movimentos; foram cadastradas 41 espécies e 126 associações de movimentos no teste. Nenhum commit ou push foi realizado.

## Próximas prioridades recomendadas

1. Continuar testes de equilíbrio dos oito níveis com equipes iniciais, capturadas e evoluídas.
2. Testar no navegador os fluxos de compra assíncrona, incluindo sucesso, saldo insuficiente, sessão expirada e token inválido.
3. Confirmar com a professora como demonstrar sobrecarga de métodos em PHP e se a exigência pede três classes de exceção personalizadas.
4. Preparar exemplos compreensíveis de composição e agregação, caso sejam cobrados; explicar que o polimorfismo atual aparece na identificação dos tipos no catálogo, não no engine de batalha.
5. Confirmar a regra de troca de perfis e as regras de distribuição dos eventos aleatórios antes de apresentá-las como decisões aprovadas.
6. Verificar os demais requisitos acadêmicos e preparar a apresentação.

## Testes de batalha — 20/11/2026

### Fluxo no navegador e persistência

- Em uma cópia temporária do projeto e do banco, foi feito login, aberto o mapa, iniciado Brock e concluída a batalha em 10 turnos.
- O mapa permitiu clicar no ponto de Brock após ajustar a camada do botão “Voltar”; na vitória, o sprite do Pokémon do jogador continuou visível.
- O teste gravou vitória, recompensa de 50 PokePila, progresso e HP final: Charmander 0, Bulbasaur 0 e Squirtle 12. Reabrir a tela manteve apenas uma finalização e o saldo em 550.
- A simulação também cobriu repetição idempotente, vitória repetida, derrota e rollback em caso de Pokémon inválido. Todos passaram em banco isolado.

### Simulação exploratória de equilíbrio

- Foram simuladas 240 batalhas: as dez combinações possíveis de três entre os cinco Pokémon iniciais, três partidas por combinação em cada nível. Cada partida começou com HP completo e o jogador escolheu o movimento de maior dano direto calculado.
- Resultados por nível: nível 1, 11/30 vitórias; nível 2, 0/30; nível 3, 0/30; nível 4, 7/30; níveis 5 a 8, 0/30.
- Como limite superior exploratório, Venusaur, Blastoise e Charizard foram testados dez vezes nos níveis 5 a 8: Koga 0/10, Sabrina 0/10, Blaine 2/10 e Giovanni 10/10.
- Esses números são anteriores à implementação da captura aleatória pós-batalha e não são um balanceamento definitivo: o jogador simulado usa uma estratégia simples, cada luta começa curada e a evolução pela loja ainda não está implementada. A jornada precisa ser testada novamente com a progressão de capturas ativa. Não foram alterados adversários ou regras sem decisão dos desenvolvedores.
- Os testes usaram banco temporário, removido ao final; os dados de jogadores reais não foram usados nem alterados durante a simulação.

## Captura aleatória pós-batalha — regra atual

- A regra atualmente aprovada é chance fixa de 50% por tentativa. Uma falha não altera essa chance.
- Após cada vitória, o sistema tenta capturar o próximo Pokémon liberado que o jogador ainda não possui. Uma falha mantém o mesmo alvo; espécies que já fazem parte do time são puladas sem novo sorteio.
- A tela de captura mostra imediatamente o sorteio. A tentativa e o resultado são persistidos com a batalha para que uma atualização da página não sorteie novamente.
- No sucesso, uma cópia é adicionada à reserva com HP completo; a equipe cadastrada do treinador não é alterada. Se o jogador já possuir todos os Pokémon liberados daquele treinador, a tela informa que todos já foram capturados e oferece a opção de continuar.

### Histórico de testes da regra anterior

Os registros abaixo foram feitos quando a regra ainda era progressiva. Eles documentam uma versão antiga e não descrevem as chances atuais:

- Na versão antiga, a chance começava em 30%, aumentava 20 pontos percentuais após cada falha e tinha limite de 90%.
- Os testes antigos validaram idempotência, avanço após espécies já possuídas e inclusão do Pokémon na reserva com HP completo. Os testes de probabilidade desse período não servem para validar a regra fixa atual.

## Atualização da auditoria e da documentação — 26/11/2026

Esta atualização compara o código atual, o schema e o banco local. Ela não altera os dados do banco nem as regras de negócio.

### Estado atual do código e dos requisitos

- Os fluxos de login/cadastro, seleção e gerenciamento do time, mapa, batalha por turnos, captura pós-vitória, recompensas, loja, uso de itens, evolução e Pokecenter estão implementados.
- As compras do Bolicho usam `fetch` e JSON; o Controller valida a sessão, o CSRF e os dados recebidos, e o Model registra débito, inventário e compra em transação. A PokeAPI é consultada para sprites e para obter dados da espécie na evolução.
- O engine de batalha permite atacar, trocar, usar itens compatíveis e fugir. Trocas e itens consomem um turno; a fuga é registrada como derrota. A fórmula atual usa dano mínimo 1, imunidade 0 e limite de metade do HP máximo. Golpes acima de 40 de potência sofrem o ajuste de precisão documentado no README e em `AGENTS.md`.
- A fábrica de tipos cobre os nove tipos principais presentes nas espécies do catálogo local. Tipos secundários permanecem nos dados de `pokemon_tipos`. O polimorfismo de `getTipo()` é usado na listagem; não está demonstrado no engine de batalha.
- As regras da distribuição de eventos aleatórios ainda precisam de confirmação dos desenvolvedores. `EventoMapa` implementa atualmente cinco pontos e a distribuição descrita no README, mas isso não deve ser apresentado como regra aprovada até a decisão de Henrique e Isadora.
- Não foi localizado um fluxo dedicado para alternar perfis/usuários. Sair e entrar com outra conta é possível, mas a exigência pode significar outra coisa.
- Sobrecarga explícita de métodos, composição e agregação entre objetos de domínio não foram identificadas de forma clara. A associação entre Controllers e Models é visível.

### Diferença encontrada no banco local

Na consulta somente de leitura feita em 26/11/2026, o banco local tinha 22 tabelas, enquanto `database/schema.sql` define 23. Faltava `batalha_treinadores`, consultada e gravada na finalização de batalha em `app/Models/Batalha.php`. A tabela `usuarios` também não tinha `foto_caminho`, usada no cadastro e no perfil. Os índices únicos de nomes definidos no schema não apareciam no banco.

O schema atual cria a tabela ausente em uma execução completa; a coluna e os índices têm migrações próprias. Antes de validar o sistema com esse banco, faça backup e siga as instruções do README. A consulta não alterou o banco; portanto, os fluxos de batalha, cadastro e perfil podem falhar até a estrutura instalada ser atualizada.

### Validação desta atualização

- O lint passou nos 56 arquivos PHP verificados.
- `tests/BatalhaEngineTest.php` passou nos casos de dano mínimo, limite de dano e imunidade.
- O banco local contém os 13 itens obrigatórios e de evolução com os preços cadastrados, além das três Views; a presença dos itens não elimina a pendência de estrutura descrita acima.
- Nenhum commit ou push foi realizado.
