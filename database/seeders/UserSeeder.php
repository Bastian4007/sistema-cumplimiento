<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'tester.alcom@example.com'],
            [
                'name' => 'Tester ALCOM',
                'password' => Hash::make('password'),
                'company_id' => 2,
                'role_id' => 1,
            ]
        );
    }
}
