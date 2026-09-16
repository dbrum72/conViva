# Backend conViva

API Laravel com autenticação JWT, MySQL, isolamento por grupo e autorização por assistido/área. Regras de acesso ficam em `app/Services/Care/AccessControl.php`; registros e rateio em `CareRecords.php`.

Execute `composer install`, `php artisan migrate --seed` e `php artisan serve --host=127.0.0.1 --port=8010`.

Testes isolados em SQLite na memória: `php -d extension=pdo_sqlite vendor/phpunit/phpunit/phpunit`. O banco de desenvolvimento é `conviva_db`; nunca usar bancos do legalis.

O transporte de e-mail local é log. Consulte o README da raiz para detalhes de configuração e operação.
