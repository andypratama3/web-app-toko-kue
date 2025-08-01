<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up()
{
    Schema::table('customers', function (Blueprint $table) {
        $table->string('nama')->after('id');
        $table->string('no_hp')->after('nama');
        $table->text('alamat')->nullable()->after('no_hp');
        $table->string('region')->nullable()->after('alamat');
        $table->text('catatan')->nullable()->after('region');
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            //
        });
    }
};
