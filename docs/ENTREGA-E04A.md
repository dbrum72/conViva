# E04A — Indisponibilidade e afastamento

Implementação em 02/10/2026, conforme as definições do usuário. O período vale somente para o assistido/grupo selecionado e é informado pelo próprio responsável ou cuidador.

## Regras implementadas

- Na Agenda, o botão **Minha disponibilidade** abre um modal com o assistido/grupo e fuso explícitos, que permite informar início/término e consultar os períodos e motivos dos participantes com paginação, respeitando a permissão de consulta ao assistido. Início e término são datas inclusivas (DATE), sem horário; um período de um único dia é permitido. Datas inválidas e término anterior ao início são rejeitados. Os limites de bloqueio são calculados no fuso explícito do grupo, incluindo todo o último dia.
- O intervalo inclui o início e exclui o término. Uma sobreposição parcial também impede a atribuição; cuidados sem duração são verificados pelo instante. Para atribuições sem data, verifica-se o instante da inclusão.
- Incluir ou revisar uma responsabilidade que coincida com a indisponibilidade do participante é rejeitado no Laravel/API antes de persistir o registro ou proposta. A mensagem informa a impossibilidade de compartilhamento, participante e intervalo conflitante. Séries diárias/semanais são verificadas até seu término, inclusive ocorrências ainda não geradas na agenda, respeitando os dias da semana e as lacunas de horário de verão da E04.
- A validação abrange o prestador designado e participantes explicitamente selecionados para assumir uma obrigação. Responsáveis incluídos automaticamente apenas para deliberar continuam podendo decidir durante o afastamento; consulta e permissões permanecem vigentes.
- Propostas pendentes são revalidadas antes do aceite/aplicação. Um afastamento informado depois da proposta pode bloquear seu aceite, sem presumir recusa nem impedir recusa motivada ou retirada. Revisões com campos de programação omitidos também verificam os dados vigentes.
- A execução avulsa ou de ocorrência é impedida durante o afastamento ativo. Também se verifica o horário informado de realização e, nas ocorrências, o período programado. Assim, registro tardio ou horário anterior informado não contorna o impedimento. A agenda e os cuidados avulsos apresentam o motivo do bloqueio; a agenda informa conflitos de prestadores designados também aos demais participantes com acesso ao cuidado.
- Cuidados aprovados e execuções anteriores não são apagados nem transferidos. Cadastrar o afastamento é permitido mesmo com cuidados existentes; o conflito fica visível na agenda e o executor afastado permanece impedido. Resolver a designação exige a revisão e os aceites já previstos no produto.
- Só o próprio usuário cancela seu período. O cancelamento conserva o registro e `cancelled_at`; para alterar datas, cancela-se o período e informa-se outro. Término/cancelamento não recuperam vínculo ou permissões revogados/expirados. Períodos sobrepostos continuam independentes.
- Observadores e pessoas sem edição vigente em rotina ou saúde não cadastram afastamentos, mas participantes com consulta vigente ao assistido podem ler os períodos e motivos do grupo. O isolamento por organização/assistido também vale nas consultas e cancelamentos.

## Persistência e API

Nova entidade `care_unavailabilities`, com organização, assistido, usuário, instantes de início/término, fuso, cancelamento e timestamps. Migration de criação `2026_10_02_100000_create_care_unavailabilities_table.php` aplicada isoladamente em MySQL `conviva_db`, com verificação da configuração e do banco efetivo, sem reconstrução ou remoção dos dados anteriores. O utilitário `backend/tools/apply-unavailability-migration.php` conserva essa proteção para aplicação da nova migration.

