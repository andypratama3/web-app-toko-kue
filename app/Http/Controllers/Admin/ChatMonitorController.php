<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Enums\OrderBotConversationState;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use App\Services\WhatsApp\WhatsappMetaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatMonitorController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        $conversations = WhatsAppConversation::with(['customer', 'region', 'latestMessage'])
            ->where('region_id', $user->region_id)
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

        return view('dashboard.admin.chat.index', compact('conversations'));
    }

    public function show(WhatsAppConversation $conversation)
    {
        $user = Auth::user();

        if ($conversation->region_id !== $user->region_id) {
            abort(403);
        }

        $conversation->load(['customer', 'region', 'messages' => function ($query) {
            $query->orderBy('created_at', 'asc');
        }]);

        return view('dashboard.admin.chat.show', compact('conversation'));
    }

    public function reply(WhatsAppConversation $conversation, Request $request, WhatsappMetaService $metaService)
    {
        $user = Auth::user();

        if ($conversation->region_id !== $user->region_id) {
            abort(403);
        }

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
        $user = Auth::user();

        if ($conversation->region_id !== $user->region_id) {
            abort(403);
        }

        $conversation->update(['status' => 'closed']);

        return back()->with('success', 'Percakapan ditutup.');
    }

    public function escalateConversation(WhatsAppConversation $conversation)
    {
        $user = Auth::user();

        if ($conversation->region_id !== $user->region_id) {
            abort(403);
        }

        $conversation->update([
            'status' => 'active',
            'current_state' => OrderBotConversationState::ESCALATED_TO_HUMAN->value,
        ]);

        return back()->with('success', 'Percakapan diserahkan ke admin (escalated).');
    }

    public function resumeConversation(WhatsAppConversation $conversation)
    {
        $user = Auth::user();

        if ($conversation->region_id !== $user->region_id) {
            abort(403);
        }

        $conversation->update([
            'status' => 'active',
            'current_state' => OrderBotConversationState::INIT->value,
            'context' => null,
        ]);

        return back()->with('success', 'Bot diaktifkan kembali untuk percakapan ini.');
    }

    public function stats(Request $request)
    {
        $user = Auth::user();
        $regionId = $user->region_id;

        $stats = [
            'total_conversations' => WhatsAppConversation::where('region_id', $regionId)->count(),
            'active_conversations' => WhatsAppConversation::where('region_id', $regionId)->where('status', 'active')->count(),
            'messages_today' => WhatsAppMessage::whereHas('conversation', function ($q) use ($regionId) {
                $q->where('region_id', $regionId);
            })->whereDate('created_at', now()->toDateString())->count(),
            'escalated' => WhatsAppConversation::where('region_id', $regionId)
                ->where('current_state', 'ESCALATED_TO_HUMAN')
                ->where('status', 'active')
                ->count(),
        ];

        return response()->json($stats);
    }
}
