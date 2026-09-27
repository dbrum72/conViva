# Verificação antes de E02

Verificação de 27/09/2026, antes de prosseguir com o cronograma.

## Entregas conferidas

- E00: contratos, matriz de capacidades e cenários em `BASELINE-E00.md`.
- E01: acesso, recuperação de senha, convites e interface em `ENTREGA-E01.md`.
- Ajuste posterior: avatar pessoal e navegação de grupos em `AVATAR-PESSOAL.md`.
- Regras vigentes conferidas em `../AGENTS.md` e `CONTEXT.MD`.

No momento desta conferência, E02 era a próxima etapa. A leitura inicial de `CareRecords`, `CareProposal`, das rotas de propostas e de `CareDecisions.vue` confirmou a base de versões e votos individuais. Naquele momento, faltavam as entregas previstas de central paginada, diferenças entre versões, destino de notificações, revisão cadastral compartilhada e tratamento explícito de mudanças de participantes. Esta conferência, isoladamente, não demonstrava o aceite da E02; sua implementação posterior está registrada em `ENTREGA-E02.md`.

## Baseline reproduzida nesta sessão

| Comando | Resultado |
| --- | --- |
| `cd frontend` e `npm test` | 31 testes aprovados em 9 arquivos |
| `cd frontend` e `npm run build` | Aprovado, 2.037 módulos transformados |
| `cd backend` e `php artisan test` | 50 testes com erro de infraestrutura; nenhuma asserção executada |

O PHP CLI 8.4 não dispõe de `pdo_sqlite`: todos os testes backend interromperam na preparação do SQLite em memória com `could not find driver`. Naquele momento, também não estava instalada a extensão GD usada na geração de imagens de teste. Os números históricos de testes aprovados das entregas anteriores não substituem esta verificação.

Foi tentada a instalação de `php8.4-sqlite3` e `php8.4-gd` via `sudo apt-get install`; o comando parou porque exige senha de administrador indisponível nesta sessão. Nenhum pacote foi instalado pelo comando.

## Encaminhamento inicial, superado pela resolução abaixo

Correção após orientação do usuário: a aplicação utiliza MySQL, confirmado pela configuração efetiva (`mysql`, banco `conviva_db`). SQLite é apenas a configuração histórica forçada em `backend/phpunit.xml`; sua instalação não é o encaminhamento adotado.

A sondagem de conexão MySQL no sandbox retornou código PDO 2002. A tentativa de executar a consulta fora do sandbox foi negada pelo usuário. Não foi possível verificar a disponibilidade de um banco isolado de testes.

Para retomar, validar o acesso MySQL e ajustar a suíte para um banco de testes isolado, com proteção do destino antes de `RefreshDatabase`, que recria tabelas. A configuração dos testes ainda não foi alterada. GD continua necessária aos testes atuais de avatar. Investigar eventuais falhas antes de implementar E02.

Até o encerramento desta conferência inicial, não havia alteração de código funcional, `.env`, migrations ou bancos de dados. E00 permanece como baseline histórico, sem reescrever seus resultados.

## Resolução durante a E02

O usuário confirmou a continuidade, instalou GD e autorizou explicitamente usar o MySQL `conviva_db` descartável para os testes, reconstruindo-o ao final. Não foi criado `conviva_test`. A configuração da suíte foi alinhada a essa orientação, com verificação do banco efetivo antes de recriar tabelas. Os 50 testes preexistentes passaram, com 363 asserções. A implementação e as validações seguintes estão em `ENTREGA-E02.md`.
