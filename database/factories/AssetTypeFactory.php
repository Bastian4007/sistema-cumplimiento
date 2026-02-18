<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Company;
use App\Models\AssetType;


/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AssetType>
 */
class AssetTypeFactory extends Factory
{
    protected $model = AssetType::class;

    public function definition(): array
    {
        $warning = $this->faker->numberBetween(10, 30);
        $danger = $this->faker->numberBetween(1, $warning - 1);

        return [
            'company_id' => Company::factory(),
            'name' => $this->faker->word(),
            'priority_level' => $this->faker->numberBetween(1, 5),
            'warning_days' => $warning,
            'danger_days' => $danger,
        ];
    }
}
