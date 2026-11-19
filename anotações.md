# Relatório de revisão do projeto

**Data:** 17/11/2026  
**Escopo:** verificação somente de leitura; nenhum código foi alterado durante a revisão.

## Resumo

O projeto possui funcionalidades já operacionais, mas ainda não está pronto como jogo completo. Foram identificados conflitos entre a documentação e as regras, uma rota da loja incompatível com o construtor do Controller e requisitos funcionais e acadêmicos pendentes.

## Conflitos e problemas prioritários

1. **Batalha — núcleo implementado; equilíbrio ainda em teste.** Os movimentos estão cadastrados por espécie; a aplicação resolve prioridade/Velocidade, efetividade, STAB, dano, status temporários, troca automática, HP, resultado, progresso e recompensas. A fórmula e os efeitos estão descritos no [README.md](./README.md) e na seção 19.5 do [AGENTS.md](./AGENTS.md). A tentativa/conclusão da captura ainda depende de decisão. Traits não são requisito obrigatório; as existentes não foram removidas.

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
| Meu time | Exibe ativos e reservas, reorganiza prioridades e troca um ativo pela reserva. A inclusão por captura depende da captura, ainda não implementada. |
| Pokecenter | Cura individual por jogador, cobra 40 Pokédollars quando necessário e usa transação no banco. |
| Ginásios | Oito líderes e suas equipes estão cadastrados. O mapa mostra níveis liberados/bloqueados conforme o progresso. |
| Batalha | Turnos, movimentos, efetividade, status, HP individual, troca automática, log, recompensa e progressão estão implementados. A tela não oferece troca manual nem itens em batalha. |
| Captura | Cada vitória aumenta a quantidade liberada; tentativa e inclusão da captura no time não estão implementadas. |
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