- `GET /api/recipients/{recipient}/my-unavailabilities?page=1`: próprios períodos, 20 por página, incluindo cancelados.
- `POST /api/recipients/{recipient}/my-unavailabilities`: `starts_at`, `ends_at` em `Y-m-d` (inclusivos) e `timezone` IANA. A autoria vem da autenticação.
- `POST /api/recipients/{recipient}/my-unavailabilities/{period}/cancel`: cancelamento do próprio período; repetição retorna 409.
- Cuidados/propostas preservam participantes de deliberação, distinguindo os explicitamente indicados para obrigação em `responsibility_user_ids` no payload da proposta. A edição do formulário restaura essa seleção, sem tratar participantes automáticos como novas obrigações.

Cadastro/cancelamento, inclusão/revisão, decisão e execução usam o mesmo bloqueio transacional de organização/assistido da E04. O fluxo da interface continua Vue → Pinia → Axios → controller → serviço Laravel. A store descarta respostas e erros de outro grupo e conserva os dados do formulário quando há erro.

Programação de medicamentos por dose, lembretes externos e passagem de cuidados continuam nas E05–E07. Essas etapas devem reutilizar o contrato de disponibilidade, sem ampliar automaticamente o bloqueio a consulta, finanças ou administração do cadastro.

## Validação e limites

Testes MySQL específicos e de regressão aprovados: **27 testes / 335 asserções**, em seis classes. Usam transações e rollback; nenhum teste desta entrega reconstruiu o banco. Cobrem autoria, cancelamento, revogação, observadores, fuso/DST, paginação, limites e sobreposição parcial, atribuição própria/compartilhada, séries diárias/semanais, medicamentos/vacinas, revisão, proposta pendente, execução avulsa e programada, registro tardio, preservação de designação e isolamento entre grupos.

Suíte frontend completa aprovada: **74 testes em 15 arquivos**. Inclui quatro novos testes de formulário, erros, respostas atrasadas, troca de contexto e mensagem de execução impedida. Após os ajustes finais, os **19 testes de indisponibilidade/agenda** também passaram. Build de produção aprovado, com **2.057 módulos**. Inspeção visual do formulário e do aviso em navegador, com dados sintéticos, em desktop e celular, sem overflow horizontal ou erros de renderização. A prévia não usou autenticação nem gravações reais.

Os serviços conservam a ordem de bloqueios da E04 e o aceite é revalidado após inclusão de um afastamento. Não foi executado novo ensaio com processos simultâneos nem a suíte destrutiva integral de backend; o ambiente Windows não dispõe do `pcntl` usado pelos ensaios existentes. Não houve alteração de `.env`, de bancos adicionais ou do Design System/Legalis.


## Transferência para modal na Agenda (02/10/2026)

- Cadastro, consulta e cancelamento transferidos da tela de cuidados para `CareUnavailabilityDialog.vue`, acionado pelo botão **Minha disponibilidade** na Agenda. Os avisos de impedimento continuam nas telas de cuidados e ocorrências.
- `GET /api/agenda` acrescenta `availability_recipient` com ID/nome do assistido ativo autorizado a cadastrar afastamento, ou `null` quando não há essa capacidade. O botão funciona com agenda vazia e não depende de uma ocorrência ou dos filtros.
- O modal conserva os dados durante falhas, identifica assistido/grupo e fuso, fecha e limpa o formulário ao mudar de grupo, e recarrega o período atual da agenda depois de salvar/cancelar. A confirmação de cancelamento e a paginação dos próprios períodos permanecem disponíveis.
- Validação: **82 testes frontend em 17 arquivos**, **14 testes específicos backend / 112 asserções**, build de produção aprovado (**2.056 módulos**), Pint e whitespace. Testes backend com rollback, sem migrations ou reconstrução nesta transferência.
- Inspeção visual em desktop e celular com dados sintéticos: modal sem overflow horizontal ou erros de renderização; Escape fecha o modal e devolve o foco ao botão Minha disponibilidade. Prévia temporária removida após a conferência.


## Motivo opcional da indisponibilidade (02/10/2026)

