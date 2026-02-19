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
            'company_id' => Company::factory(),
            'asset_id' => Asset::factory(),
            'requirement_template_id' => RequirementTemplate::factory(),
            'type' => $this->faker->randomElement(['inspection','audit','maintenance','certification',]),
            'status' => RequirementStatus::PENDING,
            'due_date' => now()->addDays(rand(5, 120)),
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


    public function expired()
    {
        return $this->state(fn () => [
            'due_date' => now()->subDays(rand(1, 15)),
            'status' => RequirementStatus::PENDING,
        ]);
    }

    public function danger()
    {
        return $this->state(fn () => [
            'due_date' => now()->addDays(rand(1, 30)),
        ]);
    }

    public function warning()
    {
        return $this->state(fn () => [
            'due_date' => now()->addDays(rand(31, 60)),
        ]);
    }

    public function normal()
    {
        return $this->state(fn () => [
            'due_date' => now()->addDays(rand(61, 120)),
        ]);
    }

    public function completed()
    {
        return $this->state(fn () => [
            'status' => RequirementStatus::COMPLETED,
            'completed_at' => now(),
        ]);
    }
}
