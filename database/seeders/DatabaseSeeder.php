<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Company;
use App\Models\Role;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RoleSeeder::class);

        $company = Company::factory()->create([
            'name' => 'Empresa Demo',
        ]);

        $operativeRole = Role::where('slug', 'operative')->firstOrFail();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'company_id' => $company->id,
            'role_id' => $operativeRole->id,
            'password' => bcrypt('password'),
        ]);
    }
}
