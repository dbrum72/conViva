# E01 — acesso, entrada e interface

Entrega de 18/09/2026. O contexto vigente continua em `docs/CONTEXT.MD`; o baseline E00 permanece como registro histórico. O documento de conversão foi removido por solicitação do usuário e suas referências foram substituídas.

## Funcionalidades entregues

- Login com link de recuperação e mensagem de sessão expirada.
- Solicitação de recuperação e redefinição de senha com páginas próprias, estado de envio, erro de validação e contagem regressiva após limite de tentativas.
- Cadastro encaminha para Meus assistidos. A seleção de grupo explica a sequência de início e a criação de grupo abre o cadastro do assistido.
- Após cadastrar assistido, a interface orienta convidar a rede e registrar o primeiro cuidado.
- Convite exibe assistido, perfil, áreas e leitura/edição. Convite indisponível orienta solicitar outro; falha de conexão oferece nova tentativa.
- Aceite de convite passou a usar Pinia entre a página e o serviço Axios.
- Formulário de registros extraído para `CareEntryDialog.vue`; execução própria em `CareExecutionDialog.vue`.
- Cadastro do assistido, execução, exclusão de documento e solicitação de cancelamento usam os diálogos existentes, com foco, Escape, restauração de foco e erro visível.
- Listas de cuidados, agenda, documentos/assistidos, despesas e notificações distinguem carregamento e erro; as páginas principais oferecem nova tentativa. A ausência de registros de uma área não orienta observadores a criar conteúdo.
- Respostas atrasadas de contexto e identidade não restauram permissões/dados após troca de grupo ou encerramento de sessão. Uma resposta 401 de token antigo não encerra uma sessão nova.

## Recuperação e sessão

Endpoints públicos, limitados por endereço/IP:

| Método | Rota | Comportamento |
| --- | --- | --- |
| POST | `/api/auth/forgot-password` | Resposta genérica; envia instruções quando aplicável |
| POST | `/api/auth/reset-password` | Valida e consome o token; redefine a senha |

Utiliza o broker de senhas do Laravel, token armazenado como hash e prazo configurado de 60 minutos. Token consumido não pode ser reutilizado. O serviço bloqueia a linha do token na transação para serializar consumo em MySQL. A prova automatizada desta entrega verifica uso sequencial; concorrência real MySQL continua prevista no cronograma.

Endereço inexistente, solicitação recente e falha do transporte de e-mail mantêm a mesma resposta pública. Falha de transporte gera aviso operacional sem e-mail, token ou mensagem do provedor no log. O limiter tem teto por IP e por hash do endereço informado.

JWTs novos carregam uma assinatura HMAC derivada do hash da senha. Rotas autenticadas verificam essa assinatura; após redefinição, tokens anteriores deixam de funcionar, inclusive para refresh. **Sessões emitidas antes desta entrega também exigem novo login**, pois não possuem a assinatura. Nenhuma senha ou hash de senha é incluído no token.

As páginas públicas usam serviço Axios sem cabeçalhos de tenant/autenticação. Regras de recuperação ficam no serviço Laravel e FormRequests; componentes não tomam decisões de domínio.

## Persistência e configuração

A migration `2026_09_18_100000_create_password_reset_tokens_table.php` cria uma nova entidade; não é uma migration incremental de campos. Foi aplicada isoladamente em `conviva_db` após verificação do driver MySQL e nome efetivo da conexão. O banco não foi reconstruído.

O link utiliza `config('conviva.frontend_url')`, verificado localmente como `http://127.0.0.1:5186`. O transporte local encontrado é SMTP; a entrega real ao destinatário não foi verificada. Os testes automatizados usam notificações simuladas e a inspeção pública usou endereço sintético sem conta, sem solicitar envio real. Nenhum `.env` foi alterado.

## Validação

- Backend: **45 testes, 296 asserções, aprovados**. Inclui token em hash, resposta genérica, expiração, uso único, confirmação de senha, limite de tentativas, falha SMTP e rejeição de JWT antigo (cabeçalho e parâmetro de consulta).
- Frontend: **24 testes em 7 arquivos, aprovados**, cobrindo recuperação, convite, expiração de sessão, contexto atrasado e diálogos.
- Build de produção aprovado: **2.031 módulos transformados**.
- Pint nos arquivos PHP da entrega e verificação de diferenças sem erro de whitespace.
- Navegador em **390 × 844**: login, solicitação de recuperação, escolha do grupo, cadastro do assistido, novo cuidado, execução vinculada e apresentação do convite inspecionados. Convite expirado exibiu instrução para pedir outro. A rolagem do diálogo preservou o acesso aos campos e ações.
- Teste de teclado automatizado confirma contenção do foco, Escape e retorno ao acionador. Teste de falha de execução confirma diálogo aberto com mensagem para tentar novamente.

Foi criado um grupo sintético **Validação E01**, com assistido e registros de teste, no banco descartável. O convite sintético foi expirado ao concluir a inspeção. Esses registros não contêm dados pessoais reais e permanecem disponíveis para conferência local.

Limites: não se realizou auditoria completa de acessibilidade com leitor de tela, ensaio concorrente em MySQL ou entrega externa de e-mail. O aceite com criação de credenciais e a redefinição de senha foram validados por testes de aplicação; no navegador foram inspecionados os formulários e o fluxo com conta sintética já criada.

## Continuidade

E01 não altera as regras de aceite compartilhado, rateio ou revisão cadastral. Próxima entrega: **E02 — central de decisões e cadastro compartilhado**, conforme `docs/CRONOGRAMA.md`.
