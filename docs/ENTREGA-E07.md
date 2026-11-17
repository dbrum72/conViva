# E07 — Rotina rápida e passagem de cuidados

Implementada em 15/11/2026. O painel **Hoje** ocupa a rota existente `/dashboard` e usa o fuso do grupo ativo.

- Próximos cuidados nos sete dias a partir de hoje, com até seis ocorrências. Inclui cuidados de rotina publicados e ocorrências de saúde autorizadas, com ou sem publicação na Agenda. Quando a execução recente pertence à mesma série, o painel a mostra junto ao próximo cuidado.
- Passagem com execuções, fatos e ocorrências vencidas dos últimos sete dias, além de até seis tarefas sem data. O resumo mostra até oito execuções, oito fatos e seis ocorrências vencidas. Identifica autoria e horário ocorrido e apresenta o resultado vigente das administrações corrigidas.
- Ações de execução reutilizam os endpoints de ocorrência e cuidado avulso. Continuam sujeitas a vínculo, área, designação, disponibilidade e proteções contra duplicidade. Decisões pendentes do usuário abrem na Central.
- Registros rápidos de alimentação e observação para os tipos de assistido existentes, com passeio adicional para pets. São fatos de rotina com autoria autenticada, permissão de edição e horário ocorrido. Alimentação e passeio respeitam afastamentos. A chave da solicitação permite repetir o envio de um mesmo fato em caso de falha de conexão.
- Ciência de leitura é pessoal, vinculada à versão do resumo autorizado. Quando fatos ou pendências exibidos mudam, a nova versão pede nova ciência. A gravação revalida acesso e versão e responde com 409 se o resumo tiver mudado. Nenhuma decisão de proposta é criada por esse fluxo.

## API e validação

- `GET /api/today` retorna ocorrências, passagem, atalhos autorizados e decisões pendentes. O estado Pinia descarta respostas de um grupo anterior.
- `POST /api/recipients/{recipient}/care-facts` registra o fato. `PUT /api/recipients/{recipient}/handover/read` confirma a versão lida.
- A migration de `care_facts` e `care_handover_reads` foi aplicada em `conviva_db`, preservando os dados existentes. Passaram dois testes de integração com 35 asserções usando rollback, 86 testes frontend e o build de produção. A verificação da E07 foi automatizada.

A próxima etapa é **E08 — Despesas e documentos contextualizados**, com consulta por período, totais e comprovantes privados já disponíveis.
