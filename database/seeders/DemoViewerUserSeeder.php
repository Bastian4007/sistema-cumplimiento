<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class DemoViewerUserSeeder extends Seeder
{
    public function run(): void
    {
        // Ajusta estos campos a tu tabla users (role/type/etc.)
        User::updateOrCreate(
            ['email' => 'viewer@demo.com'],
            [
                'name' => 'Demo Viewer',
                'password' => Hash::make('password'),
                'company_id' => 1, // <-- pon el ID real de tu company
                'role' => 'viewer', // <-- o el campo que uses
            ]
        );
    }
}