<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Asset;

trait ValidatesCompany
{
    protected function assertSameCompany($model): void
    {
        $user = auth()->user();

        $company = $model->company ?? $model->asset?->company;

        if (! $company || ! $user->canAccessCompany($company)) {
            abort(403);
        }
    }
}