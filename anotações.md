# Relatório de revisão do projeto

**Data da revisão inicial:** 17/11/2026
**Escopo:** este arquivo registra o diagnóstico inicial e as atualizações posteriores; os itens descritos como corrigidos refletem o estado atualizado do projeto.

## Resumo

O diagnóstico inicial identificou conflitos de documentação, uma incompatibilidade na rota da loja e requisitos acadêmicos pendentes. A incompatibilidade da rota e as operações da loja foram corrigidas depois; permanecem pendências de equilíbrio, eventos aleatórios e demonstração de alguns conceitos acadêmicos.

## Conflitos e problemas prioritários

1. **Batalha — núcleo implementado; equilíbrio ainda em teste.** Os movimentos estão cadastrados por espécie; a aplicação resolve prioridade/Velocidade, efetividade, STAB, dano, status temporários, troca automática, HP, resultado, progresso e recompensas. A finalização persiste os dados em transação e usa um token único para evitar duplicação de log, recompensa ou sorteio de captura. A regra atual é chance inicial de 30%, aumento de 20 pontos após falha e limite de 90%; uma espécie já possuída é pulada sem sorteio. Traits não são requisito obrigatório; as existentes não foram removidas.

2. **Loja — compras e uso de itens implementados.** O Model [Loja.php](./app/Models/Loja.php) valida o saldo e persiste débito, inventário e histórico de compra em transação. As compras do Bolicho também usam `fetch` com JSON; o Controller valida sessão e token CSRF e retorna saldo e quantidade atualizados para a tela.

3. **Listagem de Pokémon — corrigido nesta atualização.** A rota lê o catálogo pelo [PokemonController.php](./app/Controllers/PokemonController.php) e [Pokemon.php](./app/Models/Pokemon.php); [PokemonTipoFactory.php](./app/Models/PokemonTipoFactory.php) cria a subclasse de cada tipo principal. Tipos secundários continuam em `pokemon_tipos`, e a PokeAPI é usada no navegador para sprites. O catálogo local contém 41 espécies.

4. **Links diretos para Views — corrigido nesta atualização.** A navegação usa rotas e Controllers, e [app/.htaccess](./app/.htaccess) bloqueia requisições HTTP diretas à pasta interna `app`.

5. **Migração de foto de perfil — consolidada nesta atualização.** Os campos de foto já fazem parte do [schema.sql](./database/schema.sql), único script a executar para criar um banco novo. O arquivo de migração separado foi removido.

6. **Nome do projeto — corrigido nesta atualização.** O nome oficial é PokeBagual; a documentação e as telas foram padronizadas com essa identidade.

## Estado das funcionalidades

| Área | Situação observada |
|---|---|
| Cadastro e login | Implementados com hash de senha e sessão. Troca de perfis não encontrada; não há papéis de usuário no banco nem fluxo para alterná-los. |
| Escolha inicial | Implementada: seleção de três entre cinco Pokémon, com validação e persistência. |
| Meu time | Exibe ativos e reservas, reorganiza prioridades e troca um ativo pela reserva. Capturas bem-sucedidas entram na reserva. |
| Pokecenter | Cura individual por jogador, cobra 40 PokePila quando necessário e usa transação no banco. |
| Ginásios | Oito líderes e suas equipes estão cadastrados. O mapa mostra níveis liberados/bloqueados conforme o progresso. |
| Batalha | Turnos, movimentos, efetividade, status, HP individual, troca automática, log, recompensa e progressão estão implementados. A tela não oferece troca manual nem itens em batalha. |
| Captura | Tentativa aleatória após vitória, tela própria com resultado e chance crescente por falha. Uma falha mantém o alvo; espécies que o jogador já possui são puladas. Capturas bem-sucedidas entram na reserva, sem remover o Pokémon da equipe do líder. |
| Loja | Os 12 itens obrigatórios e o Item Evolutivo estão cadastrados. Compra, saldo e inventário são persistidos; itens podem ser usados conforme os fluxos implementados. |
| Evolução | O Item Evolutivo custa 300 PokePila e evolui um estágio direto cadastrado. A PokeAPI fornece atributos e tipos da nova espécie, e o HP individual é restaurado ao máximo. |
| Listagem de Pokémon | O catálogo consulta o banco e instancia subclasses para os nove tipos principais presentes nas espécies; tipos secundários vêm de `pokemon_tipos` e a PokeAPI fornece os sprites. |
| Comunicação assíncrona | Compras do Bolicho usam `fetch` com corpo e resposta JSON entre JavaScript e PHP. Saldo e inventário são atualizados sem recarregar a página; as imagens de itens e Pokémon continuam sendo consultadas na PokeAPI. |

## Requisitos acadêmicos

**Presentes ou parcialmente demonstrados:**

- MVC básico, autoload e consultas PDO.
- A classe Pokémon possui onze atributos privados e subclasses por tipo principal; o catálogo usa uma fábrica para instanciar as subclasses dos nove tipos principais presentes. Tipos secundários continuam nos dados de `pokemon_tipos`. Traits existem no código, mas não são requisito obrigatório.
- Há transações PDO no gerenciamento de time e no Pokecenter.
- O encerramento de cada batalha grava o resultado e os Pokémon do jogador envolvidos no log.
- Três Views SQL estão definidas no schema e uma exceção customizada trata saldo insuficiente na loja.

**Não encontrados ou ainda não demonstrados:**

- Sobrecarga de métodos.
- Uso funcional de polimorfismo no combate.

## Validações realizadas

- O lint passou nos arquivos PHP verificados, usando PHP 8.2.
- `git diff --check` passou.
- Banco local: **8 líderes**, equipes com os tamanhos esperados, **41 espécies**, **13 itens** e **3 Views**. Triggers são opcionais no enunciado oficial.
- Não foi encontrada suíte automatizada de testes.
- O schema completo foi executado em um banco temporário para validar a criação e a carga dos movimentos; foram cadastradas 41 espécies e 126 associações de movimentos no teste. Nenhum commit ou push foi realizado.

## Próximas prioridades recomendadas

1. Continuar testes de equilíbrio dos oito níveis com equipes iniciais, capturadas e evoluídas.
2. Testar no navegador os fluxos de compra assíncrona, incluindo sucesso, saldo insuficiente, sessão expirada e token inválido.
3. Demonstrar sobrecarga de métodos e polimorfismo conforme os recursos disponíveis nas linguagens.
4. Verificar os demais requisitos acadêmicos e preparar a apresentação.

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

## Captura aleatória pós-batalha — regra confirmada em 20/11/2026

- A chance é 30% na primeira tentativa, aumenta 20 pontos percentuais após cada falha e tem limite de 90%. O contador reinicia para cada Pokémon novo.
- Após cada vitória, o sistema tenta capturar o próximo Pokémon liberado que o jogador ainda não possui. Uma falha mantém o mesmo alvo; espécies que já fazem parte do time são puladas sem novo sorteio.
- A tela de captura mostra imediatamente o sorteio. A tentativa e o resultado são persistidos com a batalha para que uma atualização da página não sorteie novamente.
- No sucesso, uma cópia é adicionada à reserva com HP completo; a equipe cadastrada do treinador não é alterada. Se o jogador já possuir todos os Pokémon liberados daquele treinador, a tela informa que todos já foram capturados e oferece a opção de continuar.
- A implementação foi validada em banco isolado: primeira chance de 30%, limite de 90% após falhas, resultado idempotente ao repetir a finalização, avanço para o segundo Pokémon quando o primeiro já é possuído, captura na reserva com HP completo e tela “Todos os Pokémon já foram capturados” com botão “Continuar”. O banco oficial não foi alterado.
