<?php

namespace App\Console\Commands;

use App\Enums\OrderBotConversationState;
use App\Models\WhatsAppConversation;
use App\Services\WhatsApp\WhatsappMetaService;
use Illuminate\Console\Command;

/**
 * Recovery keranjang ditinggalkan.
 *
 * User sering mengisi keranjang lalu menghilang di tengah alur (pilih
 * produk / isi form). Command ini mengirim SATU pesan pengingat ramah
 * agar mereka lanjut checkout — potensi pendapatan yang dulu hilang diam.
 *
 * Batasan penting:
 * - Hanya dalam jendela < 24 jam sejak pesan terakhir user (kebijakan Meta:
 *   di luar itu WAJIB pakai template, tidak boleh teks bebas).
 * - Hanya SEKALI per keranjang (ditandai `cart_reminder_sent_at`).
 * - Tidak mengusik user yang sedang di tengah alamat / sudah punya order.
 */
class RecoverAbandonedCarts extends Command
{
    protected $signature = 'whatsapp:recover-carts
                            {--min-minutes=30 : Jeda minimal sejak pesan terakhir (menit)}
                            {--max-hours=6 : Jeda maksimal sejak pesan terakhir (jam, harus < 24)}';

    protected $description = 'Kirim pengingat sekali ke user yang meninggalkan keranjang tanpa checkout';

    public function handle(WhatsappMetaService $metaService): int
    {
        $minMinutes = max(1, (int) $this->option('min-minutes'));
        // Jaga agar tetap di dalam jendela 24 jam Meta (teks bebas).
        $maxHours = min(23, max(1, (int) $this->option('max-hours')));

        $olderThan = now()->subMinutes($minMinutes);
        $newerThan = now()->subHours($maxHours);

        $candidates = WhatsAppConversation::where('status', 'active')
            ->whereIn('current_state', [
                OrderBotConversationState::MENU_SELECTION->value,
                OrderBotConversationState::PRODUCT_BROWSING->value,
                OrderBotConversationState::AWAITING_ORDER_FORM->value,
            ])
            ->whereNotNull('last_message_at')
            ->where('last_message_at', '<', $olderThan)
            ->where('last_message_at', '>', $newerThan)
            ->get();

        $sent = 0;

        foreach ($candidates as $conversation) {
            if (! $this->shouldRemind($conversation)) {
                continue;
            }

            $reminder = $this->buildReminder($conversation);

            if ($metaService->sendText($conversation->phone_number, $reminder) === null) {
                // Gagal kirim: jangan tandai terkirim agar dicoba lagi nanti.
                continue;
            }

            $conversation->setContext('cart_reminder_sent_at', now()->toIso8601String());
            $sent++;
        }

        $this->info("✓ {$sent} pengingat keranjang terkirim (dari {$candidates->count()} kandidat).");

        return self::SUCCESS;
    }

    protected function shouldRemind(WhatsAppConversation $conversation): bool
    {
        $context = $conversation->context ?? [];

        // Keranjang harus berisi.
        $cart = $context['cart'] ?? [];
        if (! is_array($cart) || $cart === []) {
            return false;
        }

        // Sudah punya order berjalan → jangan ganggu.
        if (! empty($context['confirmed_order_id'])) {
            return false;
        }

        // Sedang menengah ubah alamat / pilih cabang → jangan potong alurnya.
        if (! empty($context['editing_address']) || ! empty($context['awaiting_branch'])) {
            return false;
        }

        // Sudah pernah diingatkan untuk keranjang ini (< 24 jam) → cukup sekali.
        $lastReminder = $context['cart_reminder_sent_at'] ?? null;
        if ($lastReminder !== null) {
            try {
                if (\Illuminate\Support\Carbon::parse($lastReminder)->greaterThan(now()->subHours(23))) {
                    return false;
                }
            } catch (\Throwable $ignored) {
                // Nilai rusak: anggap belum pernah diingatkan.
            }
        }

        return true;
    }

    protected function buildReminder(WhatsAppConversation $conversation): string
    {
        $cart = $conversation->getContext('cart') ?? [];

        $lines = [];
        $total = 0;
        foreach ($cart as $i => $row) {
            $n = $i + 1;
            $lines[] = "{$n}. {$row['qty']}x {$row['product_name']} ({$row['variant_name']})";
            $total += (int) ($row['qty'] ?? 0);
        }

        $totalLine = $total > 0 ? "\nTotal item: {$total}" : '';

        return "Halo! 👋 Keranjangmu masih tersimpan:\n\n"
            . implode("\n", $lines)
            . "{$totalLine}\n\n"
            . "Kalau mau lanjut, ketik *PESAN* ya — data keranjangmu masih aman. 🙏";
    }
}
