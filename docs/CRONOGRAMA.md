# conViva — pesquisa de referência e cronograma de evolução

Data da pesquisa: 18/09/2026. E00 e E01 concluídas em 18/09/2026; E02 concluída em 27/09/2026. E03 e E04 concluídas em 28/09/2026. E04A implementada em 02/10/2026; contrato e limites em `docs/ENTREGA-E04A.md`. Próxima etapa sequencial: E05 — medicamentos e vacinas. Evidências em `docs/BASELINE-E00.md`, `docs/ENTREGA-E01.md`, `docs/ENTREGA-E02.md` e no registro de execução abaixo.

Ajuste solicitado antes de E02: sidebar com seleção/criação de grupos somente em Meus assistidos e avatar pessoal do assistido por responsável. Entrega descrita em `docs/AVATAR-PESSOAL.md`, preservada na E02.

Verificação de retomada em 27/09/2026: frontend inicial com 31 testes e build aprovados. Após alinhamento dos testes a MySQL `conviva_db`, conforme orientação do usuário, os 50 testes backend preexistentes passaram com 363 asserções. Histórico da conferência em `docs/VERIFICACAO-PRE-E02.md`; implementação da E02 em `docs/ENTREGA-E02.md`.

## 1. Direção do produto

Evoluir o conViva como aplicação de cuidados compartilhados de crianças/adolescentes, adultos sob cuidados e pets. A experiência deve responder rapidamente: **o que precisa acontecer, quem aceitou, quem executou e o que ainda depende de decisão**.

O diferencial é coordenar cuidados sem exigir conversa direta entre responsáveis. Portanto, a prioridade é uma rotina confiável, com propostas estruturadas, aceite individual, histórico e execução identificada. Chat, rede social e automação clínica não são o centro do produto.

Este plano considera o código e as regras atuais de `AGENTS.md`, `README.md` e `docs/CONTEXT.MD`. Cada grupo admite no máximo um assistido, inclusive arquivado. O documento histórico de conversão foi removido por solicitação do usuário; as decisões vigentes permanecem no contexto e no baseline.

## 2. Método e limites da pesquisa

Foram consultadas páginas oficiais de produtos especializados em coparentalidade, coordenação de cuidados e acompanhamento de pets. A seleção privilegia proximidade funcional, documentação disponível e padrões úteis ao conViva; não constitui ranking independente de participação de mercado ou comprovação de superioridade.

Os recursos descritos são declarações públicas dos fornecedores. Não foram contratadas assinaturas nem testadas áreas autenticadas. Preços, disponibilidade por plano e qualidade operacional não foram comparados. As propostas técnicas e prioridades abaixo são recomendações para o conViva, não funcionalidades atribuídas aos concorrentes.

### Referências e aplicação prática

