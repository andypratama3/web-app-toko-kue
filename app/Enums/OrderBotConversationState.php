<?php

namespace App\Enums;

enum OrderBotConversationState: string
{
    case INIT = 'INIT';
    case WELCOME_SENT = 'WELCOME_SENT';
    case MENU_SELECTION = 'MENU_SELECTION';
    case PRODUCT_BROWSING = 'PRODUCT_BROWSING';
    case AWAITING_ORDER_FORM = 'AWAITING_ORDER_FORM';
    case AWAITING_DELIVERY_METHOD = 'AWAITING_DELIVERY_METHOD';
    case AWAITING_LOCATION_OR_ADDRESS = 'AWAITING_LOCATION_OR_ADDRESS';
    case ESCALATED_TO_HUMAN = 'ESCALATED_TO_HUMAN';
    case AWAITING_DELIVERY_SLOT = 'AWAITING_DELIVERY_SLOT';
    case ORDER_SUMMARY = 'ORDER_SUMMARY';
    case AWAITING_PAYMENT_PROOF = 'AWAITING_PAYMENT_PROOF';
    case ORDER_CONFIRMED = 'ORDER_CONFIRMED';
    case CLOSED = 'CLOSED';

    public function label(): string
    {
        return match ($this) {
            self::INIT => 'Inisialisasi',
            self::WELCOME_SENT => 'Pesan Selamat Datang Terkirim',
            self::MENU_SELECTION => 'Menunggu Pilihan Menu',
            self::PRODUCT_BROWSING => 'Menelusuri Produk',
            self::AWAITING_ORDER_FORM => 'Menunggu Formulir Pesanan',
            self::AWAITING_DELIVERY_METHOD => 'Menunggu Metode Pengiriman',
            self::AWAITING_LOCATION_OR_ADDRESS => 'Menunggu Lokasi/Alamat',
            self::ESCALATED_TO_HUMAN => 'Dieskalasi ke Manusia',
            self::AWAITING_DELIVERY_SLOT => 'Menunggu Slot Pengiriman',
            self::ORDER_SUMMARY => 'Ringkasan Pesanan',
            self::AWAITING_PAYMENT_PROOF => 'Menunggu Bukti Pembayaran',
            self::ORDER_CONFIRMED => 'Pesanan Dikonfirmasi',
            self::CLOSED => 'Percakapan Ditutup',
        };
    }

    public function isActive(): bool
    {
        return !in_array($this, [self::CLOSED, self::ESCALATED_TO_HUMAN]);
    }
}
