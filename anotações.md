# Relatório de revisão do projeto

**Data:** 17/11/2026  
**Escopo:** verificação somente de leitura; nenhum código foi alterado durante a revisão.

## Resumo

O projeto possui funcionalidades já operacionais, mas ainda não está pronto como jogo completo. Foram identificados conflitos entre a documentação e as regras, uma rota da loja incompatível com o construtor do Controller e requisitos funcionais e acadêmicos pendentes.

## Conflitos e problemas prioritários

1. **Regras de batalha divergentes.** O [README.md](./README.md) define limite de 10 turnos, desempate por prioridade/velocidade e efetividade entre Água, Fogo e Elétrico. O [AGENTS.md](./AGENTS.md) deixa fórmulas e condições de término pendentes, e a tela da batalha é apenas uma prévia. As Traits em [aguaTrait.php](./app/Traits/aguaTrait.php), [fogoTrait.php](./app/Traits/fogoTrait.php) e [plantaTrait.php](./app/Traits/plantaTrait.php) usam Água, Fogo e Planta. É necessário decidir e documentar uma regra única antes de implementar o combate.

2. **Rota da loja com erro de instanciação.** O [BolichoController.php](./app/Controllers/BolichoController.php) exige uma conexão PDO no construtor, mas o [index.php](./public/index.php) não a fornece para essa classe. Assim, acessar `?rota=bolicho` deve gerar erro. A tela [bolicho.php](./app/Views/bolicho/bolicho.php) é apenas visual; não há compra nem uso de itens.

3. **Listagem de Pokémon não usa o catálogo completo do banco.** A rota principal consulta uma lista fixa de nove espécies da PokeAPI pelo [PokemonController.php](./app/Controllers/PokemonController.php). O Model [Pokemon.php](./app/Models/Pokemon.php) tem método para listar o banco, mas ele não está conectado a essa tela. No banco local há 41 espécies.

4. **Links diretos para Views.** O [painel_jogar.php](./app/Views/layouts/painel_jogar.php) aponta diretamente a arquivos em `app/Views`, em vez de passar pelas rotas e Controllers. Como nem todas as Views verificam autenticação, esse fluxo pode contornar as proteções do Controller quando o diretório do projeto é servido pelo Apache.

5. **Migração de foto de perfil pode falhar no banco atual.** O [migracao_foto_perfil.sql](./database/migracao_foto_perfil.sql) adiciona colunas que já existem no [schema.sql](./database/schema.sql). O comentário limita seu uso a bancos antigos; executá-la depois do schema atual causaria erro por colunas duplicadas.

6. **Nome do projeto inconsistente.** A documentação e as telas usam “PokeFish”, “PokeBagual” e “MONSTROBOLSO”.

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
- A classe Pokémon possui dez atributos privados; há herança e Traits para tipos.
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