| Referência | Evidência observada na fonte oficial | Aplicação proposta no conViva | Limite da adaptação |
| --- | --- | --- | --- |
| OurFamilyWizard | Agenda compartilhada, pedidos de troca com resposta, despesas com comprovantes e histórico organizado. [Fonte](https://www.ourfamilywizard.com/) | Central de decisões, comparação entre versões e rastreabilidade de compromissos e despesas. Etapas E02, E04 e E08. | Não importar autoridade administrativa sobre outro responsável nem promessas de validade judicial. |
| OurFamilyWizard — relatórios | Exportação de registros por áreas, incluindo agenda, despesas e diário. [Fonte](https://support.ourfamilywizard.com/hc/en-us/articles/42818693916045-How-do-I-access-the-reports-in-OurFamilyWizard) | Relatórios delimitados por período e área autorizada. E09. | Exportar somente dados que o solicitante pode consultar. |
| 2houses | Agenda compartilhada com sincronização, gestão de despesas e diário familiar. [Fonte](https://www.2houses.com/en) | Agenda navegável, visão financeira por período e histórico cotidiano. E04, E07, E08 e E11. | Manter os três tipos de assistido; não restringir a experiência a pais separados. |
| Jointly, da Carers UK | Círculos de cuidado, tarefas, medicamentos, contatos, notas e permissões para conteúdo privado. [Guia](https://jointlyapp.com/user-guide) e [apresentação](https://jointlyapp.com/jointly-at-glance). | Ficha útil do assistido, contatos, passagem de cuidado e visão do dia. E03 e E07. | Não reproduzir edição coletiva irrestrita ou poder unilateral de um administrador. |
| Lotsa Helping Hands | Calendário de pedidos de ajuda, participação de membros e lembretes. A página consultada usa também a marca My Cancer Circle. [Fonte](https://lotsahelpinghands.com/how-it-works) | Tarefas com responsável definido, aceite e confirmação de execução. E04 e E07. | Não transformar convite ou designação em obrigação já aceita. |
| Medisafe — Medfriend | Apoio de pessoa indicada e alertas após ausência de registro de medicamento. [Fonte](https://app.medisafe.com/tips/med-friend-in-need-is-med-friend-indeed/) | Ocorrências programadas, registro de administração e alertas configuráveis a pessoas autorizadas. E05 e E06. | Ausência de registro não prova ausência de administração; não sugerir dose ou conduta. |
| DogLog | Registros compartilhados, eventos personalizados, lembretes, fotos e acompanhamento de tendências. [Fonte](https://www.doglogapp.com/) | Atalhos para alimentação, passeio e cuidados, com executor e horário visíveis. E03 e E07. | Preservar um assistido por grupo, mesmo que a referência ofereça vários pets no mesmo conjunto. |

O guia do Jointly apresentou conteúdo indexado na busca, mas sua abertura direta exigiu JavaScript; a análise se limita ao conteúdo público recuperado. Não se presume equivalência entre permissões dos produtos pesquisados e as regras do conViva.

### Síntese das escolhas

1. **Adotar primeiro:** visão do dia, decisões claras, agenda recorrente, execução identificada e lembretes controlados.
2. **Adaptar depois:** ficha de referência, passagem de cuidados, comprovantes e relatórios.
3. **Investigar após o piloto:** agenda externa, instalação como aplicativo web e indicadores agregados.
4. **Deixar fora do primeiro lançamento:** chat obrigatório, chamadas, geolocalização contínua, movimentação bancária, diagnóstico, prescrição e IA que decida pelos participantes.

## 3. Diagnóstico do repositório

Leitura estática do código; os testes existentes foram inspecionados, mas não executados para produzir este documento. Quantidades e resultados mencionados no README são históricos, não uma nova validação.

| Área | Base encontrada | Lacuna ou trabalho recomendado |
| --- | --- | --- |
| Identidade e grupos | JWT, convites, seleção de grupo, listagem agregada de assistidos e isolamento por organização. | Revisar recuperação de conta, expiração de sessão e jornada inicial; rotas consultadas não expõem recuperação de senha. |
| Autorizações | `AccessControl.php`, áreas, expiração, perfil observador e regras de autoria. | Reutilizar a autorização em novos endpoints, relatórios, jobs e notificações externas. |
| Acordos | `CareRecords.php`, `CareProposal`, `CareDecision`, versões e transações. | Central de pendências, diferenças entre versões, tratamento de bloqueios e revisão cadastral compartilhada. |
| Agenda | `AgendaPage.vue` mostra lista; `CareOverviewController::agenda` consulta registros datados do contexto atual. | Visões por período, recorrência, ocorrências e paginação. Agenda global entre grupos não está implícita no endpoint atual. |
| Saúde | Medicamento e vacina são tipos de `CareEntry`; detalhes têm dose, frequência e via em texto. | Separar programação e execução; texto de frequência não deve gerar doses automaticamente. |
| Notificações | Registros internos e leitura; consulta limitada aos últimos 100. | Paginação, preferências, filas e lembretes. `routes/console.php` não contém agendamentos de cuidados. |
| Documentos | Arquivos privados, validação de tipo/tamanho e autorização de download; exclusão pelo autor. | Categorias, vínculos a registros, validade opcional e tratamento consistente de falha na exclusão do arquivo. |
| Finanças | Valores inteiros em centavos, rateio, aceite e pagamento da própria parcela. | Consulta financeira dedicada, filtros, comprovantes e correções rastreáveis. Consulta básica GET /api/finance e totais no Laravel já disponíveis; operações financeiras unificadas em Despesas. Filtros avançados e paginação continuam pendentes. |
| Interface | Vue, Pinia, Axios e componentes próprios; `RecipientPage.vue` reúne múltiplas áreas e formulários. | Dividir componentes por jornada, substituir diálogos nativos e melhorar estados de erro, carregamento e acessibilidade. |
| Qualidade | `CareIsolationTest.php` e testes de frontend para estado, decisões e grupos. | Acrescentar cenários de concorrência, recorrência, filas e exportação conforme cada entrega. |

### Arquivos de referência para executar as etapas

- Backend: `backend/routes/api.php`, `backend/routes/console.php`, `backend/app/Services/Care/AccessControl.php`, `backend/app/Services/Care/CareRecords.php` e `backend/app/Services/Organizations/ListCareGroups.php`.
- Contratos e persistência: `backend/app/Http/Requests/CareEntryRequest.php`, `backend/app/Models/CareEntry.php` e `backend/database/migrations/2026_09_16_100000_create_care_tables.php`.
- Interface: `frontend/src/views/care/`, `frontend/src/components/care/CareDecisions.vue`, `frontend/src/state/care.js`, `frontend/src/services/care.js` e `frontend/src/state/auth.js`.
- Testes: `backend/tests/Feature/CareIsolationTest.php`, `frontend/tests/care.spec.js`, `frontend/tests/decisions.spec.js` e `frontend/tests/care-groups.spec.js`.

## 4. Regras obrigatórias para todas as entregas

- Um grupo contém no máximo um assistido; participação em vários grupos não combina permissões.
- Responsáveis decidem por si. Nenhum responsável suspende, rebaixa ou remove outro unilateralmente. Preservar a possibilidade atual de alterar o próprio vínculo, inclusive quando for o único responsável.
- Silêncio nunca equivale a aceite. Recusa exige motivo. Mudança pendente preserva o acordo anterior; nova proposta não cria compromisso confirmado nem parcela antes dos aceites necessários.
- Autoria, decisão e execução são informações distintas. O executor registra fato próprio sem sobrescrever o registro do solicitante.
- Observadores não assumem obrigações. Revogação, suspensão e expiração precisam valer também em processos assíncronos.
- Regras de negócio ficam no Laravel. Fluxo: Vue → ação Pinia → serviço Axios → controller → serviço de domínio → Eloquent.
- Manter Laravel, Vue, Pinia, Vite, Axios e MySQL. Nomes de classes e tabelas sugeridos neste relatório são propostas, a ajustar aos padrões locais, sem introduzir outro framework.
- No protótipo, modificar migrations de criação existentes para novos campos. Para novas entidades, criar migrations de criação; não adicionar incrementais de campos. Reconstrução, quando necessária, somente em `conviva_db`, verificando o destino antes de executar. Nunca alterar bancos ou arquivos do legalis nem `.env`.
- O Design System é apenas consultivo. Reutilizar os componentes do conViva e preservar alterações locais preexistentes.

## 5. Cronograma proposto

As semanas são relativas ao início aprovado da execução, não datas de compromisso. Premissa: uma pessoa desenvolvedora em dedicação principal, com apoio pontual de produto e validação. As faixas incluem implementação e testes; dependem das decisões de escopo e não representam orçamento fechado.

P0 = base necessária para piloto confiável; P1 = evolução funcional prioritária; P2 = expansão condicionada ao uso real.

| Etapa | Entrega | Prioridade | Dependências | Esforço estimado |
| --- | --- | --- | --- | --- |
| E00 | Baseline e contratos de produto | P0 | — | 2–3 dias úteis |
| E01 | Acesso, entrada e qualidade da interface | P0 | E00 | 5–8 dias |
| E02 | Central de decisões e revisão cadastral | P0 | E01 | 6–10 dias |
| E03 | Ficha e experiência por tipo de assistido | P1 | E02 | 4–6 dias |
| E04 | Agenda e motor de ocorrências | P0 | E02 | 8–12 dias |
| E04A | Indisponibilidade/afastamento de responsável ou cuidador | P0 | E02, E04 | 4–7 dias |
| E05 | Programação e execução de medicamentos | P0 | E03, E04, E04A | 6–10 dias |
| E06 | Lembretes e operação assíncrona | P0 | E04, E04A, E05 | 6–9 dias |
| E07 | Rotina rápida e passagem de cuidados | P1 | E03, E04, E06 | 4–6 dias |
| E08 | Despesas e documentos contextualizados | P1 | E02, E03 | 5–8 dias |
| E09 | Histórico, exportação e privacidade | P0 | E05–E08 | 5–8 dias |
| E10 | Piloto e preparação operacional | P0 | E01–E09 | 5–8 dias |
| E11 | Integrações e expansão validada | P2 | E10 | estimar após piloto |

E00–E10, incluindo E04A, somam **60–95 dias úteis**, aproximadamente **12–19 semanas**, sem reserva. Planejar mais 20% para ajustes do piloto e imprevistos: envelope aproximado de **15–23 semanas**. A faixa de esforço da E04A foi estimada antes da implementação; as evidências da entrega estão em `docs/ENTREGA-E04A.md`. Esta soma assume execução sequencial; dependências permitem reorganização se houver equipe adicional.

Marcos de entrega:

- **M1 — base utilizável:** E00–E03 concluídas; convite, ficha e decisão compreensíveis.
- **M2 — rotina confiável:** E04–E07, incluindo E04A, concluídas; afastamento respeitado, ocorrência aceita, execução e lembrete coerentes.
- **M3 — piloto completo:** E08–E10 concluídas; finanças, histórico, privacidade e operação validados.
- **M4 — expansão:** somente após evidências do piloto e nova priorização de E11.

## 6. Etapas executáveis

### E00 — Fixar baseline e contratos de produto

- [x] Registrar o estado atual dos testes e build, sem assumir que números anteriores continuam válidos.
- [x] Documentar matriz de capacidades por perfil, área e ação; usar `AccessControl` como referência de execução.
- [x] Definir estados de proposta, registro vigente e execução, incluindo os rótulos apresentados na interface.
- [x] Montar cenários sintéticos: criança com dois responsáveis, adulto com cuidador temporário e pet com cuidador autorizado; incluir usuário com papéis diferentes em dois grupos.
- [x] Consolidar referências documentais atuais, identificando decisões históricas superadas.

**Codificação:** fixtures/seeders de teste isolados e testes de caracterização somente para regras críticas ainda sem cobertura. Não substituir o serviço de decisões nesta etapa.

**Aceite:** matriz e cenários versionados; baseline reproduzível; nenhuma alteração no legalis ou em arquivos de ambiente.

### E01 — Acesso, entrada e interface consistente

- [x] Tornar clara a sequência conta → assistido/grupo → convite → primeiro cuidado.
- [x] Explicar perfil, assistido e áreas antes do aceite do convite; exibir convites expirados ou revogados com ação possível.
- [x] Implementar recuperação de senha, se confirmada a ausência, com token temporário de uso único, limite de tentativas e resposta que não revele existência de conta.
- [x] Padronizar sessão expirada, carregamento, lista vazia, falha recuperável e confirmação de ações.
- [x] Dividir os formulários de `RecipientPage.vue` em componentes de cuidado; substituir `window.prompt` e `window.confirm` por componentes existentes apropriados.
- [x] Validar navegação por teclado, foco em diálogos, mensagens associadas aos campos e uso em tela móvel.

**Codificação:** ampliar Requests/controllers de autenticação, serviços e stores existentes; criar componentes pequenos em `frontend/src/components/care/`. Não mover validação de domínio para Vue. Manter proteção contra respostas atrasadas após troca de grupo.

**Aceite:** cadastro e convite completos em celular; erros compreensíveis; recuperação expira e não pode ser reutilizada; troca de grupo nunca mostra dados ou ações herdados do anterior.

### E02 — Central de decisões e cadastro compartilhado

- [x] Exibir minhas decisões pendentes, propostas enviadas, participantes, motivo de recusa e diferença entre versão vigente e proposta.
- [x] Permitir navegação direta da notificação até a proposta autorizada.
- [x] Implementar proposta de alteração/arquivamento do cadastro compartilhado, hoje bloqueada; manter o cadastro vigente até os aceites necessários.
- [x] Mostrar bloqueio quando participante necessário perde acesso, sem aprovar, excluir voto ou substituir participante automaticamente.
- [x] Definir e testar o efeito de entrada/saída de responsável sobre propostas abertas; registrar participantes exigidos por versão e revalidar autorização na aplicação.

**Codificação:** evoluir `CareRecords`, `CareProposal`, `CareDecision` e `CareDecisions.vue`; criar serviço de revisão cadastral separado ou estrutura equivalente, sem forçar revisão do assistido dentro de um registro de cuidado. Novos endpoints de pendências devem ter filtros e paginação. Controlar concorrência por transação, versão e respostas 409.

**Aceite:** respostas simultâneas não aplicam duas versões; recusa preserva acordo anterior; revisão cadastral exige todos os aceites previstos; retirada mantém histórico; pessoa sem acesso não vota nem aparece como consentimento presumido.

### E03 — Ficha útil para cada tipo de assistido

- [x] Organizar identificação, contatos, instruções e informações de referência por área de acesso.
- [x] Criança/adolescente: contatos e referências escolares, rotina e pessoas autorizadas conforme definição do produto.
- [x] Adulto: preferências, contatos de apoio e instruções de cuidado informadas pelos participantes.
- [x] Pet: espécie/raça já existentes, identificação opcional, contato veterinário e rotina específica.
- [x] Oferecer modelos de preenchimento curtos, sem tornar campos de outro tipo obrigatórios.

**Codificação:** ampliar `CareRecipientRequest`, model e migration de criação; introduzir contatos estruturados e campos tipados apenas onde forem consultados/validados. Dados de saúde devem ter autorização própria, mesmo quando exibidos junto à identificação. Aplicar E02 às mudanças compartilhadas.

**Aceite:** cuidador de rotina não consulta conteúdo de saúde por endpoint nem pela ficha; campos e linguagem correspondem ao tipo; responsáveis conseguem revisar mudanças sem sobrescrever a versão vigente.

### E04 — Agenda e ocorrências recorrentes

- [x] Adicionar visões dia/semana/mês e lista acessível, com período, tipo, executor e situação como filtros.
- [x] Introduzir série, ocorrência e exceção: data isolada, repetição diária/semanal, término e cancelamento de uma ocorrência ou das futuras.
- [x] Manter propostas pendentes em área separada da agenda confirmada.
- [x] Explicitar fuso do grupo; guardar instantes em UTC e regra local de recorrência com fuso identificado.
- [x] Detectar sobreposição e informar conflito, sem impor troca de responsável.
- [x] Diferenciar execução registrada de conclusão administrativa do registro original.

**Codificação proposta:** `CareSchedule`, `CareOccurrence` e serviço `GenerateCareOccurrences`, vinculados à versão aprovada do cuidado. Usar chave única de série/instante/versão aplicável e geração idempotente em janela limitada. Reutilizar filas e scheduler do Laravel. Aceite da série autoriza apenas as ocorrências descritas por ela; alteração futura exige nova proposta.

**API/UI:** ampliar a agenda com `from`, `to`, filtros e paginação; criar ações Pinia e componentes de navegação. Começar no grupo selecionado. Visão entre grupos, se feita depois, deve revalidar cada contexto como `ListCareGroups`, sem remover o isolamento globalmente.

**Aceite:** duas execuções do gerador não duplicam ocorrência; mudança de fuso e virada de dia preservam horário esperado; exceção não altera histórico; revisão não aceita não modifica ocorrências vigentes. Validar concorrência também em MySQL, seguindo a proteção de destino e a política de banco descartável descritas na seção 7.

### E04A — Indisponibilidade/afastamento de responsável ou cuidador

**Implementada em 02/10/2026**, incluída por solicitação do usuário; evidências e limites em `docs/ENTREGA-E04A.md`. O identificador E04A preserva a numeração existente das etapas E05–E11 e as referências históricas.

- [x] Permitir prever e registrar períodos de indisponibilidade/afastamento de responsável ou cuidador, com início, término e fuso explícitos; definir os limites do intervalo e validar as datas.
- [x] Quando o próprio usuário informar indisponibilidade ou afastamento, impedir que ele execute cuidados durante o período defeso, inclusive cuidados já aprovados e ocorrências de séries recorrentes.
- [x] Impedir a inclusão de responsabilidade compartilhada atribuída a outro usuário quando o período do cuidado coincidir, total ou parcialmente, com a indisponibilidade/afastamento informado por esse usuário. Informar ao solicitante a impossibilidade de compartilhar o cuidado no período e identificar o participante indisponível e o intervalo conflitante, incluindo o motivo opcional informado, visível aos participantes com permissão de consulta ao assistido.
- [x] Aplicar o impedimento no Laravel, inclusive em chamadas diretas à API, revalidando a indisponibilidade na inclusão de responsabilidades compartilhadas e em toda operação de execução de cuidado; informar o período e o motivo do bloqueio na interface.
- [x] Identificar conflitos com cuidados já aprovados, propostas pendentes e séries que atravessem o período, preservando histórico e execuções anteriores, sem transferir obrigações ou presumir aceite de substitutos.
- [x] Permitir revisão/cancelamento do afastamento conforme as permissões definidas, preservando autoria e histórico; ao terminar o período, restaurar somente capacidades ainda autorizadas.
- [x] Integrar disponibilidade à agenda e aos medicamentos/vacinas datados já existentes; documentar o contrato para programação por dose (E05), lembretes/jobs (E06) e passagem de cuidados (E07), a implementar nessas etapas.

**Contrato confirmado e comportamento implementado:**

1. Regra confirmada em 02/10/2026: o próprio usuário informa indisponibilidade/afastamento e fica impedido de executar cuidados durante o período defeso. Além do bloqueio de execução, impedir a inclusão de responsabilidade compartilhada com outro usuário indisponível no período do cuidado, informando a impossibilidade ao solicitante. Consulta e demais propostas/decisões seguem suas autorizações vigentes, respeitando esse impedimento de atribuição.
2. Âmbito confirmado pelo usuário: somente o assistido/grupo selecionado. A autoria é do próprio usuário.
3. Cuidados já aprovados: o afastamento é registrado, o histórico e a designação são preservados e a execução fica bloqueada. Conflitos de designação ficam visíveis na agenda; eventual substituição depende de revisão e dos aceites vigentes.

**Codificação prevista:** entidade própria de períodos de indisponibilidade vinculada ao usuário e ao âmbito escolhido; migration de criação, FormRequests e serviço Laravel de domínio. Integrar a verificação às capacidades de `AccessControl`, inclusão de responsabilidades e decisões de `CareRecords` e ocorrências da E04. Validar o período do cuidado e cada ocorrência aplicável de séries recorrentes contra os intervalos de indisponibilidade do participante designado; rejeitar a inclusão conflitante antes de persistir responsabilidade ou proposta inválida. Coordenar afastamento e operações de cuidado em transações para impedir operações concorrentes incompatíveis. Manter o fluxo Vue → Pinia → Axios → controller → serviço Laravel; controles desabilitados na interface não substituem validação no backend.

**Aceite:** inclusão de responsabilidade com usuário indisponível no período rejeitada também pela API, sem persistir atribuição ou proposta inválida, com mensagem clara de impossibilidade de compartilhamento; testar coincidência total, parcial e ocorrências recorrentes, além de inclusão concorrente com cadastro de afastamento; início e término respeitados no fuso definido; execução pelo usuário afastado bloqueada também pela API durante o período defeso, inclusive em cuidados já aprovados; ocorrências fora do período conservam as regras normais; séries têm conflitos identificados por ocorrência; cuidados já aprovados seguem a decisão de escopo; nenhuma substituição ou anuência automática; concorrência não permite operação incompatível; término/cancelamento não recupera acesso revogado ou expirado. Validar responsáveis e cuidadores, com isolamento por grupo ou aplicação global conforme o âmbito escolhido.

### E05 — Medicamentos e vacinas com execução identificada

- [ ] Separar instrução informada, programação aprovada e registro efetivo de administração.
- [ ] Solicitar horários estruturados para novas programações; manter frequência textual anterior como informação, sem interpretação automática.
- [ ] Registrar execução com autor, horário ocorrido, horário de registro e observação; distinguir administrado, não administrado e ausência de registro.
- [ ] Bloquear duplo registro acidental da mesma ocorrência; oferecer correção vinculada com justificativa, sem apagar autoria.
- [ ] Registrar vacina aplicada e próxima data informada, sem calcular calendário clínico automaticamente.

**Codificação:** modelos de programação/administração ou especialização das ocorrências de E04; Requests específicos por tipo, serviço transacional e índices de unicidade. Interface de confirmação curta e histórico por ocorrência. Não reutilizar a atual restrição de uma execução por tarefa inteira para séries com várias doses.

**Aceite:** dois cuidadores tentando confirmar a mesma ocorrência recebem resultado consistente; nenhuma dose é criada a partir de texto livre; registro atrasado é distinguível; correção preserva original; só acessa quem possui área de saúde.

### E06 — Lembretes confiáveis e preferências

- [ ] Entregar primeiro notificações internas e e-mail; acrescentar push web quando a infraestrutura e compatibilidade forem validadas.
- [ ] Configurar antecedência, canal, resumo e horários de silêncio; adesão a alertas de terceiros deve ser explícita.
- [ ] Notificar pendência de aceite e ocorrência próxima; tratar atraso como falta de registro, sem inferir falha de cuidado.
- [ ] Exibir destino interno da notificação e situação de envio quando útil; paginar histórico.
- [ ] Cancelar envios futuros de ocorrências canceladas; suprimir envio após execução ou perda de acesso.

**Codificação:** preferências, entregas com chave idempotente, jobs com tentativas limitadas e retentativa; dispatcher pelo scheduler em `backend/routes/console.php`. Revalidar vínculo, área, versão e ocorrência no momento de envio. Definir explicitamente contexto da organização em jobs e limpá-lo ao terminar, inclusive em falhas. Evitar conteúdo sensível na tela bloqueada e nos logs.

**Aceite:** reexecução de job não duplica entrega controlável; revogação entre enfileiramento e processamento impede envio; falha do provedor não desfaz o cuidado; fila parada é detectável. Documentar que transporte externo pode falhar e não equivale a confirmação humana.

**Dependência operacional:** envio real precisa de serviço de e-mail e configuração pelo operador responsável. Não editar `.env` como parte automática da implementação. Testar com transporte simulado e ambiente de homologação preparado.

### E07 — Rotina rápida e passagem de cuidados

- [ ] Criar painel Hoje com próximos cuidados, últimas execuções, pendências e decisões necessárias.
- [ ] Disponibilizar atalhos de registro adequados ao assistido: alimentação, passeio, observação e execução de tarefa.
- [ ] Exibir quem fez o quê e quando antes de oferecer nova execução, reduzindo duplicidade.
- [ ] Criar resumo de passagem de cuidados com fatos recentes e pendências; ciência de leitura não deve equivaler a aceite de obrigação.
- [ ] Separar fato já ocorrido de proposta futura: registrar observação não pode confirmar compromisso de outra pessoa.

**Codificação:** serviço Laravel para resumo autorizado do dia; componentes `TodayCareList`, `CareExecutionForm` e `CareHandover` ou equivalentes. Agregações e indicadores de estado no backend; Pinia mantém dados compartilhados. Extensões de tipos de registro exigem revisão conjunta de `CareEntryRequest` e `AccessControl::area`.

**Aceite:** usuário identifica próxima ação e última execução sem abrir várias telas; fluxo funciona sem chat; observação pessoal não muda acordo; passagem contém apenas áreas acessíveis ao destinatário.

### E08 — Despesas e documentos contextualizados

- [ ] Criar consulta financeira dedicada com período, categoria, participante e situação, com totais calculados no Laravel.
- [ ] Exibir total, parcelas aceitas, pagamentos declarados e saldo, distinguindo declaração de pagamento de confirmação bancária.
- [x] Vincular comprovantes privados à despesa e registrar pagamento somente da própria parcela. Antecipado por solicitação do usuário em 27/09/2026; evidências em `docs/COMPROVANTES-PAGAMENTO.md`.
- [ ] Preservar o bloqueio atual de alteração/cancelamento de despesa paga. Se necessária correção, usar ajuste separado com referência, justificativa e novos aceites.
- [ ] Organizar documentos por categoria, período e validade opcional; permitir associação a cuidado ou instrução.
- [ ] Tratar falhas entre banco e armazenamento, incluindo exclusão que não deve ser apresentada como concluída quando o arquivo persistiu.

**Codificação:** serviço de consulta financeira, endpoints paginados, atualização de `ExpenseShare`, documentos e componentes de `FinancePage.vue`. Continuar com centavos inteiros. Documento vinculado exige autorização tanto ao contexto do registro quanto ao documento; vínculo nunca deve ampliar acesso silenciosamente.

**Aceite:** totais fecham exatamente em centavos; proposta não aceita não cria dívida; usuário não paga por outro; comprovante não vaza por URL; falha de armazenamento fica recuperável. Sem Pix, cobrança automática ou conciliação bancária nesta entrega.

### E09 — Histórico, exportação e privacidade

- [ ] Disponibilizar linha do tempo com propostas, decisões, recusas, execução e correções, filtrada por área.
- [ ] Exportar inicialmente CSV e página própria para impressão; avaliar geração de PDF após validar necessidade e dependências.
- [ ] Registrar solicitante, filtros, período e instante da exportação; arquivos temporários privados e com expiração.
- [ ] Definir inventário de dados, finalidade, retenção, encerramento de conta e tratamento de registros compartilhados; submeter políticas aplicáveis à revisão especializada antes do lançamento público.
- [ ] Separar trilha de auditoria de logs operacionais: logs não devem conter tokens, conteúdo de saúde ou arquivos privados.

**Codificação:** serviço de exportação, job para volume elevado e endpoint de download com nova autorização. Trilhas de alteração anexadas ao histórico existente, com controle de acesso. CSV precisa neutralizar conteúdo interpretável como fórmula por planilhas. Definir descarte de exportações e backups conforme política documentada.

**Aceite:** revogação durante geração impede entrega; exportação não contém outra área/grupo; impressão identifica período e autoria; encerramento de conta não apaga automaticamente registros de terceiros. Não prometer imutabilidade contra administradores de banco ou validade judicial apenas porque há histórico.

### E10 — Piloto, qualidade e preparação operacional

- [ ] Executar cenários completos dos três tipos de assistido e dos três perfis, com dados sintéticos antes de uso real.
- [ ] Validar concorrência de decisões, duplicidade de execução, troca de grupo, revogação, falha de rede e indisponibilidade de fila.
- [ ] Preparar processo de backup/restauração testado, monitoramento de API/fila/scheduler, alertas de falha e procedimento de recuperação.
- [ ] Configurar ambiente de homologação e pipeline com testes e build; verificar configuração de produção e armazenamento privado sem expor segredos.
- [ ] Medir usabilidade móvel, teclado, contraste e leitura assistiva nas jornadas principais.
- [ ] Realizar piloto por pelo menos duas semanas de uso, recolher dificuldades e priorizar correções antes de expansão.

**Codificação:** testes de integração para fluxos de domínio, testes Vue para interação relevante e ajustes de desempenho orientados por medições. Paginar listagens e evitar consultas repetidas de permissões. Não trocar arquitetura para resolver problemas ainda não medidos.

**Aceite:** todos os testes pertinentes e build aprovados; nenhuma falha conhecida de isolamento ou autoria; restauração demonstrada; responsável operacional definido; incidentes de envio observáveis; participantes concluem jornadas essenciais sem suporte constante.

### E11 — Expansão condicionada ao piloto

- [ ] Avaliar exportação de calendário antes de sincronização bidirecional. Assinaturas externas exigem tokens revogáveis, escopo mínimo e aviso de que cópias já importadas não podem ser recolhidas.
- [ ] Avaliar aplicativo web instalável. Começar com cache de recursos estáticos; escrita offline de saúde ou decisões exige desenho de conflitos e nova análise de autorização.
- [ ] Avaliar agenda pessoal entre grupos sem unificar membros ou permissões.
- [ ] Avaliar tendências de rotina e carga de tarefas com métricas descritivas, sem classificação clínica ou julgamento automático dos cuidadores.
- [ ] Avaliar modelos reutilizáveis de rotina e solicitações de ajuda, sempre com aceite individual de obrigações.

**Aceite para iniciar:** problema recorrente comprovado no piloto, benefício mensurável, esforço estimado e contrato de permissões definido. Não iniciar por simples paridade com concorrentes.

## 7. Ordem técnica de implementação por etapa

1. Descrever regra e critério de aceite; identificar dados e permissões afetados.
2. Atualizar migration de criação correspondente ou criar migration de nova entidade conforme política do protótipo.
3. Implementar/ajustar models, relacionamentos, índices e serviço Laravel de domínio.
4. Implementar FormRequests, controllers finos e rotas REST.
5. Adicionar método Axios em `frontend/src/services` e ação/estado em `frontend/src/state`.
6. Construir componentes e telas reutilizando UI local; backend informa capacidades e decisões permitidas.
7. Executar testes proporcionais ao risco e build; registrar evidência na etapa.
8. Atualizar documentação e marcar conclusão somente quando o aceite estiver demonstrado.

Comandos já documentados pelo projeto, a executar nos diretórios indicados durante a implementação:

```powershell
# Em backend — DESTRUTIVO: testes em MySQL conviva_db
php artisan test
# Na raiz — reconstruir o banco após os testes
php backend/tools/rebuild-conviva.php
```

```powershell
# Diretório frontend
npm test
npm run build
```

Por orientação do usuário na E02, a suíte, inclusive os ensaios concorrentes, usa o MySQL `conviva_db` descartável, com proteção explícita do destino e reconstrução ao final. A execução apaga dados; a leitura ou atualização deste relatório não exige reconstrução.

## 8. Métricas e critérios de sucesso

As metas abaixo são propostas para o piloto, não resultados já obtidos. Instrumentar eventos sem conteúdo sensível e analisar por jornada, evitando rankings pessoais.

| Objetivo | Medida | Meta inicial proposta |
| --- | --- | --- |
| Entrada compreensível | Participantes que concluem conta, assistido e convite sem ajuda | Pelo menos 80% dos participantes avaliados |
| Rapidez de uso | Tempo para registrar execução de ocorrência já configurada | Mediana de até 30 segundos em teste de usabilidade |
| Clareza de decisão | Participantes que distinguem proposta de acordo vigente | Pelo menos 90% no teste de compreensão |
| Confiabilidade | Duplicidades em testes de concorrência e reprocessamento | Zero nos cenários especificados |
| Isolamento | Vazamento entre grupos/áreas nos testes de acesso | Zero; bloqueia liberação |
| Lembretes | Entregas processadas, falhas, atraso e duplicidade | Baseline medido; meta operacional definida após conhecer provedor |
| Valor cotidiano | Grupos do piloto que usam a rotina na segunda semana | Medir baseline e motivos de abandono antes de fixar meta |

## 9. Riscos e decisões a resolver durante as etapas

| Questão | Encaminhamento proposto | Momento |
| --- | --- | --- |
| Aceite a cada repetição pode inviabilizar rotina | Aprovar série bem delimitada; reaprovar alterações; execução continua individual | E02/E04 |
| Participante perde acesso e proposta fica bloqueada | Mostrar impedimento e permitir retirada/nova proposta sob regras explícitas; nunca converter silêncio em aceite | E02 |
| Adulto assistido pode querer participar das próprias decisões | Definir representação e participação com produto; não presumir incapacidade pelo tipo de cadastro | E00/E03 |
| Saída do último responsável | Preservar regra atual de saída; informar consequências e definir recuperação de acesso sem conceder poder unilateral a terceiros | E01/E02 |
| Modelagem genérica de `CareEntry` pode ficar excessiva | Manter histórico comum e criar entidades específicas para recorrência/administração quando necessário | E04/E05 |
| Excesso de alertas | Preferências, resumos, horários de silêncio e cancelamento de alertas obsoletos | E06 |
| Arquivo visível numa área pode expor outra | Validar acesso combinado a documento e contexto; testar vínculos e exportação | E08/E09 |
| Prazo depende da equipe e do piloto | Reestimar ao concluir E00 e cada marco, sem converter estimativa em promessa | Todos |

O escopo da E04A foi esclarecido e implementado em 02/10/2026; a próxima etapa é E05. Antes de iniciar E00, convém confirmar equipe disponível, público inicial do piloto e canal externo de notificações. Na ausência dessas definições, manter as premissas deste documento: três tipos de assistido contemplados, web responsiva, notificações internas/e-mail primeiro e uma pessoa desenvolvedora como referência de esforço.

## 10. Registro de execução

Usar as caixas de seleção de cada etapa como tarefas. Para concluir uma etapa, acrescentar registro com: data, responsável, alterações, testes executados, evidência do aceite e pendências. As caixas concluídas têm evidências no registro abaixo.

| Marco | Status inicial | Evidência necessária |
| --- | --- | --- |
| M1 — E00 a E03 | Concluído: E00, E01, E02 e E03 | Fluxo de entrada, decisão e ficha validado |
| M2 — E04 a E07, incluindo E04A | Em andamento: E04 concluída; E04A implementada; próxima etapa E05 | Afastamento, recorrência, execução e lembretes testados |
| M3 — E08 a E10 | Não iniciado | Relatórios, operação e piloto aprovados |
| M4 — E11 | Aguardando piloto | Priorização baseada nas métricas e dificuldades observadas |

### 18/09/2026 — E00 concluída

- Responsável pela execução: Codex.
- Contrato e evidências: `docs/BASELINE-E00.md`, com matriz de capacidades, áreas, estados, transições e limites do baseline.
- Codificação: fixture isolada `backend/tests/Support/CareScenario.php` e três testes em `backend/tests/Feature/CareBaselineScenariosTest.php` para criança compartilhada, adulto com cuidador temporário e pet com cuidador que é responsável em outro grupo.
- Documentação: decisões históricas superadas identificadas; documento de conversão posteriormente removido por solicitação do usuário.
- Baseline inicial: backend com 35 testes / 227 asserções. Resultado final: 38 testes / 251 asserções; frontend com 12 testes; build aprovado.
- Frontend exigiu execução autorizada fora do sandbox após `spawn EPERM`. Nenhum `.env`, banco MySQL ou arquivo do legalis foi alterado.
- Pendências: E01 ainda não iniciada; validação visual/móvel, concorrência MySQL e envio externo ficam nas etapas previstas. A E00 não altera comportamento de produção.

### 18/09/2026 — E01 concluída

- Responsável pela execução: Codex. Evidências e limites: `docs/ENTREGA-E01.md`.
- Recuperação de senha ponta a ponta, broker Laravel, token em hash com expiração/uso único, limites de tentativas e resposta sem exposição de contas. Redefinição invalida JWTs anteriores; sessões legadas exigem novo login.
- Orientações de início, permissões no convite e tratamento de convite expirado/conexão; aceite via Pinia. Diálogos de registro e execução extraídos; confirmações nativas substituídas; carregamento, erro e nova tentativa padronizados.
- Proteção contra respostas atrasadas na troca de grupo e encerramento de sessão, incluindo 401 de token anterior.
- Verificação: **45 testes backend / 296 asserções; 24 testes frontend em 7 arquivos; build de produção aprovado**. Inspeção móvel em 390 × 844 e testes de foco/Escape/retorno ao acionador.
- Persistência: nova tabela de tokens aplicada por migration de criação exclusivamente em `conviva_db`, sem reconstrução. Conta, grupo e cuidados sintéticos usados na inspeção; convite sintético expirado ao final.
- Documento histórico de conversão removido conforme solicitado; referências atualizadas. Nenhum `.env` ou arquivo/banco do legalis alterado.
- Limites: envio SMTP externo e concorrência real MySQL ainda não verificados; não se trata de auditoria integral de acessibilidade. Próxima etapa: E02.

### 27/09/2026 — E02 concluída

- Responsável pela execução: Codex. Contratos, endpoints e limites em `docs/ENTREGA-E02.md`.
- Central de decisões paginada e autorizada por grupo/área, filtros, comparação de versões, participantes, motivos e destinos diretos de notificações.
- Revisão e arquivamento cadastral por propostas próprias, mantendo cadastro vigente até os aceites; recusa e retirada preservam histórico.
- Participantes fixados por versão; divergência na rede e perda de acesso bloqueiam aplicação, inclusive para votos já aceitos e autoria. Transações serializadas com alterações de membros e acessos; respostas repetidas retornam 409.
- Backend: 61 testes / 463 asserções na suíte completa MySQL; após ajuste final, 11 testes / 101 asserções, incluindo concorrência real de cuidados e cadastro, aprovados. Frontend: 36 testes em 10 arquivos. Build de produção aprovado, 2.040 módulos. Pint e whitespace aprovados.
- Banco: usuário autorizou expressamente executar testes destrutivos em `conviva_db` e reconstruí-lo ao final. Destino verificado na suíte; reconstrução com migrations e seeder concluída. Nenhum `.env` alterado e nenhum banco adicional criado.
- Limites: sem nova inspeção visual em navegador ou ensaio de carga; e-mail externo não validado. Próxima etapa sequencial: E03.

### 27/09/2026 — Comprovantes de pagamento, item antecipado da E08

- Modal de pagamento com arquivo opcional, anexo posterior à própria parcela paga e download privado nas telas de cuidados e despesas.
- Autorização combinada de finanças/documentos; autoria da parcela; arquivo original preservado; pagamento e anexo coordenados por transação.
- Nova tabela aplicada isoladamente em `conviva_db`, sem reconstrução ou remoção dos dados existentes. Testes específicos usam rollback transacional.
- Validação: 7 testes backend / 59 asserções; 40 testes frontend; build aprovado. Detalhes em `docs/COMPROVANTES-PAGAMENTO.md`.
- E08 não está concluída. A próxima etapa sequencial continua sendo E03.

### 28/09/2026 — E03 concluída

- Ficha por tipo, contatos estruturados, instruções e referências separadas por rotina/saúde. Campos opcionais e exemplos curtos.
- Revisão compartilhada preserva a ficha vigente; saúde filtrada também nas propostas e comparações, com revalidação de acesso para aceite.
- Frontend: 43 testes e build aprovados. Backend: 63 testes aprovados na execução conjunta; sete testes de comprovantes aprovados separadamente (59 asserções) após reconstrução, devido à limpeza dos testes concorrentes. Evidências e limites em `docs/ENTREGA-E03.md`.
- Migration de criação atualizada e `conviva_db` reconstruído após testes. Próxima etapa: E04.

### 28/09/2026 — Ajuste de revisão cadastral antes da E04

- Qualquer responsável com acesso vigente pode propor revisão, inclusive convidado; autoria do cadastro não restringe essa capacidade.
- Único responsável aplica imediatamente; vários responsáveis exigem anuência de todos, preservando a versão anterior durante a pendência.
- Cinco testes backend / 51 asserções aprovados com rollback, sem reconstrução do banco. Detalhes em `docs/ENTREGA-E03.md`.

### 28/09/2026 — Decisões concentradas na Central antes da E04

- Aceite e recusa motivada nos próprios cartões, com comparação expansível, bloqueios e atualização após resposta.
- Respostas removidas das telas de cuidado/detalhe; links e notificações encaminham para a Central.
- “Revogar” foi esclarecido pelo usuário como recusar proposta pendente, sem desfazer aceite anterior. Sem alteração de banco; detalhes em `docs/ENTREGA-E03.md`.


### 28/09/2026 — E04 concluída

- Agenda por dia/semana/mês/lista, período, tipo, executor, situação e paginação autorizada; propostas pendentes continuam na Central.
- Séries aprovadas com regra local/fuso, ocorrências UTC, geração idempotente, exceções por proposta e execução identificada. Revisões preservam histórico e programação vigente enquanto pendentes.
- Fuso informado ao criar grupo; séries mantêm seu próprio fuso aprovado. Sobreposições visíveis são avisadas sem alterar designações.
- Job por grupo e comando diário para antecipar 90 dias; consulta gera a janela necessária. Scheduler/worker precisam ser mantidos pelo operador.
- Suíte completa: **91 testes backend / 739 asserções**, incluindo cinco ensaios concorrentes reais em MySQL; **52 testes frontend em 13 arquivos**; build aprovado (2.047 módulos). Pint e whitespace aprovados.
- Ordem da suíte corrigida para executar concorrência após testes transacionais, resolvendo a pendência de composição registrada na E03.
- `conviva_db` reconstruído ao final com migrations e seeder. Contas e dados anteriores não foram preservados, conforme política do banco descartável; nenhum `.env` ou arquivo/banco do legalis alterado.
- Contratos, limites e evidências em `docs/ENTREGA-E04.md`. Próxima etapa sequencial: **E05 — programação e execução de medicamentos**.

### 02/10/2026 — Replanejamento: afastamento como próxima etapa

- Incluída E04A antes da E05 para previsão de indisponibilidade/afastamento de responsável ou cuidador e impedimento de cuidado compartilhado para o usuário no período.
- Dependências, marco M2 e estimativa total atualizados; medicamentos e vacinas permanecem na E05, agora dependente da E04A.
- Esclarecimento do usuário em 02/10/2026: ao informar indisponibilidade/afastamento, o próprio usuário fica impedido de executar cuidados durante o período defeso. Âmbito entre grupos e tratamento dos conflitos com cuidados aprovados permanecem pendentes. Esta atualização é documental; a E04A ainda não foi implementada.
- A indicação de E05 como próxima etapa no registro de 28/09/2026 representa o planejamento daquela data e é substituída por este replanejamento.

### 02/10/2026 — Impedimento de compartilhamento durante indisponibilidade

- Ao tentar inserir uma responsabilidade para outro usuário indisponível no período do cuidado, o solicitante deve ser impedido e informado da impossibilidade de compartilhamento.
- E04A atualizada com bloqueio na inclusão, validação no backend/API, mensagem ao solicitante e critérios de aceite para conflitos de período e concorrência. Mantido o bloqueio de execução pelo usuário afastado.


### 02/10/2026 — E04A implementada

- Cadastro/cancelamento de indisponibilidade pelo próprio usuário, restrito ao assistido/grupo selecionado; painel expansível na tela de cuidados e histórico paginado.
- Inclusão/revisão de responsabilidade conflitante rejeitada com mensagem de impossibilidade de compartilhamento; recorrências verificadas até o término; aceite revalidado após novo afastamento.
- Execução avulsa/programada bloqueada no período; verificação do horário de realização e do período da ocorrência; cuidados aprovados preservados, sem substituição automática.
- Nova migration aplicada isoladamente em `conviva_db`; testes desta entrega usam transações/rollback, sem reconstrução do banco.
- Validação: 27 testes backend / 335 asserções com rollback; suíte frontend completa com 74 testes, mais 19 testes específicos após ajustes finais; build aprovado. Evidências, contratos e limites em `docs/ENTREGA-E04A.md`. Próxima etapa: **E05 — medicamentos e vacinas**.


### 02/10/2026 — Disponibilidade transferida para modal na Agenda

- Botão Minha disponibilidade na Agenda; cadastro, consulta e cancelamento no modal, com contexto explícito de assistido/grupo e fuso. Painel removido da tela de cuidados; avisos de impedimento preservados.
- A agenda identifica o assistido autorizado mesmo sem ocorrências. Salvar/cancelar recarrega os avisos; troca de grupo fecha e limpa o modal.
- Validação: 82 testes frontend, 14 testes específicos backend / 112 asserções com rollback, build e inspeção visual desktop/celular aprovados. Sem nova migration ou reconstrução do banco. Detalhes em `docs/ENTREGA-E04A.md`.


### 02/10/2026 — Motivo opcional da indisponibilidade

- Campo opcional no modal de disponibilidade, com limite de 2.000 caracteres. A visibilidade inicialmente pessoal foi corrigida por instrução do usuário: o motivo é visível a todos com permissão de consulta ao assistido, inclusive em conflitos e impedimentos.
- Migration de criação atualizada; coluna aplicada exclusivamente em `conviva_db`, preservando registros existentes. 15 testes backend / 128 asserções, oito testes frontend específicos e build aprovados. Detalhes em `docs/ENTREGA-E04A.md`.


### 02/10/2026 — Motivo compartilhado com participantes autorizados

- Consulta dos afastamentos e motivos do grupo liberada aos participantes com permissão de visualizar o assistido, inclusive observadores. Autor identificado; formulário/cancelamento oferecidos somente quando autorizados, sem permitir cancelar período de terceiros.
- Motivo incluído nas mensagens de impedimento e conflitos da agenda. Revogação/expiração impede consulta; isolamento por grupo/assistido preservado.
- 16 testes backend / 140 asserções, 83 testes frontend e build aprovados. Sem alteração de banco.

### Ajuste E04A — datas de indisponibilidade (02/10/2026)

- Início e término passam a representar datas inclusivas, armazenadas como DATE e enviadas como YYYY-MM-DD. Períodos de um dia são permitidos.
- Modal sem campos de horário; Agenda identifica afastamentos de dia inteiro. Bloqueio inclui todo o último dia no fuso informado, respeitando horário de verão.
- Migration de criação e tabela existente atualizadas exclusivamente em conviva_db; registros preservados, sem reconstrução.
