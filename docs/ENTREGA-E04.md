# E04 — Agenda e ocorrências recorrentes

Implementação de 28/09/2026. A agenda do grupo passa a consultar ocorrências de programações aprovadas, com versões próprias, exceções e execução identificada.

## Entrega e contrato

- Visões dia, semana, mês e lista, navegação por período, filtros de tipo, executor e situação, com paginação no servidor. A lista inclui somente áreas autorizadas; totais, opções de executor e conflitos respeitam esse mesmo limite. Propostas pendentes ficam na Central de Decisões, acessível pela agenda.
- Compromissos, tarefas e alimentação admitem data isolada, repetição diária ou semanal, dias da semana, duração de zero a 1.440 minutos e término inclusivo em até um ano do início. Medicamentos e vacinas continuam com datas isoladas; a programação por dose pertence à E05. Nenhuma frequência textual é interpretada automaticamente.
- Fuso IANA explícito na criação do grupo (padrão `America/Sao_Paulo`), no formulário de programação e na agenda. A série guarda horário local, fuso e regra; ocorrências guardam instantes UTC. Alterar o fuso de exibição do grupo não reinterpreta uma série. Alterar o fuso da série exige nova proposta.
- Na mudança de horário de verão, uma hora local inexistente é omitida; não é deslocada silenciosamente. Um início inexistente é rejeitado. Uma hora repetida gera somente uma ocorrência, com o deslocamento resolvido pelo calendário PHP/Carbon. O formulário informa esse comportamento.
- Uma revisão só altera a programação após os aceites. O corte é o instante de aplicação: ocorrências anteriores mantêm série e conteúdo originais; futuras sem execução são substituídas. Uma execução antecipada também permanece histórica e impede uma nova ocorrência no mesmo instante da série revisada. Revisões de programação exigem a versão conhecida.
- O autor pode propor cancelamento de uma ocorrência futura ou dela e das futuras da mesma série. A proposta usa os votos e a central existentes; só produz efeito após os aceites. Recusa e retirada mantêm a programação. Se a ocorrência passar antes da decisão, o aceite fica bloqueado, preservando recusa e retirada. Ocorrências já executadas nunca são apagadas.
- Sobreposições de horários do assistido ou executor são informadas, sem mudar designações. O aviso considera apenas cuidados visíveis e também os que ficaram em outra página/filtro; não revela a existência de conteúdo de outra área.
- O prestador designado registra execução da ocorrência com horário ocorrido, horário de registro, autoria e observação. Duas confirmações simultâneas geram um registro e uma resposta 409. A execução gera `CareEntry` próprio, ligado por `related_entry_id`, sem concluir o original. A conclusão administrativa do original aparece separadamente e mantém o bloqueio de novas execuções previsto no contrato anterior.
- Registros anteriores com data ganham programação isolada ao consultar a agenda; uma execução vinculada anterior é reconhecida, sem voltar a oferecer a mesma ocorrência como não executada. Cuidados sem data mantêm o fluxo anterior de execução.

## Persistência, API e operação

`care_schedules` contém versão aprovada, regra local, cópia do conteúdo e intervalo de vigência. `care_occurrences` contém instante, situação, referência da execução e referência da proposta de cancelamento quando aplicável. Índices únicos de registro/versão e série/instante, mais bloqueios transacionais, protegem a geração concorrente. A leitura da ocorrência usa bloqueio para evitar a visão antiga de uma transação MySQL após esperar outra geração.

As tabelas são novas entidades na migration `2026_09_28_100000_create_care_schedules_table.php`. `organizations.timezone` e `care_entries.schedule` foram acrescentados às migrations de criação, conforme a política do protótipo. Não há migration incremental de campos.

- `GET /api/agenda`: `from` e `to` são datas inclusivas no fuso do grupo, período limitado a 93 dias; `kind`, `executor`, `status`, `page` e `per_page` (até 100). Retorna envelope paginado, fuso e executores autorizados. Situações: `scheduled`, `executed`, `cancelled`, `superseded`. O painel inicial foi adaptado ao novo contrato.
- `POST /api/occurrences/{occurrence}/execution`: `occurred_at` e observação opcional. Revalida grupo, área, vínculo, executor, estado e proposta pendente.
- `POST /api/occurrences/{occurrence}/cancellation`: `scope=one|future`; cria proposta `cancel_occurrence`, respondida pelos endpoints já existentes da Central.
- Criar/editar cuidado aceita `schedule` estruturado; formulários, serviços Axios e Pinia preservam o fluxo do projeto. A store descarta páginas e erros atrasados, inclusive após troca de grupo/sessão. Falha de execução conserva o formulário.

