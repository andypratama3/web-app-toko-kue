<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_broadcast_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('broadcast_id')->constrained('whatsapp_broadcasts')->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('region_id')->nullable()->constrained()->nullOnDelete();
            $table->string('phone', 20);
            $table->string('name')->nullable();
            $table->string('status', 20)->default('pending'); // pending, sent, failed
            $table->string('message_id')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['broadcast_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_broadcast_recipients');
    }
};