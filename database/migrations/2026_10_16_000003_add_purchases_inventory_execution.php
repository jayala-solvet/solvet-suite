<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('catalog_items', function (Blueprint $t) {
            $t->boolean('can_buy')->default(true);
            $t->boolean('can_sell')->default(true);
            $t->boolean('track_stock')->default(false);
        });
        Schema::create('sales_orders', function (Blueprint $t) {
            $t->id();
            $t->foreignId('quote_id')->unique()->constrained()->restrictOnDelete();
            $t->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $t->string('status', 20)->default('confirmed');
            $t->string('currency', 3);
            $t->decimal('sales_minor', 20, 0);
            $t->decimal('sales_tax_minor', 20, 0);
            $t->json('estimated_snapshot')->nullable();
            $t->timestampTz('completed_at')->nullable();
            $t->timestamps();
        });
        Schema::create('order_lines', function (Blueprint $t) {
            $t->id();
            $t->foreignId('sales_order_id')->constrained()->restrictOnDelete();
            $t->unsignedInteger('quote_line_index');
            $t->foreignId('catalog_item_id')->nullable()->constrained()->restrictOnDelete();
            $t->string('description', 1000);
            $t->string('kind', 20);
            $t->string('unit', 15);
            $t->boolean('track_stock');
            $t->decimal('quantity', 18, 3);
            $t->decimal('reserved_quantity', 18, 3)->default(0);
            $t->decimal('done_quantity', 18, 3)->default(0);
            $t->decimal('estimated_unit_minor', 28, 8)->nullable();
            $t->string('source', 20)->nullable();
            $t->foreignId('cost_profile_id')->nullable()->constrained()->restrictOnDelete();
            $t->timestamps();
        });
        Schema::create('purchases', function (Blueprint $t) {
            $t->id();
            $t->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $t->string('supplier_name', 200);
            $t->string('supplier_tax_id', 30);
            $t->string('reference', 200);
            $t->string('currency', 3);
            $t->date('purchase_date');
            $t->string('status', 20)->default('draft');
            $t->json('lines');
            $t->decimal('total_minor', 20, 0);
            $t->decimal('freight_minor', 20, 0);
            $t->unsignedSmallInteger('freight_tax_rate');
            $t->string('freight_vat_treatment', 25);
            $t->string('freight_method', 20);
            $t->timestampTz('received_at')->nullable();
            $t->timestamps();
        });
        Schema::create('stock_balances', function (Blueprint $t) {
            $t->foreignId('catalog_item_id')->primary()->constrained()->restrictOnDelete();
            $t->decimal('quantity', 18, 3)->default(0);
            $t->decimal('reserved', 18, 3)->default(0);
            $t->decimal('value_minor', 28, 8)->default(0);
            $t->timestamps();
        });
        Schema::create('stock_movements', function (Blueprint $t) {
            $t->id();
            $t->foreignId('catalog_item_id')->constrained()->restrictOnDelete();
            $t->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $t->string('kind', 30);
            $t->decimal('quantity', 18, 3);
            $t->decimal('value_minor', 28, 8);
            $t->decimal('unit_cost_minor', 28, 8);
            $t->foreignId('purchase_id')->nullable()->constrained()->restrictOnDelete();
            $t->foreignId('order_line_id')->nullable()->constrained()->restrictOnDelete();
            $t->unsignedBigInteger('original_movement_id')->nullable();
            $t->decimal('returned_quantity', 18, 3)->default(0);
            $t->string('operation_key', 150)->unique();
            $t->string('request_hash', 64);
            $t->string('reason', 1000);
            $t->timestamps();
        });
        Schema::create('execution_entries', function (Blueprint $t) {
            $t->id();
            $t->foreignId('order_line_id')->constrained()->restrictOnDelete();
            $t->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $t->foreignId('purchase_id')->nullable()->constrained()->restrictOnDelete();
            $t->string('source', 20);
            $t->decimal('quantity', 18, 3);
            $t->decimal('cost_minor', 28, 8);
            $t->string('reference', 1000);
            $t->timestampTz('voided_at')->nullable();
            $t->string('void_reason', 1000)->nullable();
            $t->string('operation_key', 150)->unique();
            $t->string('request_hash', 64);
            $t->timestamps();
        });
        Schema::create('operation_batches', function (Blueprint $t) {
            $t->id();
            $t->string('operation_key', 150)->unique();
            $t->string('request_hash', 64);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['operation_batches', 'execution_entries', 'stock_movements', 'stock_balances', 'purchases', 'order_lines', 'sales_orders'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::table('catalog_items', fn (Blueprint $t) => $t->dropColumn(['can_buy', 'can_sell', 'track_stock']));
    }
};
