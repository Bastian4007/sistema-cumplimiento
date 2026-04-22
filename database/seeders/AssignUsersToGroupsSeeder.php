<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AssignUsersToGroupsSeeder extends Seeder
{
    public function run(): void
    {
        User::with(['company', 'role'])->get()->each(function (User $user) {
            if (! $user->company || ! $user->company->group_id) {
                return;
            }

            $user->group_id = $user->company->group_id;
            $user->scope_level = $user->role?->slug === 'admin'
                ? 'group'
                : 'company';

            $user->save();
        });
    }
}