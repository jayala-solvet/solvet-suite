<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('catalog_items', function (Blueprint $table) {
            $table->boolean('can_purchase')->default(true);
            $table->boolean('can_sell')->default(true);
            $table->boolean('track_stock')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('catalog_items', fn (Blueprint $table) => $table->dropColumn([
            'can_purchase', 'can_sell', 'track_stock',
        ]));
    }
};
