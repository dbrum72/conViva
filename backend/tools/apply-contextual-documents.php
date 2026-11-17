<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$connection = config('database.connections.mysql');
if (config('database.default') !== 'mysql' || $connection['database'] !== 'conviva_db' || ! empty($connection['url'])
    || DB::selectOne('SELECT DATABASE() AS name')->name !== 'conviva_db') {
    throw new RuntimeException('Alteração recusada: use exclusivamente MySQL conviva_db.');
}

if (! Schema::hasColumn('care_documents', 'category')) {
    Schema::table('care_documents', fn (Blueprint $table) => $table->string('category', 100)->nullable());
}
if (! Schema::hasColumn('care_documents', 'valid_until')) {
    Schema::table('care_documents', fn (Blueprint $table) => $table->date('valid_until')->nullable());
}
if (! Schema::hasColumn('care_documents', 'care_entry_id')) {
    Schema::table('care_documents', fn (Blueprint $table) => $table->foreignId('care_entry_id')->nullable());
}
if (! Schema::hasIndex('care_documents', ['care_entry_id'])) {
    Schema::table('care_documents', fn (Blueprint $table) => $table->index('care_entry_id'));
}
if (! collect(Schema::getForeignKeys('care_documents'))->contains(fn ($key) => $key['columns'] === ['care_entry_id'])) {
    Schema::table('care_documents', fn (Blueprint $table) => $table->foreign('care_entry_id')->references('id')->on('care_entries')->restrictOnDelete());
}

echo "Campos de documentos contextualizados aplicados em conviva_db.\n";
