# E09 — Histórico, exportação e privacidade

## Entrega

- A navegação do grupo inclui Histórico. A linha do tempo mostra propostas e revisões cadastrais, decisões, recusas, retiradas, execuções e correções. A autorização por área ocorre no Laravel antes da consulta, da geração e do download.
- A tela solicita PDF usando o período e a área aplicados. O PDF identifica período, fuso, áreas, solicitante e autoria dos registros. Exportações anteriores em CSV e HTML continuam sujeitas à autorização e expiração originais.
- A geração usa a fila e revalida as concessões após ler o histórico. A API autoriza novamente cada download. Os arquivos ficam em armazenamento privado e expiram após 24 horas. O scheduler roda a limpeza de hora em hora e remove metadados de auditoria após 90 dias.
- Exceções da API e do banco vão para os logs operacionais sem a mensagem original nem os dados da requisição. A trilha de solicitação de exportações fica em tabela própria.
- A migration cria apenas a tabela de exportações. Foi aplicada em `conviva_db` após a verificação do destino, sem reconstruir o banco.

## Operação e privacidade

Instalar as dependências do backend pelo Composer para disponibilizar o Dompdf. Manter o worker de fila e o scheduler ativos no ambiente de implantação. A [especificação](../specs/features/history.md) documenta os contratos e propõe o inventário, os prazos de guarda, o descarte de backups e o procedimento de encerramento de conta. O encerramento é um procedimento operacional, com tratamento de vínculos e preservação de registros compartilhados. As políticas propostas exigem revisão especializada antes do lançamento público.

## Como repetir a validação

Em 18/11/2026, passaram 12 testes PHP com 105 asserções e 100% das linhas e métodos do módulo History. Os 92 testes do frontend e o build de produção passaram. O módulo Histórico no Vue atingiu 100% de instruções, linhas, funções e ramificações.

Com a exportação exclusiva em PDF, passaram 13 testes PHP com 115 asserções e 100% das linhas e métodos do módulo. Os quatro testes do Histórico no Vue, a cobertura de 100% do módulo e o build de produção foram confirmados.

Na pasta `frontend`:

```powershell
npm run test:coverage -- --coverage.include=src/modules/history/** --coverage.reporter=text --coverage.thresholds.lines=100 --coverage.thresholds.branches=100 --coverage.thresholds.functions=100 --coverage.thresholds.statements=100
npm run build
```

Na pasta `backend`, com Xdebug habilitado em modo de cobertura e a migration aplicada em `conviva_db`:

```powershell
php vendor/phpunit/phpunit/phpunit --filter 'History(Test|ExportWriterTest)' --coverage-html coverage-history
```

Consulte `coverage-history/Modules/History/index.html` para a cobertura do módulo PHP. Os testes de API usam transações com rollback.
