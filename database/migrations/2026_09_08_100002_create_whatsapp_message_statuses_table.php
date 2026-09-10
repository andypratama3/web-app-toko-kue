<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_message_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('message_id'); // references whatsapp_message_id
            $table->string('status'); // sent, delivered, read, failed
            $table->string('recipient')->nullable();
            $table->timestamp('timestamp')->nullable();
            $table->json('errors')->nullable();
            $table->timestamps();

            $table->unique(['message_id', 'status']);
            $table->index('message_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_message_statuses');
    }
};