- Modal Minha disponibilidade inclui Motivo da indisponibilidade (opcional), com até 2.000 caracteres. O formulário conserva o texto quando há erro e o limpa após salvar ou trocar de contexto.
- `POST /api/recipients/{recipient}/my-unavailabilities` aceita `reason` nullable/string/max:2000; ausência ou texto em branco resulta em null. O motivo fica no histórico dos períodos, inclusive após cancelamento. Conforme correção do usuário, é visível a todos com permissão de consulta ao assistido e integra mensagens de impedimento e conflitos da agenda.
- Campo nullable `reason` acrescentado à migration de criação, conforme política do protótipo. O utilitário protegido de aplicação foi atualizado para acrescentar a coluna à tabela já existente exclusivamente em `conviva_db`, preservando registros; não foi criada migration incremental nem reconstruído o banco.
- Validação: 15 testes backend / 128 asserções com rollback, oito testes frontend do modal/agenda e build aprovados. Cobertura inclui motivo preenchido/ausente/em branco, limite de tamanho, privacidade, preservação do texto após falha e limpeza após sucesso.


## Visibilidade compartilhada dos motivos (02/10/2026)

- Substituída a visibilidade apenas pessoal pela regra solicitada pelo usuário: todos com permissão de consulta ao assistido podem ver os períodos e motivos informados, inclusive observadores. Não há concessão de permissão de cadastro/cancelamento a esses leitores.
- `GET /api/recipients/{recipient}/unavailabilities` retorna períodos paginados do assistido/grupo, autoria limitada a ID/nome e `can_cancel` calculado para o solicitante. A rota `my-unavailabilities` permanece disponível para consulta estritamente pessoal sob a autorização anterior.
- A Agenda fornece contexto de disponibilidade também aos leitores, com `availability_recipient.can_manage` para distinguir consulta e edição. Quem tem apenas consulta abre Disponibilidade do grupo e vê a lista/motivos, sem formulário de cadastro nem cancelamento. Quem pode cadastrar mantém Minha disponibilidade e consulta a mesma lista compartilhada. O formulário informa explicitamente a visibilidade do motivo.
- Motivos informados também acompanham impedimentos de atribuição/execução e conflitos visíveis na agenda. Revogação ou expiração de acesso impede a consulta; cancelamento de período alheio continua vedado.
- Validação: 16 testes backend / 140 asserções com rollback, 83 testes frontend e build aprovados (2.056 módulos). Sem alterações de schema ou dados preexistentes.

### Indisponibilidade na Agenda (02/10/2026)

Os afastamentos não cancelados de participantes ativos, exceto observadores, aparecem em mês, semana, dia e lista, com a superfície cinza do estado desabilitado. Cada item identifica participante, intervalo, assistido e motivo opcional, sem ações de execução de cuidado. A data de término é inclusiva: o afastamento ocupa todo o último dia e não ocupa o dia seguinte.

A API da Agenda entrega os afastamentos sobrepostos ao período solicitado em `unavailabilities`, separados da paginação de cuidados, respeitando o grupo e a permissão de consulta ao assistido. Observadores autorizados podem consultar os afastamentos de responsáveis/cuidadores. O filtro de executor também filtra os afastamentos; os filtros de tipo/situação de cuidado não ocultam esses períodos. Cancelamentos pelo modal recarregam o calendário.

### Datas sem horário (02/10/2026)

`care_unavailabilities.starts_at` e `ends_at` são DATE e a API devolve YYYY-MM-DD. O modal usa seletores de data e a Agenda mostra Dia inteiro / Dias completos. O bloqueio usa internamente o intervalo entre a meia-noite local do início e a meia-noite após o término, convertendo esses limites para UTC e respeitando horário de verão. A Agenda inclui `interval_ends_at` para sua projeção temporal, mantendo `ends_at` como data inclusiva.

A migration de criação foi atualizada e o utilitário protegido converteu a tabela existente exclusivamente em conviva_db. Os instantes antigos foram convertidos para os dias locais abrangidos no respectivo fuso, preservando autores, motivos, cancelamentos e IDs. Nenhuma reconstrução ou migration incremental foi necessária.