A geração ocorre sob demanda para a janela consultada e pelo comando `php artisan care:generate-occurrences`, que enfileira um job por grupo ativo para os próximos 90 dias. O scheduler agenda o comando diariamente. O job restaura o contexto de grupo ao terminar, inclusive em falha. O operador precisa manter scheduler e worker ativos para a geração antecipada; a agenda funciona com geração sob demanda. Lembretes e entregas externas continuam na E06.

## Validação

- Suíte completa backend: **91 testes / 739 asserções aprovados** em MySQL `conviva_db`, sem testes ignorados. Inclui 13 testes funcionais da agenda e três cenários novos com processos/conexões independentes (geração, execução e aceite de série), além dos dois ensaios concorrentes anteriores.
- Cobertura: recorrência diária/semanal, unicidade, paginação, conflitos autorizados, virada UTC, transições de horário de verão, revisão/fuso, exceções, corte futuro seguido de revisão/cancelamento, versão desatualizada, execução antecipada, revogação, isolamento, legado e contexto do job.
- Frontend: **52 testes em 13 arquivos aprovados**; seis testes novos de agenda, programação, comparação e proteção de contexto. Build de produção aprovado: **2.047 módulos**.
- `php backend/tools/rebuild-conviva.php` concluído após os testes: migrations e seeder aplicados exclusivamente em `conviva_db`, incluindo as novas tabelas. O banco foi reinicializado; o seeder cria permissões, não contas de acesso.
- Pint e `git diff --check` aprovados. Nenhum `.env` ou arquivo/banco do legalis foi alterado.

A composição da suíte foi corrigida: `phpunit.xml` executa testes concorrentes após os testes comuns/transacionais. Assim, a limpeza de schema de `DatabaseMigrations` não interrompe os testes de comprovantes. Os testes concorrentes continuam exigindo `pcntl` e conexões MySQL independentes. A suíte continua destrutiva e protegida para uso exclusivo de `conviva_db`.

Limites da entrega original: não houve inspeção visual em navegador, auditoria com leitor de tela, teste de carga ou operação prolongada de um worker externo. A reformulação visual posterior está registrada abaixo. A janela de geração é limitada, mas uma consulta ainda pode processar várias séries do mesmo grupo; medir desempenho no piloto antes de ampliar volume. A agenda é do grupo selecionado, sem agregação entre grupos.

E04 concluída. Próxima etapa sequencial: **E05 — programação e execução de medicamentos**.


## Reformulação da agenda seguindo o Legalis (28/09/2026)

Por solicitação do usuário, a view foi refeita a partir da estrutura visual da agenda de `/home/lucas/code/legalis`, consultada somente para referência.

- Mês como visão inicial, grade de sete colunas começando na segunda-feira, seis semanas, destaque de hoje e do dia selecionado. Miniaturas por tipo, horário e título; dias com muitos cuidados mostram três itens e a indicação dos demais.
- Painel lateral com todos os cuidados do dia selecionado, executor, situação, alertas e ações autorizadas. Em telas menores, o painel fica abaixo do calendário.
- Barra de navegação com anterior/próximo, Hoje, período e botões Mês/Semana/Dia/Lista. Filtros de tipo, executor e situação aplicam imediatamente; uma data permite saltar para outro período.
- Semana em sete colunas, com detalhes acessíveis pela seleção do dia; lista agrupada por data com período livre e paginação. Conteúdo com duração entre dias aparece em cada dia ocupado, respeitando o fuso do grupo e término exclusivo à meia-noite.
- A store reúne todas as páginas da API nas visões de calendário, evitando datas falsamente vazias. Publica o resultado completo somente no contexto vigente e conserva a paginação na lista. Falha em página intermediária não publica calendário parcial.
- Componentes próprios para mês e detalhes do cuidado; regras de autorização, execução e decisão continuam no backend existente. Nenhuma alteração de banco ou arquivo do Legalis nesta reformulação.

Validação: **57 testes frontend em 13 arquivos aprovados**, incluindo seleção, navegação, teclado, dias com múltiplos cuidados, fusos, carregamento de páginas, falha parcial e troca de contexto. Build aprovado com **2.050 módulos**. Inspeção visual em Chrome headless com dados sintéticos: desktop 1.440 × 1.100 e celular 390 × 844, sem erros de renderização nem overflow horizontal da página. Foi inspecionada a view isolada, sem autenticação ou gravações reais; não constitui uma nova validação ponta a ponta do backend. Arquivo temporário de prévia removido ao concluir.


