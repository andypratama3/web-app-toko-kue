<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Region;
use App\Services\WhatsApp\AdminNotificationRouterService;
use App\Support\Phone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class RegionController extends Controller
{
    /**
     * Halaman Pengaturan Cabang.
     *
     * Fokus: nomor WhatsApp owner/pemilik tiap cabang. Notifikasi pesanan
     * baru (dan eskalasi) diteruskan otomatis ke nomor ini.
     *
     * Catatan: `meta_phone_number_id` sengaja TIDAK dikelola di sini.
     */
    public function index()
    {
        $regions = Region::orderBy('name')->get();
        $ownRegionId = Auth::user()->region_id;

        return view('dashboard.admin.regions.index', compact('regions', 'ownRegionId'));
    }

    /**
     * Simpan nomor owner cabang. Hanya admin cabang terkait yang boleh
     * mengubah (konsisten dengan scoping region di seluruh aplikasi).
     */
    public function update(Request $request, Region $region)
    {
        if ($region->id !== Auth::user()->region_id) {
            abort(403, 'AKSES DITOLAK');
        }

        $validator = Validator::make($request->all(), [
            'owner_phone' => [
                'nullable',
                'string',
                'max:25',
                function ($attribute, $value, $fail) {
                    $raw = trim((string) $value);

                    if ($raw === '') {
                        return;
                    }

                    if (! preg_match('/^[0-9+()\-\s.]+$/', $raw)) {
                        $fail('Nomor HP hanya boleh berisi angka dan simbol +, -, spasi, titik, atau kurung.');

                        return;
                    }

                    $normalized = Phone::normalize($raw);
                    if (strlen($normalized) < 10 || strlen($normalized) > 15) {
                        $fail('Nomor HP tidak valid (harus 10–15 digit setelah dinormalisasi).');
                    }
                },
            ],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            return redirect()->route('admin.regions.index')
                ->withErrors($validator)
                ->withInput()
                ->with('error_modal_id', 'edit-region-modal-'.$region->id);
        }

        $raw = trim((string) $request->input('owner_phone', ''));
        $region->owner_phone = $raw === '' ? null : Phone::normalize($raw);
        if ($request->has('latitude')) {
            $region->latitude = $request->input('latitude') === '' ? null : $request->input('latitude');
        }
        if ($request->has('longitude')) {
            $region->longitude = $request->input('longitude') === '' ? null : $request->input('longitude');
        }
        if ($request->has('is_active')) {
            $region->is_active = $request->boolean('is_active');
        }
        $region->save();

        return redirect()->route('admin.regions.index')->with(
            'success',
            $region->owner_phone
                ? "Nomor owner cabang {$region->name} berhasil disimpan ({$region->owner_phone})."
                : "Nomor owner cabang {$region->name} berhasil disimpan."
        );
    }

    /**
     * Kirim pesan tes ke nomor owner cabang untuk memverifikasi jalur kirim.
     */
    public function sendTest(Region $region, AdminNotificationRouterService $router)
    {
        if ($region->id !== Auth::user()->region_id) {
            abort(403, 'AKSES DITOLAK');
        }

        if (! $region->owner_phone) {
            return back()->with('error', 'Isi dan simpan nomor owner terlebih dahulu sebelum mengirim tes.');
        }

        $sent = $router->sendOwnerTestMessage($region);

        return back()->with(
            $sent ? 'success' : 'error',
            $sent
                ? 'Pesan tes berhasil dikirim ke '.Phone::normalize($region->owner_phone).'.'
                : 'Gagal mengirim pesan tes. Periksa koneksi/token Meta dan nomor owner cabang.'
        );
    }
}
