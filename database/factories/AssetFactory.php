<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Company;
use App\Models\AssetType;
use App\Models\Asset;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Asset>
 */
class AssetFactory extends Factory
{
    protected $model = Asset::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'asset_type_id' => AssetType::factory(),
            'name' => $this->faker->word(),
        ];
    }

    public function forCompany(Company $company)
    {
        return $this->state(fn () => [
            'company_id' => $company->id,
            'asset_type_id' => AssetType::factory()->create([
                'company_id' => $company->id,
            ])->id,
        ]);
    }
}
