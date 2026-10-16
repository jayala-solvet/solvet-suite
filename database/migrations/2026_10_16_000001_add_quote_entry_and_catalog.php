<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->string('payment_mode', 20)->nullable();
            $table->unsignedSmallInteger('credit_days')->nullable();
            $table->text('payment_notes')->nullable();
            $table->text('delivery_terms')->nullable();
            $table->text('warranty_terms')->nullable();
            $table->softDeletes();
        });
        Schema::table('companies', fn (Blueprint $table) => $table->text('quote_terms')->nullable());
        Schema::create('catalog_items', function (Blueprint $table) {
            $table->id();
            $table->string('sku', 50)->nullable()->unique();
            $table->string('name', 200);
            $table->text('description')->nullable();
            $table->string('kind', 20)->default('service');
            $table->string('currency', 3);
            $table->decimal('price_minor', 20, 0);
            $table->unsignedSmallInteger('tax_rate')->default(10);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_items');
        Schema::table('companies', fn (Blueprint $table) => $table->dropColumn('quote_terms'));
        Schema::table('quotes', fn (Blueprint $table) => $table->dropColumn(['payment_mode', 'credit_days', 'payment_notes', 'delivery_terms', 'warranty_terms', 'deleted_at']));
    }
};
