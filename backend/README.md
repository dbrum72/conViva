# Backend conViva

API Laravel com autenticação JWT, MySQL, isolamento por grupo e autorização por assistido/área. No módulo `app/Modules/Care`, `Services/AccessControl.php` controla o acesso e `Services/CareRecords.php` trata registros e rateios.

Execute `composer install`, `php artisan migrate --seed` e `php artisan serve --host=127.0.0.1 --port=8000`.

**Testes destrutivos no MySQL `conviva_db`:** `php artisan test`. O usuário autorizou este banco como descartável; a suíte recria tabelas e valida o destino antes de executar. Requer PDO MySQL e GD; os cenários concorrentes também usam `pcntl`. Depois, na raiz do projeto, execute `php backend/tools/rebuild-conviva.php` para reconstruir o banco com migrations e seeder. Não executar sobre dados que precisem ser preservados. Consulte `docs/ENTREGA-E02.md` na raiz.

O transporte de e-mail local é log. Consulte o README da raiz para detalhes de configuração e operação.
