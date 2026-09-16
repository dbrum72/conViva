<?php

namespace Database\Seeders;

use App\Support\Organizations\OrganizationRoleDefinitions;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        foreach (OrganizationRoleDefinitions::permissions() as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'api']);
        }
    }
}
