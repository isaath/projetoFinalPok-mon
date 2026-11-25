# Relatório de revisão do projeto

**Data:** 17/11/2026  
**Escopo:** verificação somente de leitura; nenhum código foi alterado durante a revisão.

## Resumo

O projeto possui funcionalidades já operacionais, mas ainda não está pronto como jogo completo. Foram identificados conflitos entre a documentação e as regras, uma rota da loja incompatível com o construtor do Controller e requisitos funcionais e acadêmicos pendentes.

## Conflitos e problemas prioritários

1. **Batalha — núcleo implementado; equilíbrio ainda em teste.** Os movimentos estão cadastrados por espécie; a aplicação resolve prioridade/Velocidade, efetividade, STAB, dano, status temporários, troca automática, HP, resultado, progresso e recompensas. A finalização persiste os dados em transação e usa um token único para evitar duplicação de log, recompensa ou sorteio de captura. A regra atual é chance inicial de 30%, aumento de 20 pontos após falha e limite de 90%; uma espécie já possuída é pulada sem sorteio. Traits não são requisito obrigatório; as existentes não foram removidas.

2. **Loja ainda sem operações.** O Controller agora recebe PDO e a rota mostra a tela do Bolicho para usuários autenticados. A tela [bolicho.php](./app/Views/bolicho/bolicho.php) continua sendo apenas visual; compras e uso de itens ficam para a implementação da loja.

3. **Listagem de Pokémon — corrigido nesta atualização.** A rota principal lê o catálogo do banco pelo [PokemonController.php](./app/Controllers/PokemonController.php) e [Pokemon.php](./app/Models/Pokemon.php); a PokeAPI é usada no navegador apenas para sprites. No banco local havia 41 espécies na última verificação.

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
| Loja | Os 12 itens obrigatórios estão cadastrados, mas a tela, as compras, o inventário e o uso dos itens não estão implementados. Itens de evolução e suas regras dependem de decisão. |
| Evolução | A PokeAPI fornece dados de linhas evolutivas para exibição, mas não há evolução do Pokémon do jogador por item. |
| Listagem de Pokémon | O catálogo consulta as espécies no banco; a PokeAPI fornece os sprites. |
| Comunicação assíncrona | Há `fetch` JavaScript para imagens diretamente da PokeAPI e cURL no PHP. Não foi encontrada comunicação JSON assíncrona entre JavaScript e PHP. |

## Requisitos acadêmicos

**Presentes ou parcialmente demonstrados:**

- MVC básico, autoload e consultas PDO.
- A classe Pokémon possui dez atributos privados e há herança. Traits existem no código, mas não são mais requisito obrigatório.
- Há transações PDO no gerenciamento de time e no Pokecenter.
- O encerramento de cada batalha grava o resultado e os Pokémon do jogador envolvidos no log.

**Não encontrados ou ainda não demonstrados:**

- Triggers e as três Views do banco: não há definições no schema nem no banco local.
- Exceção personalizada: há exceções padrão do PHP, mas nenhuma classe customizada.
- Sobrecarga de métodos.
- Uso funcional de polimorfismo no combate.
- Comunicação JSON entre JavaScript e PHP.

## Validações realizadas

- O lint passou nos arquivos PHP verificados, usando PHP 8.2.
- `git diff --check` passou.
- Banco local: **8 líderes**, equipes com os tamanhos esperados, **41 espécies**, **12 itens**, **zero Views** e **zero triggers**.
- Não foi encontrada suíte automatizada de testes.
- O schema completo foi executado em um banco temporário para validar a criação e a carga dos movimentos; foram cadastradas 41 espécies e 126 associações de movimentos no teste. Nenhum commit ou push foi realizado.

## Próximas prioridades recomendadas

1. Implementar compras e uso de itens na loja.
2. Definir e implementar tentativa/conclusão de capturas; validar a progressão de equipe.
3. Continuar testes de equilíbrio dos oito níveis com equipes iniciais, capturadas e evoluídas.
4. Implementar compra/uso de itens de evolução e respectivos efeitos.
5. Planejar triggers, Views, exceção customizada e demonstração de sobrecarga.
6. Implementar comunicação JSON entre JavaScript e PHP em uma funcionalidade apropriada.

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