### Ajuste da identidade cromática

A agenda passou a usar a paleta conViva de forma mais visível: verde suave na barra e no cabeçalho do dia, amarelo em Hoje/seleção, coral nos compromissos e no marcador da data atual, lavanda nos cabeçalhos do calendário e referências de saúde. Miniaturas, legendas e etiquetas usam fundos coloridos com texto escuro; os cartões recebem fundos suaves por tipo. Ajuste restrito a CSS, com tokens existentes do tema; build de produção aprovado.


### Vermelho reservado ao vencimento sem execução

Por solicitação do usuário, compromissos usam lavanda e o marcador de hoje usa verde. O vermelho fica restrito a ocorrências vencidas sem execução registrada, em mês, semana, dia e lista. O Laravel informa `is_overdue`: ocorrência programada, sem referência de execução e com término anterior ao instante da consulta. Canceladas, substituídas e executadas não são marcadas; despesas e registros de diário não recebem esse indicador de execução. Conflitos de horário usam amarelo, sem confundir sobreposição com vencimento. A indicação diz “Vencido · sem execução registrada”, preservando a distinção entre ausência de registro e confirmação de que o cuidado não ocorreu.

Validação específica: oito testes backend/oito asserções, sem migrations ou alterações de dados; 12 testes da agenda frontend e build aprovados. Pint e whitespace aprovados.

### Ampliação da paleta

Acrescentados petróleo suave (#B8D8D3), azul névoa (#C2D6E5) e areia (#E6D6B8) ao tema, mantendo a identidade pastel existente. Compromissos passam de lavanda para petróleo suave em etiquetas, legendas e cartões de todas as visões, com texto escuro (#285B57) e superfície clara (#EDF5F3). Azul névoa e areia ficam disponíveis para usos complementares. O vermelho continua reservado ao vencimento sem execução na agenda. Alteração somente de estilos e documentação; build de produção aprovado (2.050 módulos).


### Publicação explícita na agenda

Todos os tipos de cuidado com data passam a exigir “Publicar na agenda”. O campo `care_entries.publish_to_agenda` é booleano, com padrão falso na migration de criação, conforme a política do protótipo. Registros anteriores não são considerados publicados automaticamente. O formulário permite marcar/desmarcar, exige data quando marcado e preserva o valor na edição. A comparação de propostas mostra a mudança como Sim/Não.

A escolha integra a versão aprovada do registro e respeita autoria, permissão de edição e os aceites já exigidos. Enquanto houver proposta pendente, prevalece a publicação vigente. A agenda filtra publicação no servidor antes de paginação, conflitos e lista de executores, sempre respeitando grupo, assistido e área. Não publicar não oculta o cadastro na tela de cuidados nem elimina séries ou execuções históricas. O link para ocorrências na agenda só aparece quando o registro está publicado.

Validação: suíte completa backend com **102 testes/822 asserções**, frontend com **59 testes em 13 arquivos**, build de produção com **2.050 módulos**, todos aprovados. Inclui os sete tipos, ausência de data, legado não publicado, metadados sem vazamento, acesso revogado, autoria, aceites e marcação/desmarcação no formulário. O `conviva_db` foi reconstruído com migrations e seeder após a suíte destrutiva; não houve alteração no Legalis.


### Encerramento administrativo separado da execução

Mantido `status=completed` com `completed_at` para o cadastro: a ausência dessa informação impediria distinguir aberto/encerrado e consultar quando foi encerrado. A interface usa “Cadastro encerrado”, explica que isso não cancela a programação e mantém a data. Registros vinculados de execução usam “Executado”. Não houve alteração de schema ou reconstrução do banco.

O encerramento deixa de bloquear execução de ocorrências programadas e cuidados avulsos. Sem designado, a execução pode ser registrada por responsável ativo com edição na área; com designado, continua restrita a ele. A identidade vem da autenticação, o horário de realização é informado no formulário e o horário de registro é guardado pelo servidor. A execução não reabre o cadastro nem altera a data de encerramento. Cancelamento de programação continua sujeito aos aceites, sem ser inferido do encerramento administrativo.

Validação específica: três testes backend/33 asserções, usando transações e rollback; 21 testes frontend da agenda/store, build aprovado. Casos incluem encerramento, execução tardia, autoria autenticada, data futura inválida, duplicação, restrição por designação, papéis, edição e revogação de acesso. A expectativa da suíte E04 foi atualizada para permitir execução após encerramento; essa suíte destrutiva não foi rodada nesta alteração.
