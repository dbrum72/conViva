# E02 — Central de decisões e revisão cadastral

Implementação de 27/09/2026. Contratos anteriores preservados em `BASELINE-E00.md` e `ENTREGA-E01.md`; regras vigentes em `CONTEXT.MD`.

## Entrega

A central reúne cuidados e revisões cadastrais do grupo selecionado, com filtros de minhas decisões pendentes, propostas enviadas, situação e tipo. A paginação ocorre no servidor, depois do filtro de autorização. Cada detalhe mostra autor, participantes exigidos, votos, justificativas, datas, bloqueios e comparação com a versão vigente no momento da proposta.

Notificações novas de proposta, decisão e retirada abrem diretamente o detalhe autorizado. As notificações anteriores continuam abrindo o assistido. O acesso é novamente verificado ao abrir a proposta; conhecer um ID não permite consultar outra área ou grupo.

O responsável autor pode propor revisão ou arquivamento do cadastro pela tela Assistido. A submissão representa a manifestação do autor; os demais responsáveis vinculados recebem votos individuais. Sem outros responsáveis, a aplicação é imediata. Enquanto houver votos pendentes, o cadastro permanece vigente. Recusa motivada e retirada preservam cadastro, proposta e votos. As rotas antigas de alteração direta continuam bloqueando mudanças unilaterais em cadastro compartilhado e também recusam mudanças durante revisão pendente.

## Participantes e concorrência

Cada nova proposta guarda a lista de responsáveis existente na submissão. Ela não recebe participantes adicionais nem perde votos automaticamente. Se a lista atual divergir, a proposta fica bloqueada para aceite; o autor com acesso ainda pode retirá-la. Uma nova proposta considera a rede atual, mantendo as regras de participação de pessoas afetadas pelo acordo anterior. A restauração exata da rede e dos acessos pode remover o bloqueio; não há invalidação permanente de uma versão por uma alteração temporária de vínculo.

Perda de acesso de qualquer participante exigido, inclusive do autor ou de quem já aceitou, impede aplicação. Expiração, suspensão e perfil são revalidados no servidor. Quem ainda pode decidir pode recusar com motivo mesmo diante de um bloqueio; silêncio nunca vira aceite.

As transações bloqueiam grupo, assistido e proposta. Alterações de perfil/situação de membros, aceite de convites e concessão/revogação de acesso usam a mesma ordem de bloqueio do grupo. Uma segunda resposta ao mesmo voto ou a uma proposta encerrada retorna 409. Revisões cadastrais exigem a versão conhecida; formulários de cuidado enviam a revisão vigente para detectar edição desatualizada. Não há alteração automática de acordos já aplicados quando alguém entra ou sai. Cancelamentos novos incluem os responsáveis atuais dos tipos de cuidado que exigem aceite compartilhado.

Concluir, executar ou pagar um cuidado continua indisponível durante proposta pendente, embora o acordo anterior permaneça vigente. A interface agora informa essa distinção.

## Persistência e arquitetura

Nova migration de criação: `2026_09_27_100000_create_care_profile_proposals_table.php`. Cria entidades próprias para propostas e decisões cadastrais, além dos vínculos entre notificações e propostas. Não adiciona campos incrementais às tabelas existentes. Comparações e participantes de cuidados usam o JSON de payload existente. Payload e participantes permanecem imutáveis; somente situação e votos evoluem.

Fluxo: componentes Vue → Pinia → Axios → controllers → serviços Laravel → Eloquent. Autorização, bloqueios, diferenças e capacidades são calculados no backend. Troca de grupo ou sessão invalida respostas atrasadas da central. Propostas antigas sem cópia do conteúdo anterior são identificadas na interface, sem inventar uma comparação histórica.

Endpoints novos:

- `GET /api/decisions`: `scope=mine|sent|all`, `status`, `type=entry|profile`, `page`, `per_page` (máximo 50).
- `GET /api/decisions/{type}/{proposal}`.
- `POST /api/recipients/{recipient}/profile-proposals`: operação `save|archive`, versão conhecida e campos do cadastro para alteração.
- `POST /api/recipients/{recipient}/profile-proposals/{proposal}/decision`.
- `POST /api/recipients/{recipient}/profile-proposals/{proposal}/withdraw`.

## Validação e ambiente

A configuração de testes foi alinhada a MySQL por orientação do usuário. **Os testes recriam as tabelas de `conviva_db`**, autorizado como descartável nesta sessão, e não devem ser executados sobre dados a preservar. `Tests/TestCase.php` verifica ambiente de teste, driver, nome configurado e `SELECT DATABASE()` antes de permitir `RefreshDatabase`/`DatabaseMigrations`. URLs alternativas são rejeitadas. Nenhum `.env` foi alterado. Não foi criado banco de testes adicional.

A extensão GD foi instalada pelo usuário. Os 50 testes anteriores passaram em MySQL com 363 asserções. A entrega acrescenta testes de revisão, recusa, retirada, isolamento, paginação, novos responsáveis, perda de acesso, bloqueio após voto anterior e concorrência real com conexões independentes. Os testes Vue cobrem comparação, recusa, bloqueio, conflito e respostas atrasadas.

Resultados:

- Suíte completa backend: **61 testes / 463 asserções aprovados** em MySQL.
- Após o ajuste final de comparação de campos omitidos e revalidação na aplicação: **11 testes / 101 asserções aprovados**, incluindo os dois cenários concorrentes, sem testes ignorados.
- Frontend: **36 testes aprovados em 10 arquivos**.
- Build de produção aprovado: **2.040 módulos**.
- Pint e `git diff --check` aprovados.
- `php backend/tools/rebuild-conviva.php` concluído após os testes: todas as migrations, inclusive a nova, e seeder aplicados em `conviva_db`. O banco foi reinicializado; contas e registros anteriores não foram preservados, conforme autorização expressa para usar e reconstruir esse banco descartável. O seeder atual cria permissões, não contas de acesso.

E02 concluída. Próxima etapa sequencial: **E03 — ficha e experiência por tipo de assistido**.

Limites: ensaio de concorrência exige `pcntl`; sua ausência marca os dois cenários concorrentes como ignorados. A suíte comum continua executável. Não houve inspeção visual em navegador nesta entrega, teste de carga ou validação de e-mail externo. SMTP e operação assíncrona permanecem nas etapas previstas.
