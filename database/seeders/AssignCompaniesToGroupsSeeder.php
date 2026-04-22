<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Company;
use App\Models\Group;

class AssignCompaniesToGroupsSeeder extends Seeder
{
    public function run(): void
    {
        $vigia = Group::where('slug', 'vigia')->firstOrFail();
        $daval = Group::where('slug', 'daval')->firstOrFail();

        Company::where('name', 'DAVAL')->update([
            'group_id' => $daval->id,
        ]);

        Company::whereNull('group_id')->update([
            'group_id' => $vigia->id,
        ]);
    }
}