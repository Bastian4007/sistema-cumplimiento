<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAssetRequest;
use App\Http\Requests\UpdateAssetRequest;
use App\Models\Asset;
use App\Models\AssetType;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Enums\RequirementStatus;
use App\Models\RequirementTemplate;
use App\Models\AssetRequirement;
use Illuminate\Support\Facades\DB;
use App\Enums\TaskStatus;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class AssetController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Asset::class, 'asset');
    }

    public function index(Request $request)
    {
        $companyId = $request->user()->company_id;

        $assetTypes = AssetType::query()
            ->orderBy('name')
            ->get(['id','name']);

        $query = Asset::query()
            ->where('company_id', $companyId);

        if ($request->filled('status') && in_array($request->status, ['active','inactive'], true)) {
            $query->where('status', $request->status);
        }

        if ($request->filled('asset_type_id')) {
            $query->where('asset_type_id', (int) $request->asset_type_id);
        }

        if ($request->filled('q')) {
            $q = trim($request->q);
            $query->where('name', 'like', "%{$q}%");
        }

        if ($request->filled('location')) {
            $query->whereRaw('UPPER(TRIM(location)) = ?', [strtoupper(trim($request->location))]);
        }

        $assets = $query
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $locations = Asset::where('company_id', auth()->user()->company_id)
            ->whereNotNull('location')
            ->where('location', '!=', '')
            ->distinct()
            ->orderBy('location')
            ->pluck('location');

        return view('assets.index', compact('assets', 'assetTypes', 'locations'));
    }

    public function create(Request $request)
    {
        $companyId = (int) $request->user()->company_id;

        $assetTypes = AssetType::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        $responsibles = \App\Models\User::query()
            ->where('company_id', $companyId)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
        
        $mexicoStates = [
            'Aguascalientes',
            'Baja California',
            'Baja California Sur',
            'Campeche',
            'Coahuila',
            'Colima',
            'Chiapas',
            'Chihuahua',
            'Ciudad de México',
            'Durango',
            'Guanajuato',
            'Guerrero',
            'Hidalgo',
            'Jalisco',
            'México',
            'Michoacán',
            'Morelos',
            'Nayarit',
            'Nuevo León',
            'Oaxaca',
            'Puebla',
            'Querétaro',
            'Quintana Roo',
            'San Luis Potosí',
            'Sinaloa',
            'Sonora',
            'Tabasco',
            'Tamaulipas',
            'Tlaxcala',
            'Veracruz',
            'Yucatán',
            'Zacatecas',
        ];

        return view('assets.create', compact('assetTypes', 'responsibles', 'mexicoStates'));
    }

    public function store(StoreAssetRequest $request)
    {
        $data = $request->validated();
        $companyId = (int) $request->user()->company_id;

        if (!empty($data['code'])) {
            $data['code'] = Str::upper(trim($data['code']));
        }

        if (empty($data['code'])) {
            $namePart = Str::upper(Str::substr(Str::slug($data['name'], ''), 0, 10));

            $type = AssetType::find($data['asset_type_id']);
            $typePart = $type?->code
                ? Str::upper(Str::slug($type->code, ''))
                : Str::upper(Str::substr(Str::slug($type?->name ?? 'TIPO', ''), 0, 6));

            $prefix = "{$namePart}-{$typePart}-";

            $last = Asset::query()
                ->where('company_id', $companyId)
                ->where('code', 'like', $prefix . '%')
                ->orderBy('code', 'desc')
                ->value('code');

            $nextNumber = 1;

            if ($last) {
                $lastNumber = (int) Str::afterLast($last, '-');
                $nextNumber = $lastNumber + 1;
            }

            $data['code'] = $prefix . str_pad((string) $nextNumber, 3, '0', STR_PAD_LEFT);
        }

        $asset = DB::transaction(function () use ($companyId, $data) {
            $asset = Asset::create([
                'company_id' => $companyId,
                ...$data,
            ]);

            $this->createRequirementsFromTemplates($asset);

            return $asset;
        });

        return redirect()
            ->route('assets.show', $asset)
            ->with('status', 'Activo creado.');
    }

    private function createRequirementsFromTemplates(Asset $asset): void
    {
        $templates = RequirementTemplate::query()
            ->where('asset_type_id', $asset->asset_type_id)
            ->orderBy('id')
            ->get();

        foreach ($templates as $template) {
            $requirement = AssetRequirement::firstOrCreate(
                [
                    'asset_id' => $asset->id,
                    'requirement_template_id' => $template->id,
                ],
                [
                    'company_id' => $asset->company_id,
                    'status' => RequirementStatus::PENDING,
                    'due_date' => $asset->compliance_due_date,
                    'compliance_scope' => $template->compliance_scope ?? 'project',
                    'completed_at' => null,
                    'issued_at' => null,
                    'expires_at' => null,
                    'type' => 'initial',
                    'current_document_id' => null,
                ]
            );
        }
    }

    private function generateAssetCode(int $companyId): string
    {
        $lastNumeric = \App\Models\Asset::query()
            ->where('company_id', $companyId)
            ->whereNotNull('code')
            ->orderByDesc('id')
            ->value('code');

        $n = is_numeric($lastNumeric) ? ((int)$lastNumeric + 1) : 1;

        return str_pad((string)$n, 3, '0', STR_PAD_LEFT);
    }

    public function show(Asset $asset)
    {
        $this->authorize('view', $asset);

        $scope = request()->get('scope', 'project');

        $search = trim((string) request('search'));
        $authority = trim((string) request('authority'));
        $risk = trim((string) request('risk'));
        $status = trim((string) request('status'));
        $showFilters = request()->boolean('show_filters')
            || $authority !== ''
            || $risk !== ''
            || $status !== '';

        $assetInactive = ($asset->status ?? null) === \App\Models\Asset::STATUS_INACTIVE
            || (method_exists($asset, 'isInactive') && $asset->isInactive());

        $scopeTitle = $scope === 'operation'
            ? 'Normativa de operación'
            : 'Normativa de proyecto';

        $scopeDescription = $scope === 'operation'
            ? 'Visualiza el avance, riesgo y estado de cada carpeta de cumplimiento en operación.'
            : 'Visualiza el avance, riesgo y estado de cada carpeta de cumplimiento del proyecto.';

        $requirementsQuery = AssetRequirement::query()
            ->with(['template'])
            ->where('asset_id', $asset->id)
            ->where('compliance_scope', $scope)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->whereHas('template', function ($templateQuery) use ($search) {
                        $templateQuery->where('name', 'ilike', "%{$search}%");
                    })->orWhere('type', 'ilike', "%{$search}%");
                });
            })
            ->when($authority !== '', function ($query) use ($authority) {
                $query->whereHas('template', function ($templateQuery) use ($authority) {
                    $templateQuery->where('authority', $authority);
                });
            })
            ->withCount([
                'tasks as tasks_total',
                'tasks as tasks_done' => fn ($q) => $q->where('status', TaskStatus::COMPLETED),
            ])
            ->orderBy('id');

        $requirementsCollection = $requirementsQuery->get()->map(function ($requirement) {
            $expiresAt = $requirement->expires_at ?? $requirement->due_date;

            $riskLevel = 'normal';

            if ($expiresAt) {
                $daysToExpire = now()->startOfDay()->diffInDays($expiresAt->startOfDay(), false);

                if ($daysToExpire < 0) {
                    $riskLevel = 'danger';
                } elseif ($daysToExpire <= 30) {
                    $riskLevel = 'warning';
                }
            }

            $tasksTotal = (int) ($requirement->tasks_total ?? 0);
            $tasksDone = (int) ($requirement->tasks_done ?? 0);

            $hasOfficialDocument = ! is_null($requirement->current_document_id);

            $computedStatus = $requirement->status?->value ?? $requirement->status ?? 'pending';

            if (! $hasOfficialDocument) {
                $computedStatus = 'missing_document';
            } elseif ($riskLevel === 'danger') {
                $computedStatus = 'expired';
            } elseif ($tasksTotal > 0 && $tasksDone === $tasksTotal) {
                $computedStatus = 'completed';
            } elseif ($tasksDone > 0 && $tasksDone < $tasksTotal) {
                $computedStatus = 'in_progress';
            } else {
                $computedStatus = $computedStatus ?: 'pending';
            }

            $progress = 0;

            if ($hasOfficialDocument) {
                if ($tasksTotal > 0) {
                    $progress = (int) round(($tasksDone / max($tasksTotal, 1)) * 100);
                } else {
                    $progress = 100;
                }
            } else {
                if ($tasksTotal > 0 && $tasksDone > 0) {
                    $progress = min((int) round(($tasksDone / max($tasksTotal, 1)) * 100), 80);
                } else {
                    $progress = 0;
                }
            }

            $requirement->risk_level = $riskLevel;
            $requirement->computed_status = $computedStatus;
            $requirement->computed_progress = $progress;
            $requirement->has_official_document = $hasOfficialDocument;

            return $requirement;
        });

        $requirementsCollection = $requirementsCollection
            ->when($risk !== '', function (Collection $collection) use ($risk) {
                return $collection->filter(fn ($item) => ($item->risk_level ?? '') === $risk);
            })
            ->when($status !== '', function (Collection $collection) use ($status) {
                return $collection->filter(fn ($item) => ($item->computed_status ?? '') === $status);
            })
            ->values();

        $perPage = 10;
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $currentItems = $requirementsCollection->slice(($currentPage - 1) * $perPage, $perPage)->values();

        $requirements = new LengthAwarePaginator(
            $currentItems,
            $requirementsCollection->count(),
            $perPage,
            $currentPage,
            [
                'path' => request()->url(),
                'query' => request()->query(),
            ]
        );

        $authorities = RequirementTemplate::query()
            ->where('asset_type_id', $asset->asset_type_id)
            ->whereNotNull('authority')
            ->where('authority', '!=', '')
            ->distinct()
            ->orderBy('authority')
            ->pluck('authority');

        $navContext = [
            'asset' => $asset,
            'requirement' => null,
            'task' => null,
            'documentSection' => false,
        ];

        return view('assets.show', compact(
            'asset',
            'requirements',
            'scope',
            'scopeTitle',
            'scopeDescription',
            'assetInactive',
            'navContext',
            'search',
            'authority',
            'risk',
            'status',
            'authorities',
            'showFilters'
        ));
    }

    public function edit(Request $request, Asset $asset)
    {
        abort_unless($asset->company_id === (int) $request->user()->company_id, 404);

        $asset->load(['assetType', 'responsible']);

        $assetTypes = AssetType::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        $responsibles = User::query()
            ->where('company_id', $request->user()->company_id)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $mexicoStates = [
            'Aguascalientes',
            'Baja California',
            'Baja California Sur',
            'Campeche',
            'Chiapas',
            'Chihuahua',
            'Ciudad de México',
            'Coahuila',
            'Colima',
            'Durango',
            'Estado de México',
            'Guanajuato',
            'Guerrero',
            'Hidalgo',
            'Jalisco',
            'Michoacán',
            'Morelos',
            'Nayarit',
            'Nuevo León',
            'Oaxaca',
            'Puebla',
            'Querétaro',
            'Quintana Roo',
            'San Luis Potosí',
            'Sinaloa',
            'Sonora',
            'Tabasco',
            'Tamaulipas',
            'Tlaxcala',
            'Veracruz',
            'Yucatán',
            'Zacatecas',
        ];

        return view('assets.edit', compact('asset', 'responsibles', 'assetTypes', 'mexicoStates'));
    }

    public function update(UpdateAssetRequest $request, Asset $asset)
    {
        $asset->update($request->validated());

        return redirect()
            ->route('assets.show', $asset)
            ->with('status', 'Activo actualizado.');
    }

    public function destroy(Asset $asset)
    {
        // Recomendación MVP: NO borrar si ya tiene requirements/obligations
        if ($asset->requirements()->exists() || $asset->obligations()->exists()) {
            return back()->with('status', 'No se puede eliminar: el activo ya tiene obligaciones/requerimientos.');
        }

        $asset->delete();

        return redirect()
            ->route('assets.index')
            ->with('status', 'Activo eliminado.');
    }

    public function deactivate(Asset $asset)
    {
        abort_unless($asset->company_id === auth()->user()->company_id, 403);

        $asset->update(['status' => Asset::STATUS_INACTIVE]);

        return redirect()->route('assets.index')->with('success', 'Activo desactivado.');
    }

    public function activate(Asset $asset)
    {
        abort_unless($asset->company_id === auth()->user()->company_id, 403);

        $asset->update(['status' => Asset::STATUS_ACTIVE]);

        return back()->with('success', 'Activo reactivado.');
    }

    protected function prepareForValidation()
    {
        if ($this->has('location')) {
            $this->merge([
                'location' => strtoupper(trim($this->location)),
            ]);
        }
    }
}
