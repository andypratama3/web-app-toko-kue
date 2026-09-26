<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Enums\OrderBotConversationState;
use App\Models\Region;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use App\Services\WhatsApp\ConversationRegionResolver;
use App\Services\WhatsApp\WhatsappMetaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatMonitorController extends Controller
{
    /**
     * Cabang yang sedang difilter. Tanpa parameter eksplisit, admin
     * melihat percakapan cabangnya sendiri. "all" melihat semua cabang
     * karena satu nomor WhatsApp dipakai bersama.
     */
    protected function resolveRegionFilter(Request $request): ?int
    {
        $requested = $request->query('region');

        if ($requested === null || $requested === '' || $requested === 'all') {
            return $request->has('region') && $requested === 'all'
                ? null
                : Auth::user()?->region_id;
        }

        if (! ctype_digit((string) $requested)) {
            return Auth::user()?->region_id;
        }

        $exists = Region::where('id', (int) $requested)->exists();

        return $exists ? (int) $requested : Auth::user()?->region_id;
    }

    public function index(Request $request, ConversationRegionResolver $regionResolver)
    {
        $regionResolver->syncUnresolved();

        $regionId = $this->resolveRegionFilter($request);

        $conversations = WhatsAppConversation::with(['customer', 'region', 'latestMessage'])
            ->when($regionId !== null, fn ($query) => $query->where('region_id', $regionId))
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('phone_number', 'like', "%{$search}%")
                      ->orWhere('profile_name', 'like', "%{$search}%")
                      ->orWhereHas('customer', function ($cq) use ($search) {
                          $cq->where('name', 'like', "%{$search}%");
                      });
                });
            })
            ->when($request->status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->latest('last_message_at')
            ->paginate(15)
            ->withQueryString();

        $regions = Region::orderBy('name')->get();

        return view('dashboard.admin.chat.index', compact('conversations', 'regions', 'regionId'));
    }

    /**
     * Pencabutan percakapan. Satu nomor WhatsApp dipakai semua cabang,
     * jadi admin tetap boleh membalas percakapan cabang lain.
     */
    public function updateRegion(
        Request $request,
        WhatsAppConversation $conversation,
        ConversationRegionResolver $regionResolver
    ) {
        $data = $request->validate([
            'region_id' => ['required', 'integer', 'exists:regions,id'],
        ]);

        $regionResolver->assignManually($conversation, (int) $data['region_id']);

        return back()->with('success', 'Cabang percakapan berhasil diperbarui.');
    }

    public function show(WhatsAppConversation $conversation)
    {

        $conversation->load(['customer', 'region', 'messages' => function ($query) {
            $query->orderBy('created_at', 'asc');
        }]);

        return view('dashboard.admin.chat.show', compact('conversation'));
    }

    public function reply(WhatsAppConversation $conversation, Request $request, WhatsappMetaService $metaService)
    {

        $request->validate(['message' => 'required|string|max:4096']);

        if ($conversation->status !== 'active') {
            $conversation->update(['status' => 'active']);
        }

        $text = trim($request->input('message'));

        try {
            $messageId = $metaService->sendText($conversation->phone_number, $text, record: false);
        } catch (\Throwable $e) {
            return back()->withErrors(['message_send' => 'Gagal mengirim: ' . $e->getMessage()]);
        }

        if (!$messageId) {
            return back()->withErrors(['message_send' => 'Pesan tidak terkirim oleh WhatsApp API.']);
        }

        WhatsAppMessage::create([
            'conversation_id' => $conversation->id,
            'whatsapp_message_id' => $messageId,
            'sender_type' => 'admin',
            'message_type' => 'text',
            'content' => $text,
            'status' => 'accepted',
        ]);

        $conversation->incrementMessageCount();

        return back()->with('success', 'Pesan berhasil dikirim.');
    }

    public function closeConversation(WhatsAppConversation $conversation)
    {

        $conversation->update(['status' => 'closed']);

        return back()->with('success', 'Percakapan ditutup.');
    }

    public function escalateConversation(WhatsAppConversation $conversation)
    {

        $conversation->update([
            'status' => 'active',
            'current_state' => OrderBotConversationState::ESCALATED_TO_HUMAN->value,
        ]);

        return back()->with('success', 'Percakapan diserahkan ke admin (escalated).');
    }

    public function resumeConversation(WhatsAppConversation $conversation)
    {

        $conversation->update([
            'status' => 'active',
            'current_state' => OrderBotConversationState::INIT->value,
            'context' => null,
        ]);

        return back()->with('success', 'Bot diaktifkan kembali untuk percakapan ini.');
    }

    public function stats(Request $request)
    {
        $regionId = $this->resolveRegionFilter($request);

        $conversations = fn () => WhatsAppConversation::query()
            ->when($regionId !== null, fn ($q) => $q->where('region_id', $regionId));

        $stats = [
            'total_conversations' => $conversations()->count(),
            'active_conversations' => $conversations()->where('status', 'active')->count(),
            'messages_today' => WhatsAppMessage::whereHas('conversation', function ($q) use ($regionId) {
                $q->when($regionId !== null, fn ($cq) => $cq->where('region_id', $regionId));
            })->whereDate('created_at', now()->toDateString())->count(),
            'escalated' => $conversations()
                ->where('current_state', 'ESCALATED_TO_HUMAN')
                ->where('status', 'active')
                ->count(),
        ];

        return response()->json($stats);
    }
}
