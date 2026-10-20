<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class OrderController extends Controller
{
    /**
     * Menampilkan daftar pesanan pada region admin.
     * Logika peringatan diperbarui sesuai kategori customer.
     */
    public function index(Request $request)
    {
        $admin = Auth::user();
        $orders = Order::with(['customer.category', 'createdBy'])
            ->where('region_id', $admin->region_id)
            ->where('status', '!=', 'diverifikasi_admin');

        $search = $request->input('search');

        $newOrdersCount = Order::where('region_id', $admin->region_id)
            ->where('status', 'baru')->count();

        $orders = $orders->when($search, function ($query, $searchTerm) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('invoice_number', 'like', "%{$searchTerm}%")
                    ->orWhereHas('customer', function ($sub) use ($searchTerm) {
                        $sub->where('name', 'like', "%{$searchTerm}%")
                            ->orWhere('company_name', 'like', "%{$searchTerm}%");
                    })
                    ->orWhereHas('createdBy', function ($sub) use ($searchTerm) {
                        $sub->where('name', 'like', "%{$searchTerm}%");
                    });
            });
        })->latest()->get();

        foreach ($orders as $order) {
            $order->show_warning = false;
            if (is_null($order->payment_proof) && $order->customer && $order->customer->category) {
                $categoryName = strtolower($order->customer->category->name);
                $warningDays = 0;
                if ($categoryName === 'reseller') {
                    $warningDays = 5;
                } elseif ($categoryName === 'supermarket') {
                    $warningDays = 28;
                }
                if ($warningDays > 0) {
                    $daysSinceCreation = Carbon::parse($order->created_at)->diffInDays(now());
                    if ($daysSinceCreation >= $warningDays) {
                        $order->show_warning = true;
                    }
                }
            }
        }

        // Kurir se-region untuk fitur tugaskan order bot (status baru).
        $couriers = User::role('kurir')->where('region_id', $admin->region_id)
            ->orderBy('name')->get(['id', 'name']);

        return view('dashboard.admin.order-list.index', compact('orders', 'newOrdersCount', 'couriers'));
    }

    /**
     * Tugaskan order bot (status baru, tanpa kurir) ke kurir se-region.
     * Kurir lalu memprosesnya via alur normal (dikemas → diambil → ...).
     */
    public function assign(Request $request, $id)
    {
        $validated = $request->validate([
            'courier_id' => 'required|integer|exists:users,id',
        ]);

        $admin = Auth::user();
        $order = Order::where('region_id', $admin->region_id)
            ->where('status', 'baru')
            ->whereNull('created_by_user_id')
            ->findOrFail($id);

        $courier = User::role('kurir')
            ->where('id', $validated['courier_id'])
            ->where('region_id', $admin->region_id)
            ->first();
        if (! $courier) {
            return response()->json(['message' => 'Kurir tidak valid untuk wilayah ini.'], 422);
        }

        $order->created_by_user_id = $courier->id;
        $order->status = 'dikemas';
        $order->save();

        return response()->json([
            'message' => "Pesanan ditugaskan ke {$courier->name}.",
            'order_id' => $order->id,
        ]);
    }

    /**
     * Mengambil detail pesanan untuk modal verifikasi.
     * Diperbarui agar selaras dengan detail yang ada di PesananController.
     */
    public function details($id)
    {
        $admin = Auth::user();
        try {
            // Eager loading tetap sama
            $order = Order::with([
                'customer',
                'createdBy',
                'items',
                'returns',
                'returns.returnedProducts.product',
                'returns.returnedProducts.variant',
            ])
                ->where('region_id', $admin->region_id)
                ->findOrFail($id);

            $paidAtLabel = '';
            $paidAtFormatted = null;

            if ($order->paid_at) {
                $createdAt = Carbon::parse($order->created_at)->startOfDay();
                $paidAt = Carbon::parse($order->paid_at)->startOfDay();
                $diffInDays = $createdAt->diffInDays($paidAt);

                if ($diffInDays == 1) {
                    $paidAtLabel = ' (Harian)';
                } elseif ($diffInDays >= 2 && $diffInDays <= 7) {
                    $paidAtLabel = ' (Mingguan)';
                }

                $paidAtFormatted = Carbon::parse($order->paid_at)->isoFormat('D MMMM YYYY, HH:mm');
            }

            $activeReturn = $order->returns()->where('status', '!=', 'ditolak')->latest()->first();

            $formattedOrder = [
                'id' => $order->id,
                'invoice_number' => $order->invoice_number,
                'status' => $order->status,
                'total_amount' => $order->total_amount,
                'payment_method' => $order->payment_method,
                'created_at' => $order->created_at->isoFormat('D MMMM YYYY, HH:mm'),
                'paid_at' => $paidAtFormatted,
                'paid_at_label' => $paidAtLabel,
                // 'payment_proof' => $order->payment_proof,
                'payment_proof' => $order->payment_proof ? asset('storage/'.preg_replace('#^(storage/|public/)#', '', $order->payment_proof)) : null,
                'note' => $order->note,
                'customer' => [
                    'name' => $order->customer->name ?? 'N/A',
                    'company_name' => $order->customer->company_name ?? null,
                    'phone' => $order->customer->phone ?? 'N/A',
                    'address' => $order->customer->address ?? 'N/A',
                ],
                'kurir_name' => $order->createdBy->name ?? '-',
                'items' => $order->items->map(fn ($item) => [
                    'id' => $item->product_id,
                    'name' => $item->product_name,
                    'price' => $item->price,
                    'quantity' => $item->quantity,
                    'variant_name' => $item->variant_name,
                ])->toArray(),

                // Perbaikan pada bagian ini
                'return_details' => $activeReturn ? [
                    // 'return_proof' => $activeReturn->return_proof,
                    'return_proof' => $activeReturn->return_proof ? asset('storage/'.preg_replace('#^(storage/|public/)#', '', $activeReturn->return_proof)) : null,
                    'total_amount_returned' => $activeReturn->total_amount_returned,
                    'returned_products' => $activeReturn->returnedProducts->map(function ($p) {
                        // **PENGECEKAN AMAN**
                        // Cek apakah relasi product ada sebelum mengakses propertinya
                        $productName = $p->product ? $p->product->name : 'Produk Telah Dihapus';
                        // Cek apakah relasi variant ada
                        $variantName = $p->variant ? $p->variant->name : null;

                        return [
                            'name' => $productName,
                            'variant_name' => $variantName,
                            'quantity' => $p->quantity,
                            'price' => $p->price,
                        ];
                    })->toArray(),
                ] : null,
            ];

            return response()->json($formattedOrder);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Pesanan tidak ditemukan.'], 404);
        } catch (\Exception $e) {
            // Log error untuk debugging
            \Log::error('Error fetching order details for order ID '.$id.' by admin: '.$e->getMessage());

            return response()->json(['message' => 'Terjadi kesalahan internal.'], 500);
        }
    }

    /**
     * Verifikasi pesanan (ubah status menjadi diverifikasi_admin).
     * Disertai bukti: retur wajib ada return_proof, non-retur wajib
     * ada payment_proof (termasuk cash — kurir wajib foto bukti terima).
     * Order 'baru' bayar-di-depan (mis. via bot + bukti) bisa langsung
     * diverifikasi owner TANPA kurir — pengiriman menyusul alur kurir.
     */
    public function verify($id)
    {
        $admin = Auth::user();
        // DIUBAH: Mencari pesanan dalam tiga kemungkinan status
        $order = Order::where('region_id', $admin->region_id)
            ->whereIn('status', ['baru', 'selesai', 'menunggu_verifikasi_admin']) // Perbaikan di sini
            ->findOrFail($id);

        $activeReturn = $order->returns()->where('status', '!=', 'ditolak')->latest()->first();
        if ($activeReturn) {
            if (empty($activeReturn->return_proof)) {
                return response()->json(['message' => 'Belum ada bukti retur. Minta kurir mengunggah bukti retur dulu.'], 422);
            }
        } elseif (empty($order->payment_proof)) {
            return response()->json(['message' => 'Belum ada bukti pembayaran. Minta kurir mengunggah bukti dulu.'], 422);
        }

        $order->status = 'diverifikasi_admin';
        $order->save();

        return response()->json(['message' => 'Pesanan berhasil diverifikasi.']);
    }

    /**
     * Admin mengunggah bukti (pembayaran / pesanan Grab-GoSend / retur)
     * langsung dari halaman verifikasi. Berguna saat kurir tak tersedia
     * atau pesanan luar kota yang diatur manual oleh admin.
     */
    public function uploadProof(Request $request, $id)
    {
        $request->validate([
            'payment_proof' => 'required|image|mimes:jpeg,png,jpg,webp|max:4096',
        ]);

        $admin = Auth::user();

        try {
            $order = Order::where('region_id', $admin->region_id)
                ->whereIn('status', ['baru', 'selesai', 'menunggu_verifikasi_admin'])
                ->findOrFail($id);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Pesanan tidak ditemukan.'], 404);
        }

        $oldProof = $order->payment_proof;

        $path = $request->file('payment_proof')->store('payment_proofs', 'public');

        if (! $path) {
            return response()->json(['message' => 'Gagal mengunggah bukti. Coba lagi.'], 500);
        }

        $order->payment_proof = $path;
        $order->save();

        // Hapus file lama agar tak menumpuk (hanya setelah file baru tersimpan).
        if ($oldProof && $oldProof !== $path) {
            Storage::disk('public')->delete(preg_replace('#^(storage/|public/)#', '', $oldProof));
        }

        return response()->json([
            'message' => 'Bukti berhasil diunggah.',
            'payment_proof' => asset('storage/'.$path),
        ]);
    }

    /**
     * Verifikasi PEMBAYARAN saja (bayar di depan): tandai lunas tanpa
     * mengubah status pengiriman — alur kurir tetap jalan. Syarat: ada bukti.
     */
    public function verifyPayment($id)
    {
        $admin = Auth::user();
        $order = Order::where('region_id', $admin->region_id)->findOrFail($id);

        if (empty($order->payment_proof)) {
            return response()->json(['message' => 'Belum ada bukti pembayaran.'], 422);
        }

        if (empty($order->paid_at)) {
            $order->paid_at = now();
            $order->save();
        }

        return response()->json(['message' => 'Pembayaran terverifikasi (Lunas). Pengiriman tetap lanjut.']);
    }

    /**
     * Tolak verifikasi pesanan (kembalikan status ke diterima_pembeli).
     */
    public function reject(Request $request, $id)
    {
        $validated = $request->validate([
            'rejection_note' => 'required|string|min:10',
        ]);

        $admin = Auth::user();
        $order = Order::where('region_id', $admin->region_id)
            ->whereIn('status', ['selesai', 'menunggu_verifikasi_admin'])
            ->findOrFail($id);

        // (Logika penolakan lainnya tetap sama)
        $returnRequest = $order->returns()->where('status', 'menunggu_konfirmasi')->first();
        if ($returnRequest) {
            if ($returnRequest->return_proof) {
                Storage::disk('public')->delete($returnRequest->return_proof);
            }
            $returnRequest->status = 'ditolak';
            $returnRequest->admin_notes = 'Verifikasi retur ditolak oleh admin.';
            $returnRequest->save();
            $order->status = 'diterima_pembeli';
        } else {
            if ($order->payment_proof) {
                Storage::disk('public')->delete($order->payment_proof);
            }
            $order->status = 'diterima_pembeli';
            $order->payment_proof = null;
            $order->paid_at = null;
        }

        $order->rejection_note = $validated['rejection_note'];
        $order->save();

        // [!code block:start]
        // Ganti respons JSON dengan redirect dan pesan flash
        return redirect()->route('admin.orders.index')->with('success', 'Verifikasi pesanan berhasil ditolak.');
        // [!code block:end]
    }

    /**
     * Hapus pesanan secara permanen dari database.
     * Termasuk menghapus file bukti bayar/retur yang tersimpan.
     */
    public function destroy($id)
    {
        $admin = Auth::user();
        DB::beginTransaction();

        try {
            $order = Order::where('region_id', $admin->region_id)->with('returns')->findOrFail($id);

            // 1. Hapus bukti pembayaran utama
            if ($order->payment_proof) {
                Storage::disk('public')->delete($order->payment_proof);
            }

            // 2. Hapus bukti retur (jika ada)
            foreach ($order->returns as $return) {
                if ($return->return_proof) {
                    Storage::disk('public')->delete($return->return_proof);
                }
            }

            // 3. Hapus data pesanan dari database
            // (Relasi seperti order_items dan returns akan terhapus otomatis jika foreign key di-set cascade)
            $order->delete();

            DB::commit();

            return response()->json(['message' => 'Pesanan berhasil dihapus secara permanen.']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal menghapus pesanan ID '.$id.': '.$e->getMessage());

            return response()->json(['message' => 'Terjadi kesalahan saat menghapus pesanan.'], 500);
        }
    }

    public function downloadBotRekap(Request $request, $id)
    {
        $start = $request->input('start');
        $end = $request->input('end');
        $order = Order::with('items', 'customer')
            ->where('region_id', Auth::user()->region_id)
            ->findOrFail($id);
        if ($order->channel !== 'whatsapp' || $order->status !== 'diverifikasi_admin') {
            abort(403, 'Rekap hanya untuk pesanan bot yang sudah diverifikasi.');
        }
        if ($start && $end) {
            $rangeStart = Carbon::parse($start)->startOfDay();
            $rangeEnd = Carbon::parse($end)->endOfDay();
            if ($order->created_at < $rangeStart || $order->created_at > $rangeEnd) {
                abort(403, 'Pesanan bot di luar range tanggal.');
            }
        }
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('dashboard.admin.orders.bot-rekap_pdf', compact('order', 'start', 'end'));

        return $pdf->download('BotRekap_'.$order->invoice_number.'.pdf');
    }
}
