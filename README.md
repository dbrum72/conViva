# conViva

Aplicação de cuidados compartilhados para crianças e adolescentes, pessoas adultas sob cuidados e pets. Base Laravel + Vue adaptada do projeto local legalis, com estado compartilhado em Pinia e banco MySQL exclusivo `conviva_db`.

## Executar localmente

Em `backend`:

```powershell
composer install
php artisan migrate --seed
php artisan serve --host=127.0.0.1 --port=8010
```

Em `frontend`:

```powershell
npm install
npm run dev
```

Interface: http://127.0.0.1:5186. API: http://127.0.0.1:8010/api. Crie sua conta em `/register`; o cadastro cria o primeiro grupo e o perfil de responsável. O menu “Trocar ou criar grupo” permite criar grupos adicionais.

## Recursos

- Grupos independentes, cuidadores, convites, perfis e suspensão de membros.
- Assistidos dos tipos criança/adolescente, adulto sob cuidados e pet.
- Agenda, tarefas, diário de cuidados, alimentação, medicamentos e vacinas.
- Documentos privados com autorização verificada em cada download.
- Despesas em centavos, rateio por membro e registro de pagamento das parcelas (sem movimentação bancária).
- Notificações internas dos registros de cuidados.
- Acesso por assistido e por área: rotina, saúde, documentos e despesas; leitura/edição e expiração.

O responsável acessa os assistidos que cadastrou ou aos quais foi vinculado; não tem autoridade unilateral sobre outro responsável. Cuidadores são terceiros contratados/autorizados; observadores apenas visualizam as áreas concedidas. Consulte `CONVERSAO.md` para decisões e referências de mercado.

## Banco e e-mail

O banco `conviva_db` foi reconstruído pelas migrations. Não foram importados dados pessoais do legalis. `backend/tools/rebuild-conviva.php` é uma ferramenta **destrutiva**, limitada a esse nome de banco; Nesta fase de projeto, o usuário autorizou recriar esse banco descartável. Altere diretamente as migrations de criação, sem incrementais para campos; execute `php backend/tools/rebuild-conviva.php` na raiz para aplicar mudanças a um banco já existente.

No ambiente local, o transporte de e-mail está em `log`: convites são gerados, mas a mensagem fica em `backend/storage/logs/laravel.log`. O envio real depende da configuração de SMTP. Notificações internas não dependem de SMTP. Frequência de medicamentos é um registro descritivo; não há geração automática de doses nem notificações push agendadas.

## Validação

```powershell
# Na raiz (PDO SQLite habilitado somente para testes em memória)
php -d extension=pdo_sqlite backend/vendor/phpunit/phpunit/phpunit -c backend/phpunit.xml
# Em frontend
npm test
npm run build
```

Os testes verificam isolamento, restrições de área, revogação/expiração, observador, documentos, rateio, criação de grupos e convites. O manifesto `conversion-source-manifest.json` registra hashes dos arquivos consultados e copiados da base; legalis permanece somente leitura.


## Decisões compartilhadas

- Cada pessoa controla os próprios registros e documentos. Nenhum perfil pode sobrescrever registros alheios ou registrar pagamento por outro participante.
- Tarefas, compromissos, alimentação, medicamentos e vacinas exigem aceite dos demais responsáveis vinculados ao assistido. Prestadores designados, participantes de rateios e outros afetados selecionados também precisam aceitar.
- Uma nova proposta só entra na agenda e gera parcelas após todos os aceites. Recusa exige motivo. Silêncio não equivale a aceite.
- Alterar ou cancelar algo acordado gera nova versão: a anterior continua válida até todos aceitarem. Recusa mantém o acordo anterior. Retirar uma proposta não apaga votos nem justificativas.
- Cancelamentos mantêm o histórico. Documentos só podem ser excluídos pelo autor. Pagamentos impedem alteração/cancelamento da despesa.
- O cuidador designado registra sua execução como um registro próprio, vinculado à tarefa original, sem sobrescrever o solicitante.
- Não é possível remover, suspender ou rebaixar outro responsável unilateralmente. Cadastro compartilhado do assistido não pode ser alterado/arquivado unilateralmente; fluxo de revisão desses dados cadastrais permanece bloqueado.
- O esquema de decisões está consolidado na migration de criação das tabelas de cuidados. A reconstrução elimina os dados anteriores e inicia diretamente com os perfis atuais, sem conversão de perfis antigos.

Validação desta etapa: 24 testes de backend (117 asserções), 10 testes de frontend e build de produção.

Na gestão de membros, um responsável pode alterar a própria função ou desativar o próprio vínculo; nunca o de outro responsável. A interface oculta ações não permitidas e atualiza o contexto após alterações próprias. Validação: 26 testes de backend, 131 asserções; build aprovado.

## Convites vinculados a assistidos

Cada grupo admite um único assistido, inclusive quando arquivado, com unicidade garantida no banco. Um responsável pode participar de vários grupos; participantes, permissões e cuidados são independentes em cada um. Para outro filho, pessoa ou pet, crie outro grupo.

O convite inclui somente o assistido do grupo atual. Para cuidadores e observadores, selecione as áreas autorizadas. O aceite cria o vínculo ao grupo e ao assistido na mesma transação. Responsáveis recebem acesso completo a esse assistido; observadores nunca recebem edição. A autoridade do remetente é revalidada no aceite.

Validação: 34 testes de backend / 209 asserções, 10 testes de frontend e build aprovados.

Após o login, a tela Meus assistidos reúne os assistidos autorizados de todos os grupos ativos, com o perfil específico em cada grupo. Ao selecionar um assistido, o contexto e as permissões são recarregados antes da navegação e os dados do grupo anterior são limpos. A consulta GET /api/groups valida vínculos ativos e concessões não expiradas em cada grupo. Validação: 35 testes de backend (227 asserções), 12 testes de frontend e build aprovados.
