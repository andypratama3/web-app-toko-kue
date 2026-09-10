<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('conversation_id');
            $table->string('whatsapp_message_id')->unique(); // Meta message ID for dedup
            $table->string('sender_type')->default('customer'); // customer, bot, admin
            $table->string('message_type')->default('text'); // text, image, document, interactive, location, sticker, video
            $table->text('content')->nullable();
            $table->string('media_url')->nullable();
            $table->string('media_type')->nullable();
            $table->string('status')->default('sent'); // sent, delivered, read, failed
            $table->timestamps();

            $table->foreign('conversation_id')->references('id')->on('whatsapp_conversations')->cascadeOnDelete();
            $table->index('conversation_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_messages');
    }
};
