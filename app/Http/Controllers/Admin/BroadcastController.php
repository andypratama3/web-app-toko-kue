<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessWhatsAppBroadcastJob;
use App\Jobs\SyncWhatsAppTemplatesJob;
use App\Models\CustomerCategory;
use App\Models\WhatsAppBroadcast;
use App\Models\WhatsAppTemplate;
use App\Services\WhatsApp\WhatsAppBroadcastService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class BroadcastController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        $broadcasts = WhatsAppBroadcast::with(['template', 'creator', 'region'])
            ->when($user->region_id, function ($query, $regionId) {
                $query->where(function ($q) use ($regionId) {
                    $q->where('region_id', $regionId)->orWhereNull('region_id');
                });
            })
            ->when($request->status, fn ($query, $status) => $query->where('status', $status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('dashboard.admin.broadcast.index', compact('broadcasts'));
    }

    public function create()
    {
        $templates = WhatsAppTemplate::where('is_active', true)
            ->orderBy('name')
            ->get();

        if ($templates->isEmpty()) {
            return redirect()->route('admin.broadcast.index')
                ->with('warning', 'Belum ada template broadcast. Klik "Sinkronkan Template" untuk mengambil template yang sudah di-approve Meta.');
        }

        $categories = CustomerCategory::orderBy('name')->get();
        $regions = \App\Models\Region::orderBy('name')->get();

        return view('dashboard.admin.broadcast.create', compact('templates', 'categories', 'regions'));
    }

    public function store(Request $request, WhatsAppBroadcastService $service)
    {
        $validated = $request->validate([
            'whatsapp_template_id' => 'required|exists:whatsapp_templates,id',
            'title' => 'nullable|string|max:190',
            'region_id' => 'nullable|integer',
            'customer_category_id' => 'nullable|exists:customer_categories,id',
            'only_opt_in' => 'nullable|boolean',
            'parameters' => 'nullable|array',
            'header_media_type' => 'nullable|string|in:image,video,document',
            'header_media_url' => 'nullable|url|max:2048',
            'header_media_file' => 'nullable|file',
        ]);

        $regionRaw = $validated['region_id'] ?? null;
        if ($regionRaw && (int) $regionRaw !== 0 && ! \App\Models\Region::whereKey((int) $regionRaw)->exists()) {
            return back()->withErrors(['region_id' => 'Cabang tidak valid.'])->withInput();
        }

        $template = WhatsAppTemplate::findOrFail($validated['whatsapp_template_id']);
        $headerFormat = WhatsAppBroadcastService::headerFormat($template->components ?? []);

        // Olah media header (gambar/video/document) bila template punya header media.
        [$headerMediaType, $headerMediaUrl] = $this->resolveHeaderMedia($request, $headerFormat);

        $parameters = [];
        foreach ($validated['parameters'] ?? [] as $index => $value) {
            $parameters[(int) $index] = trim((string) $value);
        }

        // Parameter yang diisi harus lengkap sesuai jumlah placeholder template
        for ($i = 1; $i <= $template->parameters_count; $i++) {
            if (empty($parameters[$i])) {
                return back()->withErrors([
                    'parameters' => "Parameter {$i} wajib diisi (sesuai placeholder {{$i}} pada template).",
                ])->withInput();
            }
        }

        // region_id "0" berarti semua cabang; null → cabang admin (default)
        $regionRaw = $validated['region_id'] ?? null;
        $regionId = ($regionRaw && (int) $regionRaw !== 0)
            ? (int) $regionRaw
            : ((int) $regionRaw === 0 ? null : auth()->user()->region_id);

        $filter = [
            'region_id' => $regionId,
            'customer_category_id' => $validated['customer_category_id'] ?? null,
            'only_opt_in' => ! empty($validated['only_opt_in']),
        ];

        $broadcast = WhatsAppBroadcast::create([
            'user_id' => Auth::id(),
            'region_id' => $filter['region_id'],
            'whatsapp_template_id' => $template->id,
            'title' => $validated['title'] ?? null,
            'body_preview' => $service->renderBodyPreview($template, $parameters, $headerMediaType, $headerMediaUrl),
            'header_media_type' => $headerMediaType,
            'header_media_url' => $headerMediaUrl,
            'parameters' => $parameters,
            'status' => 'draft',
        ]);

        $recipientCount = $service->storeRecipients($broadcast, $filter);

        if ($recipientCount === 0) {
            $broadcast->update(['status' => 'cancelled']);
            $broadcast->recipients()->delete();

            return redirect()->route('admin.broadcast.index')
                ->with('warning', 'Tidak ada penerima yang cocok dengan filter tersebut. Broadcast dibatalkan.');
        }

        $service->dispatchBroadcast($broadcast);

        return redirect()->route('admin.broadcast.show', $broadcast)
            ->with('success', "Broadcast dikirim ke {$recipientCount} penerima.");
    }

    /**
     * Tentukan media header broadcast dari upload file / URL, sesuai tipe header template.
     * Mengembalikan [type|null, url|null]. Media kosong = pakai media bawaan template yang di-approve Meta.
     */
    protected function resolveHeaderMedia(Request $request, ?string $headerFormat): array
    {
        // Header teks tidak boleh membawa media.
        if (! $headerFormat || $headerFormat === 'TEXT') {
            return [null, null];
        }

        $allowedType = strtolower($headerFormat); // image | video | document
        $file = $request->file('header_media_file');
        $urlRaw = trim((string) $request->input('header_media_url', ''));

        if ($file && $file->isValid()) {
            $mimes = match ($allowedType) {
                'image' => 'jpg,jpeg,png,webp',
                'video' => 'mp4,3gp,mov',
                default => 'pdf',
            };
            $maxKb = $allowedType === 'video' ? 20480 : 10240;

            $request->validate([
                'header_media_file' => ["required", "file", "mimes:{$mimes}", "max:{$maxKb}"],
            ]);

            // Ekstensi diambil dari MIME yang terdeteksi (bukan nama file klien)
            // agar tidak mungkin tersimpan sebagai .php/.phtml/.svg dsb.
            $detectedMime = strtolower((string) $file->getMimeType());
            $extension = match ($detectedMime) {
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
                'video/mp4' => 'mp4',
                'video/3gpp' => '3gp',
                'video/quicktime' => 'mov',
                'application/pdf' => 'pdf',
                default => null,
            };

            if ($extension === null) {
                return back()->withErrors(['header_media_file' => 'Tipe file tidak diizinkan.'])->withInput();
            }

            $filename = 'broadcast_media/'.time().'_'.uniqid().'.'.$extension;
            $file->storeAs('public', $filename);
            $url = url(\Illuminate\Support\Facades\Storage::url($filename));

            return [$allowedType, $url];
        }

        if ($urlRaw && str_starts_with($urlRaw, 'http')) {
            return [$allowedType, $urlRaw];
        }

        return [null, null];
    }

    public function show(WhatsAppBroadcast $broadcast)
    {
        $user = Auth::user();

        if ($broadcast->region_id && $broadcast->region_id !== $user->region_id) {
            abort(403);
        }

        $broadcast->load(['template', 'creator', 'region']);

        $recipients = $broadcast->recipients()
            ->orderBy('id')
            ->paginate(30)
            ->withQueryString();

        return view('dashboard.admin.broadcast.show', compact('broadcast', 'recipients'));
    }

    /**
     * Trigger sinkronisasi template dari Meta (dispatcher async).
     */
    public function syncTemplate()
    {
        $wabaId = config('services.whatsapp.waba_id');

        if (! $wabaId) {
            return redirect()->route('admin.broadcast.index')
                ->withErrors(['template_sync' => 'META_WABA_ID belum dikonfigurasi di .env. Tambahkan lalu coba lagi.']);
        }

        SyncWhatsAppTemplatesJob::dispatch();

        return redirect()->route('admin.broadcast.index')
            ->with('success', 'Sinkronisasi template berjalan di antrian. Silakan muat ulang setelah beberapa saat.');
    }

    /**
     * Hitung jumlah penerima (AJAX) untuk preview filter.
     */
    public function previewCount(Request $request, WhatsAppBroadcastService $service)
    {
        $count = $service->countRecipients([
            'region_id' => $request->integer('region_id', 0) ?: null,
            'customer_category_id' => $request->integer('customer_category_id', 0) ?: null,
            'only_opt_in' => $request->boolean('only_opt_in'),
        ]);

        return response()->json(['count' => $count]);
    }

    /**
     * Pratinjau hasil render body template berdasarkan parameter yang dikirim (AJAX).
     */
    public function previewBody(Request $request)
    {
        $template = WhatsAppTemplate::findOrFail($request->integer('template_id'));

        $parameters = [];
        foreach ($request->input('parameters', []) as $index => $value) {
            $parameters[(int) $index] = trim((string) $value);
        }

        $mediaType = $request->input('header_media_type');
        $mediaUrl  = $request->input('header_media_url');

        return response()->json([
            'preview' => app(WhatsAppBroadcastService::class)->renderBodyPreview(
                $template,
                $parameters,
                $mediaType ?: null,
                $mediaUrl ?: null,
            ),
        ]);
    }

    /**
     * Batalkan broadcast yang masih queued/processing.
     */
    public function cancel(WhatsAppBroadcast $broadcast)
    {
        $user = Auth::user();

        if ($broadcast->region_id && $broadcast->region_id !== $user->region_id) {
            abort(403);
        }

        if ($broadcast->status !== 'queued' && $broadcast->status !== 'processing') {
            return back()->with('warning', 'Broadcast sudah selesai dan tidak bisa dibatalkan.');
        }

        $broadcast->update([
            'status' => 'cancelled',
            'finished_at' => now(),
        ]);

        // Batal sisa recipient yang belum terkirim
        $broadcast->recipients()->where('status', 'pending')->update(['status' => 'failed', 'error' => 'Broadcast dibatalkan admin.']);

        return back()->with('success', 'Broadcast dibatalkan.');
    }
}