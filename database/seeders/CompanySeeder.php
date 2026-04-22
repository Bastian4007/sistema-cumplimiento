<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Company;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        $companies = [
            'ALCOM',
            'BIOMEX',
            'CAPITAL HUMANO',
            'FISCAL',
            'TI',
            'DAVAL',
            'SOM',
            'INMUEBLES',
            'MDI',
            'PROPANE',
            'SOLTRACK',
            'TRONCALNET' ,
            'Terrenos Vigia',
            'MIGAR',
            'KIWI GAS',
        ];

        foreach ($companies as $name) {
            Company::firstOrCreate(
                ['name' => trim($name)]
            );
        }
    }
}