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

        if (empty($data['code'])) {
            $namePart = Str::upper(Str::substr(Str::slug($data['name'], ''), 0, 10)); 

            $type = \App\Models\AssetType::find($data['asset_type_id']);
            $typePart = $type?->code
                ? Str::upper(Str::slug($type->code, ''))         
                : Str::upper(Str::substr(Str::slug($type?->name ?? 'TIPO', ''), 0, 6)); 

            $prefix = "{$namePart}-{$typePart}-";

            $last = Asset::query()
                ->where('company_id', $companyId)
                ->where('code', 'like', $prefix.'%')
                ->orderBy('code', 'desc')
                ->value('code');

            $nextNumber = 1;
            if ($last) {
                $lastNumber = (int) Str::afterLast($last, '-');
                $nextNumber = $lastNumber + 1;
            }

            $data['code'] = $prefix . str_pad((string)$nextNumber, 3, '0', STR_PAD_LEFT);
        }

        $asset = Asset::create([
            'company_id' => $companyId,
            ...$data,
        ]);

        return redirect()
            ->route('assets.show', $asset)
            ->with('status', 'Activo creado.');
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

        $requirements = $asset->requirements()
            ->when($scope === 'project', fn($q) => $q->where('compliance_scope', 'project'))
            ->when($scope === 'operation', fn($q) => $q->where('compliance_scope', 'operation'))
            ->get();

        $asset->load([
            'assetType',
            'responsible',
            'requirements' => function ($q) {
                $q->with('template')
                ->withCount([
                    'tasks as tasks_total',
                    'tasks as tasks_done' => fn ($t) => $t->whereNotNull('completed_at'),
                ]);
            },
        ]);

        $navContext = [
            'asset' => $asset,
            'requirement' => null,
            'task' => null,
            'documentSection' => false,
        ];

        return view('assets.show', compact('asset', 'navContext', 'scope', 'requirements'));
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
