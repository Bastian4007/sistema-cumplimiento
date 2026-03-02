<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAssetRequest;
use App\Http\Requests\UpdateAssetRequest;
use App\Models\Asset;
use App\Models\AssetType;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AssetController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Asset::class, 'asset');
    }

    public function index()
    {
        $companyId = auth()->user()->company_id;

        $status = request('status', 'all'); 

        $query = \App\Models\Asset::query()
            ->where('company_id', $companyId)
            ->with(['type', 'responsibleUser', 'creator']);

        if ($status === 'active') {
            $query->where('status', 'active');
        } elseif ($status === 'inactive') {
            $query->where('status', 'inactive');
        }

        $assets = $query->latest()->paginate(15)->withQueryString();

        return view('assets.index', compact('assets', 'status'));
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

        return view('assets.create', compact('assetTypes', 'responsibles'));
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

        app(\App\Application\Compliance\AssignDefaultRequirementsToAsset::class)
            ->handle($asset);

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

        $asset->load([
            'assetType',
            'responsible',
            'requirements' => function ($q) {
                $q->with('template')
                ->withCount([
                    'tasks as tasks_total',
                    'tasks as tasks_done' => fn ($t) => $t->whereNotNull('completed_at'),
                ])
                ->orderByRaw("CASE WHEN due_date IS NULL THEN 1 ELSE 0 END")
                ->orderBy('due_date');
            },
        ]);

        return view('assets.show', compact('asset'));
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

        return view('assets.edit', compact('asset', 'assetTypes', 'responsibles'));
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
}
