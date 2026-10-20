<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'delivery_method')) {
                $table->string('delivery_method')->nullable()->index()->after('channel');
            }
            if (! Schema::hasColumn('orders', 'distance_km')) {
                $table->decimal('distance_km', 8, 2)->nullable()->after('delivery_method');
            }
            if (! Schema::hasColumn('orders', 'shipping_fee')) {
                $table->unsignedInteger('shipping_fee')->default(0)->after('distance_km');
            }
            if (! Schema::hasColumn('orders', 'shipping_zone_id')) {
                $table->unsignedBigInteger('shipping_zone_id')->nullable()->after('shipping_fee');
            }
            if (! Schema::hasColumn('orders', 'external_courier_fee')) {
                $table->unsignedInteger('external_courier_fee')->nullable()->after('shipping_zone_id');
            }
            if (! Schema::hasColumn('orders', 'location_source')) {
                $table->string('location_source')->nullable()->after('external_courier_fee');
            }
            if (! Schema::hasColumn('orders', 'delivery_lat')) {
                $table->decimal('delivery_lat', 10, 7)->nullable()->after('location_source');
            }
            if (! Schema::hasColumn('orders', 'delivery_lng')) {
                $table->decimal('delivery_lng', 10, 7)->nullable()->after('delivery_lat');
            }
            if (! Schema::hasColumn('orders', 'delivery_date')) {
                $table->date('delivery_date')->nullable()->after('delivery_lng');
            }
            if (! Schema::hasColumn('orders', 'delivery_slot')) {
                $table->string('delivery_slot')->nullable()->after('delivery_date');
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'delivery_date')) {
                $table->index('delivery_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['delivery_date']);
            $table->dropColumn([
                'delivery_method',
                'distance_km',
                'shipping_fee',
                'shipping_zone_id',
                'external_courier_fee',
                'location_source',
                'delivery_lat',
                'delivery_lng',
                'delivery_date',
                'delivery_slot',
            ]);
        });
    }
};
