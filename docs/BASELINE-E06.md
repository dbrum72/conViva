# Baseline do conViva após a E06

Atualizada em 14/11/2026. Ponto de retomada da E07 e da migração modular.

## Estado do projeto

- O cronograma registra E00 a E06, incluindo E04A. A E07 permanece pendente. Finanças já tem consulta por período, totais e comprovantes privados. O restante da E08 segue no cronograma.
- Auth, Groups e Notifications estão modularizados no Vue e no Laravel. O módulo HTTP fica no frontend e centraliza os clientes Axios. O Auth ainda expõe a interface de contexto de grupo usada pelas telas existentes. A troca de grupo invalida o estado e as respostas pendentes do contexto anterior.
- Assistidos, cuidados, decisões e finanças ainda usam a estrutura anterior. A migração modular segue incremental e preserva rotas, permissões e regras de negócio.

## Contratos vigentes

- Cada grupo tem no máximo um assistido, inclusive arquivado. Um usuário pode ter papéis distintos por grupo. O Laravel valida vínculo ativo e acesso vigente à área. O aceite de convite cria o vínculo ao grupo e a concessão ao assistido em uma transação.
- Propostas guardam versões e respostas individuais. Enquanto a revisão aguarda os aceites exigidos, preserva-se a versão vigente. A recusa exige motivo e o autor pode retirar a própria proposta. A Central concentra as respostas.
- A Agenda geral contém cuidados aprovados com publicação explícita. Recorrência usa o fuso aprovado. Indisponibilidades abrangem dias completos e afetam atribuições e execuções. O encerramento administrativo é independente do registro de execução.
- A E05 guarda um resultado por ocorrência de saúde, com autoria, horário ocorrido e horário de registro. Correções justificadas preservam o original. A próxima data informada após vacina fica no histórico. A ficha de Saúde dá acesso às ocorrências autorizadas mesmo sem publicação na Agenda geral.
- Na E06, cada usuário ativa canais por grupo. Há antecedência configurável, silêncio no fuso do grupo, resumo diário e adesão explícita a alertas sobre ocorrências de terceiros. Os jobs revalidam acesso e situação antes de entregar. O contador considera todas as notificações autorizadas, independentemente da página da caixa de entrada.

## Evidências e operação

- E06: 3 testes de backend com 35 asserções, 85 testes de frontend e build de produção aprovados. Migration da E06 aplicada localmente. O teste de e-mail usou transporte em memória.
- O scheduler enfileira lembretes a cada cinco minutos. `php artisan notifications:check-reminders` verifica a atividade do dispatcher e do worker. SMTP real e operação contínua do worker ainda precisam ser verificados no ambiente de implantação. Push web continua condicionado à infraestrutura.
- Os cenários transacionais da E05 passaram. A concorrência real de duas confirmações na mesma dose requer `pcntl` e ficou pendente no PHP Windows local.
- Esta atualização registra as evidências das entregas anteriores. A árvore de trabalho contém alterações locais das etapas e da modularização, inclusive arquivos novos.

## Retomada

Começar pela **E07 — Rotina rápida e passagem de cuidados**. O painel Hoje deve mostrar o próximo cuidado, as últimas execuções, pendências e decisões necessárias. O resumo de passagem só pode incluir áreas acessíveis ao destinatário. Ciência de leitura e registro de fatos ocorridos têm contratos próprios, distintos do aceite de propostas.

Consultar [o cronograma](CRONOGRAMA.md), [o contexto do projeto](CONTEXT.MD) e as entregas [E05](ENTREGA-E05.md) e [E06](ENTREGA-E06.md). A [baseline E00](BASELINE-E00.md) registra o estado histórico de partida.
