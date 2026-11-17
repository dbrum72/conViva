# E05 — Medicamentos e vacinas

Na aba Saúde dos cuidados do assistido, o responsável pode guardar instruções e propor uma programação. Dose, via e frequência em texto permanecem como referência. A programação de medicamentos usa horários explícitos com fuso e admite data isolada ou repetição diária/semanal com fim. A aplicação de vacina usa data isolada. A Central de Decisões mostra os horários na comparação e aplica mudanças após os aceites exigidos.

O link **Consultar ocorrências** abre o histórico do cuidado aprovado, inclusive quando ele não foi publicado na Agenda geral. O Laravel filtra por grupo, assistido e acesso à área de saúde. Na Agenda geral, a publicação continua explícita.

Cada ocorrência recebe um resultado (**Administrada** ou **Não administrada**), autoria, horário ocorrido, horário de registro e observação opcional. Sem resultado, a ocorrência permanece programada ou vencida sem registro. Na aplicação de vacina, uma data posterior pode ser informada para referência. Essa data fica no histórico, sem gerar outro compromisso, lembrete ou cálculo de calendário clínico.

O próprio autor, com edição de Saúde vigente em assistido ativo, pode corrigir o registro com justificativa. A correção é acrescentada ao histórico e identifica o registro corrigido. A situação da ocorrência reflete o resultado vigente. O histórico exibe o original e cada correção com autoria e datas.

## Contratos

- `schedule.times` contém de 1 a 24 valores `HH:mm` distintos para medicamentos. O menor horário coincide com o horário de `local_start`. Uma data isolada admite uma dose. As verificações de disponibilidade avaliam todos os horários no fuso aprovado.
- `GET /api/agenda?health_entry={id}&status=all` consulta ocorrências do cuidado de saúde aprovado por período, com paginação. Identificadores fora do escopo autorizado retornam 404.
- `POST /api/occurrences/{id}/execution` recebe `occurred_at`, `outcome` (`administered` ou `not_administered`), `description` opcional e `next_date` opcional para vacina aplicada. Um segundo registro na mesma ocorrência retorna 409. Ocorrências datadas legadas aceitam a confirmação sem `outcome` para compatibilidade.
- `POST /api/occurrences/{id}/execution/{execution}/corrections` exige `reason` e o identificador da versão vigente da execução. Identificador desatualizado retorna 409. O original continua associado à ocorrência.
- A persistência usa as tabelas de programações, ocorrências e registros vinculados. O bloqueio transacional por organização, assistido e ocorrência serializa registros e correções. A unicidade de programação e instante mantém uma ocorrência por horário. A entrega dispensa migration.

## Validação

Passaram 23 testes transacionais MySQL com 244 asserções, incluindo aprovação, múltiplas doses, isolamento da Agenda geral, duplicidade, acesso à Saúde, correções e próxima data de vacina. A suíte frontend passou com 85 testes e o build de produção foi concluído. Pint aprovou os arquivos PHP alterados.

O cenário com dois responsáveis tentando registrar a mesma dose simultaneamente requer `pcntl` e ficou sem execução no PHP Windows local. A validação de frontend usou testes de componentes e build.

Próxima etapa do cronograma é a E06 — lembretes e preferências.
