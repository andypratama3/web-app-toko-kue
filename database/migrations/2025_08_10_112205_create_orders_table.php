<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->string('phone')->nullable(); // Kolom phone
            $table->string('address')->nullable(); // Kolom address
            $table->string('payment_method');
            $table->text('note')->nullable();
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->string('status')->default('pending'); // pending, completed, cancelled, etc.
            // $table->foreignId('courier_id')->nullable()->constrained('users')->onDelete('set null'); // Jika ada kurir
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};