# Relatório de revisão do projeto

**Data:** 17/11/2026  
**Escopo:** verificação somente de leitura; nenhum código foi alterado durante a revisão.

## Resumo

O projeto possui funcionalidades já operacionais, mas ainda não está pronto como jogo completo. Foram identificados conflitos entre a documentação e as regras, uma rota da loja incompatível com o construtor do Controller e requisitos funcionais e acadêmicos pendentes.

## Conflitos e problemas prioritários

1. **Regras de batalha divergentes — resolvido na atualização de 17/11/2026.** A ordem das ações e a efetividade dos tipos foram padronizadas na seção “Regras de batalha aprovadas” do [README.md](./README.md) e na seção 19.5 do [AGENTS.md](./AGENTS.md), após consulta a referências sobre FireRed/LeafGreen. A prioridade ordena as ações; a Velocidade desempata ações de mesma prioridade; a efetividade de tipos altera o dano, não a ordem. Os Pokémon mantêm suas tipagens da Geração I, conforme decisão dos desenvolvedores. A fórmula completa de dano e o encerramento do combate continuam pendentes. Traits não são mais requisito obrigatório, conforme decisão dos desenvolvedores; as existentes não foram removidas nesta atualização documental.

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
| Ginásios | Oito líderes e suas equipes estão cadastrados. Há prévia por nível, mas não há mapa nem bloqueio real de acesso pelo progresso. |
| Batalha | Não implementada como combate: a tela mostra sprites e dados, mas não executa turnos, dano, atualização de HP, registro de resultado, recompensa ou progressão. |
| Captura | A ordem de liberação está no banco, mas não há tentativa nem inclusão da captura no time. |
| Loja | Os 12 itens obrigatórios estão cadastrados, mas a tela, as compras, o inventário e o uso dos itens não estão implementados. Itens de evolução e suas regras dependem de decisão. |
| Evolução | A PokeAPI fornece dados de linhas evolutivas para exibição, mas não há evolução do Pokémon do jogador por item. |
| Listagem de Pokémon | A tela consulta uma lista fixa da PokeAPI, não as 41 espécies do banco. |
| Comunicação assíncrona | Há `fetch` JavaScript para imagens diretamente da PokeAPI e cURL no PHP. Não foi encontrada comunicação JSON assíncrona entre JavaScript e PHP. |

## Requisitos acadêmicos

**Presentes ou parcialmente demonstrados:**

- MVC básico, autoload e consultas PDO.
- A classe Pokémon possui dez atributos privados e há herança. Traits existem no código, mas não são mais requisito obrigatório.
- Há transações PDO no gerenciamento de time e no Pokecenter.
- Existe tabela para logs de batalha, mas a aplicação ainda não a preenche.

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
- Na ocasião da revisão, havia seis arquivos modificados localmente; nenhum commit ou push foi realizado.

## Próximas prioridades recomendadas

1. Corrigir a instanciação e implementar as operações da loja.
2. Unificar a especificação das batalhas e depois implementar combate, progresso, recompensas e logs.
3. Implementar o mapa com bloqueio de níveis pelo progresso.
4. Conectar a listagem de Pokémon ao banco e implementar a comunicação JSON entre JavaScript e PHP.
5. Planejar triggers, Views, exceção customizada e demonstração de sobrecarga.
6. Definir e implementar compra/uso de itens de evolução e captura.
7. Corrigir links diretos para Views, alinhar o nome do projeto e documentar com clareza quando executar a migração de foto.
