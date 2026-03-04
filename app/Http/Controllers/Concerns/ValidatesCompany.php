<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Asset;

trait ValidatesCompany
{
    protected function assertSameCompany(Asset $asset): void
    {
        if ((int) $asset->company_id !== (int) auth()->user()->company_id) {
            abort(403, 'Not allowed.');
        }
    }
}