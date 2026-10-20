<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeliveryZone;
use App\Models\Region;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class DeliveryZoneController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $ownRegionId = $user->region_id;
        $regions = Region::orderBy('name')->get();
        $zones = DeliveryZone::with('region')
            ->when($user->role !== 'super_admin', function ($q) use ($ownRegionId) {
                $q->where('region_id', $ownRegionId);
            })
            ->orderBy('region_id')
            ->orderBy('distance_km')
            ->get();

        return view('dashboard.admin.delivery-zones.index', compact('zones', 'regions', 'ownRegionId'));
    }

    public function create()
    {
        $user = Auth::user();
        $ownRegionId = $user->region_id;
        $regions = $user->role === 'super_admin' ? Region::orderBy('name')->get() : Region::where('id', $ownRegionId)->get();

        return view('dashboard.admin.delivery-zones.create', compact('regions'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $ownRegionId = $user->region_id;

        $validated = $request->validate([
            'region_id' => ['required', 'exists:regions,id'],
            'area_name' => ['required', 'string', 'max:255'],
            'landmark_keyword' => ['nullable', 'string', 'max:500'],
            'distance_km' => ['required', 'numeric', 'min:0.01'],
            'ongkir' => ['required', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if ($user->role !== 'super_admin' && $validated['region_id'] != $ownRegionId) {
            abort(403, 'AKSES DITOLAK');
        }

        DeliveryZone::create([
            'region_id' => $validated['region_id'],
            'area_name' => $validated['area_name'],
            'landmark_keyword' => $validated['landmark_keyword'] ?? null,
            'distance_km' => $validated['distance_km'],
            'ongkir' => $validated['ongkir'],
            'notes' => $validated['notes'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        $this->invalidateCache($validated['region_id']);

        return redirect()->route('admin.delivery-zones.index')->with('success', 'Zona pengiriman berhasil ditambahkan.');
    }

    public function edit(DeliveryZone $deliveryZone)
    {
        $user = Auth::user();
        $ownRegionId = $user->region_id;
        if ($user->role !== 'super_admin' && $deliveryZone->region_id != $ownRegionId) {
            abort(403, 'AKSES DITOLAK');
        }

        $regions = $user->role === 'super_admin' ? Region::orderBy('name')->get() : Region::where('id', $ownRegionId)->get();

        return view('dashboard.admin.delivery-zones.edit', compact('deliveryZone', 'regions'));
    }

    public function update(Request $request, DeliveryZone $deliveryZone)
    {
        $user = Auth::user();
        $ownRegionId = $user->region_id;

        $validated = $request->validate([
            'region_id' => ['required', 'exists:regions,id'],
            'area_name' => ['required', 'string', 'max:255'],
            'landmark_keyword' => ['nullable', 'string', 'max:500'],
            'distance_km' => ['required', 'numeric', 'min:0.01'],
            'ongkir' => ['required', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if ($user->role !== 'super_admin' && $deliveryZone->region_id != $ownRegionId) {
            abort(403, 'AKSES DITOLAK');
        }
        if ($user->role !== 'super_admin' && $validated['region_id'] != $ownRegionId) {
            abort(403, 'AKSES DITOLAK');
        }

        $deliveryZone->update([
            'region_id' => $validated['region_id'],
            'area_name' => $validated['area_name'],
            'landmark_keyword' => $validated['landmark_keyword'] ?? null,
            'distance_km' => $validated['distance_km'],
            'ongkir' => $validated['ongkir'],
            'notes' => $validated['notes'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        $this->invalidateCache($deliveryZone->region_id);
        if (isset($validated['region_id']) && $validated['region_id'] != $deliveryZone->region_id) {
            $this->invalidateCache($validated['region_id']);
        }

        return redirect()->route('admin.delivery-zones.index')->with('success', 'Zona pengiriman berhasil diperbarui.');
    }

    public function destroy(DeliveryZone $deliveryZone)
    {
        $user = Auth::user();
        $ownRegionId = $user->region_id;
        if ($user->role !== 'super_admin' && $deliveryZone->region_id != $ownRegionId) {
            abort(403, 'AKSES DITOLAK');
        }

        $regionId = $deliveryZone->region_id;
        $deliveryZone->delete();
        $this->invalidateCache($regionId);

        return redirect()->route('admin.delivery-zones.index')->with('success', 'Zona pengiriman berhasil dihapus.');
    }

    protected function invalidateCache(?int $regionId): void
    {
        if ($regionId) {
            Cache::increment('delivery_zones_ver_'.$regionId);
        }
        Cache::increment('delivery_zones_ver_all');
    }
}
