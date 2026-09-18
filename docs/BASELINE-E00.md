# E00 — contratos e baseline do conViva

Verificado em 18/09/2026 por Codex. Escopo: comportamento atual, cenários sintéticos e evidências para iniciar E01. Este documento não antecipa como implementados os recursos futuros do cronograma.

## Fonte das regras

`AGENTS.md` define arquitetura e limites de trabalho; `docs/CONTEXT.MD` e o README descrevem as decisões atuais. A implementação é verificada em `AccessControl`, `CareRecords`, `CareRecipientController`, serviços de membros e testes de API. Múltiplos assistidos por grupo, administrador/coordenador e migration incremental são decisões antigas superadas; o documento histórico de conversão foi removido por solicitação do usuário.

Cada grupo contém no máximo um assistido, mesmo arquivado. Usuário pode ter papéis diferentes em grupos distintos. Identificação do grupo vem do contexto autorizado; possuir ou conhecer um ID não concede acesso. Grupo e vínculo precisam estar ativos.

## Matriz de capacidades

Todas as permissões abaixo dependem do contexto do grupo e do acesso ao assistido. Responsável criador tem acesso às áreas pelo caminho explícito de `AccessControl::allowed`; demais responsáveis dependem do vínculo ao assistido. Convites atuais de responsável concedem todas as áreas, sem expiração. Não confundir perfil do grupo com acesso automático ao assistido.

| Ação | Responsável | Cuidador | Observador |
| --- | --- | --- | --- |
| Consultar área | Criador ou concessão vigente | Concessão vigente | Concessão vigente |
| Criar registro | Área com edição | Área com edição | Não |
| Alterar/cancelar registro | Autor e área com edição; sujeito aos aceites | Autor e área com edição; sujeito aos aceites | Não |
| Responder proposta | Somente o próprio voto pendente e área com edição | Somente o próprio voto pendente e área com edição | Não |
| Retirar proposta | Autor do registro, área com edição e proposta pendente | Mesmas condições | Não |
| Concluir registro original | Autor, área com edição, estado permitido e sem proposta pendente | Mesmas condições | Não |
| Registrar execução vinculada | Se designado, com edição e cuidado confirmado | Mesmas condições | Não |
| Registrar pagamento | Somente a própria parcela aceita e acesso financeiro de edição | Mesmas condições | Não |
| Consultar/baixar documento | Acesso à área documentos | Acesso à área documentos | Acesso à área documentos |
| Enviar documento | Área documentos com edição | Área documentos com edição | Não |
| Excluir documento | Autor e área documentos com edição | Mesmas condições | Não |
| Criar assistido | Permissão `recipients.create`, grupo sem assistido | Não | Não |
| Alterar cadastro | Autor com edição de rotina; bloqueado se houver mais de um responsável vinculado | Backend exige autoria e edição; não há fluxo normal de criação para cuidador | Não |
| Arquivar assistido | Responsável autor; bloqueado se compartilhado | Não | Não |
| Gerir concessões | Responsável vinculado; não substitui/revoga acesso de responsável vinculado | Não | Não |
| Convidar/gerir membros | Conforme permissões; não altera perfil/situação de outro responsável | Não | Não |
| Alterar o próprio perfil/vínculo | Permitido pelas rotas atuais de gestão, inclusive sendo o único responsável | Sem permissão de gestão nessas rotas | Sem permissão de gestão nessas rotas |

Concessão a observador nunca permite escrita, mesmo se um dado legado contiver `can_edit=true`. Expiração é estrita: no instante `expires_at`, a concessão já não é válida. Participante afetado precisa de vínculo ativo e edição da área; observador não pode assumir obrigações.

### Áreas

| Área técnica | Conteúdo atual |
| --- | --- |
| `routine` | `event`, `task`, `journal`, `feeding` |
| `health` | `medication`, `vaccine` |
| `documents` | Listagem, upload, download e exclusão de documentos |
| `finance` | `expense`, rateios e pagamentos declarados |

Uma concessão de documentos não concede rotina. Notificações são filtradas pela área e reautorizadas ao marcar leitura. A agenda atual pertence ao grupo selecionado; a listagem `/api/groups` é agregada e calcula capacidades separadamente.

## Estados e transições

### Proposta (`care_proposals`)

| Estado | Rótulo da interface | Significado |
| --- | --- | --- |
| `pending` | Aguardando aceite | Há participantes que precisam decidir |
| `accepted` | Aceita | Todos os aceites exigidos foram obtidos ou não existem outros participantes afetados |
| `rejected` | Recusada | Um participante recusou com justificativa |
| `withdrawn` | Retirada | Autor retirou a proposta, preservando votos e histórico |

Proposta pendente termina em aceita, recusada ou retirada; nova tentativa é nova versão. `operation=save` cria/altera; `operation=cancel` propõe cancelamento. Votos individuais usam `pending`, `accepted` e `rejected`, com autor da decisão, data e motivo de recusa.

