<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Enums\RequirementStatus;
use App\Models\Company;
use App\Models\Asset;
use App\Models\RequirementTemplate;
use App\Models\AssetRequirement;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AssetRequirement>
 */
class AssetRequirementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = AssetRequirement::class;
    public function definition(): array
    {
        return [
            'asset_id' => Asset::factory(),
            'requirement_template_id' => RequirementTemplate::factory(),
            'status' => RequirementStatus::PENDING,
            'due_date' => now()->addDays(rand(5, 30)),
        ];
    }
    
    public function forCompany(Company $company)
    {
        return $this->state(fn () => [
            'company_id' => $company->id,
            'asset_id' => Asset::factory()->for($company),
            'requirement_template_id' => RequirementTemplate::factory()->for($company),
        ]);
    }
}
