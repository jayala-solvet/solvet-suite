<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cost_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('catalog_item_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('source', 20);
            $table->string('supplier_name', 200)->nullable();
            $table->string('supplier_tax_id', 30)->nullable();
            $table->string('reference', 200)->nullable();
            $table->string('currency', 3);
            $table->string('unit', 15);
            $table->unsignedSmallInteger('purchase_tax_rate');
            $table->unsignedSmallInteger('sales_tax_rate');
            $table->string('vat_treatment', 25);
            $table->decimal('purchase_minor', 20, 0);
            $table->decimal('extra_minor', 20, 0);
            $table->decimal('effective_unit_minor', 28, 8)->nullable();
            $table->string('pricing_mode', 20);
            $table->decimal('pricing_value', 14, 2);
            $table->decimal('suggested_price_minor', 20, 0)->nullable();
            $table->date('cost_date');
            $table->date('valid_until')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['catalog_item_id', 'created_at']);
        });
        Schema::table('catalog_items', function (Blueprint $table) {
            $table->string('unit', 15)->default('unit');
            $table->foreignId('current_cost_id')->nullable()->constrained('cost_profiles')->restrictOnDelete();
        });
        Schema::table('quotes', fn (Blueprint $table) => $table->json('cost_snapshot')->nullable());
    }

    public function down(): void
    {
        Schema::table('quotes', fn (Blueprint $table) => $table->dropColumn('cost_snapshot'));
        Schema::table('catalog_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('current_cost_id');
            $table->dropColumn('unit');
        });
        Schema::dropIfExists('cost_profiles');
    }
};
