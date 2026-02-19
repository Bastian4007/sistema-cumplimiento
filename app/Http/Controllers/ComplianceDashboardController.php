<?php

namespace App\Http\Controllers;

use App\Application\Compliance\ComplianceDashboardService;

final class ComplianceDashboardController extends Controller
{
    public function me(ComplianceDashboardService $service)
    {
        return response()->json(
            $service->metricsForCompany(30)
        );
    }
}
