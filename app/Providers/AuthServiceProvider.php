<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

use App\Models\Asset;
use App\Models\AssetRequirement;
use App\Models\Task;
use App\Models\TaskDocument;

use App\Policies\AssetPolicy;
use App\Policies\AssetRequirementPolicy;
use App\Policies\TaskPolicy;
use App\Policies\TaskDocumentPolicy;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Asset::class => AssetPolicy::class,
        AssetRequirement::class => AssetRequirementPolicy::class,
        Task::class => TaskPolicy::class,
        TaskDocument::class => TaskDocumentPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}