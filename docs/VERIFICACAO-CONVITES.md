# Verificação da aceitação de convites — 30/09/2026

## Falha reproduzida e correção

O e-mail apontava para `http://127.0.0.1:5173/invitations/accept/{token}`, mas o Vite, configurado com `host: "localhost"`, escutava somente em `::1:5173` neste Windows. O navegador recebia `ERR_CONNECTION_REFUSED` antes de carregar a aplicação.

O host em `frontend/vite.config.js` foi ajustado para `127.0.0.1`, mantendo porta 5173 e `strictPort`. O servidor recarregou a configuração; tanto `127.0.0.1:5173` quanto `localhost:5173` responderam HTTP 200. O convite original abriu no navegador, apresentou cadastro, grupo, assistido e áreas concedidas, sem erros no console. Não foi submetido o aceite do destinatário real.

Os avisos de scripts bloqueados na imagem do Mailpit pertenciam ao iframe de prévia do e-mail. A causa da conexão recusada foi o endereço de escuta do frontend.

## Testes

`backend/tests/Feature/InvitationAcceptanceTest.php` usa `DatabaseTransactions` com rollback sobre o MySQL `conviva_db` existente e envio de e-mail simulado. Não recria tabelas. Cinco testes e 50 asserções aprovados:

- Emissão do convite com URL configurada e link no HTML; consulta pública, cadastro e aceite único, autenticação e acesso ao assistido.
- Conta existente sem duplicação, alteração de senha ou emissão de token de login.
- Rejeição de convites expirados, revogados e já utilizados.
- Correção de senha não confirmada sem consumir o convite.
- Perda de acesso do remetente impede aceite e desfaz o cadastro sintético.

Três testes existentes em `frontend/tests/invitation-acceptance.spec.js` aprovados: aceite pela store, nova tentativa após falha de conexão e orientação para convite expirado. Build aprovado com 2.055 módulos; Pint e verificação de whitespace aprovados. Testes frontend e build precisaram de execução autorizada fora do sandbox após `spawn EPERM`.

Comandos, nos diretórios correspondentes:

```text
backend: php artisan test --filter=InvitationAcceptanceTest
backend: php vendor/bin/pint --test tests/Feature/InvitationAcceptanceTest.php
frontend: npm test -- tests/invitation-acceptance.spec.js
frontend: npm run build
```

Nenhum `.env` foi modificado e não houve reconstrução do banco. A verificação no navegador cobriu a abertura do convite real; o aceite completo foi exercitado pelos testes automatizados com dados sintéticos. A suíte backend completa não foi executada.
