<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAssetRequest;
use App\Http\Requests\UpdateAssetRequest;
use App\Models\Asset;
use App\Models\AssetType;
use App\Models\User;
use Illuminate\Http\Request;

class AssetController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Asset::class, 'asset');
    }

    public function index(Request $request)
    {
        $assets = Asset::query()
            ->where('company_id', $request->user()->company_id)
            ->with(['assetType', 'responsible'])
            ->latest()
            ->paginate(15);

        return view('assets.index', compact('assets'));
    }

    public function create(Request $request)
    {
        $assetTypes = AssetType::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        $responsibles = User::query()
            ->where('company_id', $request->user()->company_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('assets.create', compact('assetTypes', 'responsibles'));
    }

    public function store(StoreAssetRequest $request)
    {
        $asset = Asset::create([
            'company_id' => $request->user()->company_id, // 🔒
            ...$request->validated(),
        ]);

        return redirect()
            ->route('assets.show', $asset)
            ->with('status', 'Activo creado.');
    }

    public function show(Asset $asset)
    {
        $asset->load(['assetType', 'responsible']);
        return view('assets.show', compact('asset'));
    }

    public function edit(Request $request, Asset $asset)
    {
        $assetTypes = AssetType::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        $responsibles = User::query()
            ->where('company_id', $request->user()->company_id)
            ->orderBy('name')
            ->get(['id', 'name']);

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
}
