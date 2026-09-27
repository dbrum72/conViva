# Recibos e comprovantes de pagamento

Entrega de 27/09/2026, antecipando por solicitação do usuário o item de comprovantes da E08. As demais tarefas da E08 continuam pendentes.

Ao clicar em **Registrar pagamento**, o usuário vê a despesa, o valor da própria parcela e um seletor `AppFileUpload`. O arquivo é opcional: PDF, JPG, PNG ou WebP, até 20 MB, validado também no Laravel. O pagamento pode continuar sendo registrado sem arquivo. Uma parcela paga sem comprovante oferece **Anexar comprovante** ao próprio pagador autorizado. O horário original de pagamento é preservado.

Apenas o titular da parcela pode registrar seu pagamento ou anexar seu comprovante. O envio exige edição das áreas financeira e de documentos; o download exige leitura das duas áreas, com vínculo e acesso ainda vigentes. O backend informa a capacidade de anexar e omite metadados de arquivo quando falta acesso. As telas de cuidados e despesas oferecem download autenticado. Não existe URL pública; o caminho interno não integra as respostas da API.

O arquivo é vinculado à parcela por `expense_payment_receipts`, com unicidade por parcela. Um novo envio não substitui um comprovante existente e retorna 409. A tabela tem migration própria de criação, aplicada isoladamente em `conviva_db` após verificar o driver e o banco efetivo. Não houve reconstrução do banco nem alteração de `.env`; os registros existentes foram preservados.

`ExpensePayments` concentra a transação: bloqueia grupo, assistido, despesa e parcela; revalida permissões e impede pagamento de proposta não aceita ou com alteração pendente. Falha de armazenamento impede a gravação do pagamento. Falha na persistência limpa o arquivo recém-enviado e desfaz a transação. Repetir uma chamada sem anexo não muda os horários já registrados. Pagamentos continuam bloqueando alteração/cancelamento da despesa.

API:

- `POST /api/recipients/{recipient}/entries/{entry}/shares/{share}/pay`: aceita multipart com `receipt` opcional.
- `GET /api/recipients/{recipient}/entries/{entry}/shares/{share}/receipt`: download privado, com `Cache-Control: private, no-store` e `nosniff`.

Validação: **7 testes backend / 59 asserções**, cobrindo arquivo opcional, anexo posterior, autoria, isolamento de grupos e áreas, expiração, arquivo inválido, proposta pendente e falhas de armazenamento/persistência. Esses testes usam `DatabaseTransactions` sobre o schema já aplicado, com rollback, sem recriar tabelas. Comando específico: `php artisan test --filter=ExpensePaymentReceiptTest`. A suíte backend completa continua contendo testes destrutivos e não foi reexecutada nesta entrega.

Frontend: **40 testes em 11 arquivos aprovados**, incluindo envio do arquivo pelo diálogo, manutenção da seleção em falha, limpeza ao trocar parcela e ausência de upload sem permissão. Build de produção aprovado (2.041 módulos); Pint e `git diff --check` aprovados. Não houve inspeção visual em navegador nesta entrega.