Rotinas, eventos, alimentação, medicamento e vacina incluem os demais responsáveis vinculados. Designado, participantes do rateio e outros afetados também decidem. O autor não recebe voto separado: sua submissão é a manifestação da proposta. Participantes da versão vigente continuam considerados quando uma alteração os remove.

### Registro (`care_entries`)

| Estado | Rótulo atual | Significado |
| --- | --- | --- |
| `awaiting_approval` | Aguardando aceite | Proposta inicial ainda não vigente; sem agenda nem parcelas |
| `pending` | Confirmado | Versão aceita; nome técnico não significa proposta pendente |
| `completed` | Concluído | Autor concluiu registro, todas as parcelas foram pagas ou é um registro próprio de execução |
| `rejected` | Proposta recusada | Proposta inicial recusada, sem versão vigente |
| `withdrawn` | Proposta retirada | Proposta inicial retirada, sem versão vigente |
| `cancelled` | Cancelado | Cancelamento aplicado; histórico preservado |

`revision` identifica a versão aplicada. Alteração pendente mantém título, estado e dados vigentes; recusa/retirada mantém essa versão. A aceitação de alteração reaplica os dados e retorna o registro a `pending`. Despesa com qualquer parcela paga não pode ser alterada/cancelada. Nova proposta é impedida enquanto houver outra pendente.

### Execução

Hoje não há entidade separada de ocorrência: a execução gera outro `CareEntry`, concluído, com `related_entry_id`, autoria do executor e sua própria proposta aceita. O registro original continua pertencendo ao solicitante e não é concluído automaticamente. Uma segunda execução do mesmo designado para o mesmo registro é bloqueada. Correção precisa de novo registro; não pode sobrescrever a execução histórica.

Concluir, executar e pagar ficam bloqueados enquanto há alteração pendente, embora a versão anterior continue vigente. Essa diferença precisa ser comunicada em E02; não mudar o comportamento sem definir o contrato. Recorrência e administrações por dose serão tratadas em E04/E05, sem reaproveitar a restrição atual de uma execução por tarefa para uma série inteira.

## Cenários sintéticos executáveis

Fixture: `backend/tests/Support/CareScenario.php`. Usa factories e banco de teste; não é registrada no seeder da aplicação. Nomes são sintéticos; não envia mensagens externas nem popula `conviva_db`.

| Cenário | Arranjo | Verificação automatizada |
| --- | --- | --- |
| Criança compartilhada | Criador e segundo responsável com todas as áreas | Autor não altera nem arquiva cadastro unilateralmente; registro original preservado |
| Adulto com cuidador temporário | Responsável e cuidador de rotina com expiração em uma hora | Aceite anterior não permite executar depois da expiração; notificações e marcação de leitura também ficam inacessíveis |
| Pet com cuidador autorizado | Usuário é responsável no grupo da criança e cuidador de rotina no grupo do pet | Não herda saúde/gestão; aceita alimentação e registra execução própria sem alterar original |

Esses três testes estão em `backend/tests/Feature/CareBaselineScenariosTest.php`. A suíte anterior já cobre isolamento, observador, documentos privados, convites, rateio, recusa motivada, cancelamento, retirada, suspensão, papéis distintos por grupo e proteção de outro responsável. Não foram duplicados esses fluxos integralmente.

## Evidências de validação

| Verificação | Resultado em 18/09/2026 |
| --- | --- |
| Backend antes da E00 | 35 testes, 227 asserções, aprovados |
| Backend após cenários E00 | 38 testes, 251 asserções, aprovados |
| Frontend | 3 arquivos, 12 testes, aprovados |
| Build de produção | Aprovado; 2.023 módulos transformados |

Comando backend, a partir da raiz:

```powershell
php -d extension=pdo_sqlite backend/vendor/phpunit/phpunit/phpunit -c backend/phpunit.xml
```

Comandos frontend, a partir de `frontend`:

```powershell
npm test
npm run build
```

`backend/phpunit.xml` força SQLite `:memory:`. Não houve reconstrução de banco. Testes/build frontend falharam inicialmente por `spawn EPERM` no sandbox e passaram após execução autorizada fora dele. O build mostrou informação de tempo de plugins, sem impedir a geração.

Limites: este baseline não prova concorrência real em MySQL, acessibilidade visual, funcionamento móvel ou entrega de e-mail. Essas verificações continuam nas etapas correspondentes. As alterações de trabalho preexistentes no README, frontend e remoção de script foram preservadas.

## Encaminhamento para E01

Rotas de autenticação consultadas não oferecem recuperação de senha. Implementar fluxo seguro com token temporário e testes de uso único/expiração; depois concluir entrada, estados de interface e diálogos. E00 não muda regras de produção nem libera a revisão cadastral compartilhada prevista para E02.
