<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'tester.CP@example.com'],
            [
                'name' => 'Tester CAPITAL HUMANO',
                'password' => Hash::make('password'),
                'company_id' => 4,
                'role_id' => 1,
            ]
        );
    }
}
