<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->string('role')->default('reader');
            $t->boolean('is_active')->default(true);
        });
        Schema::create('companies', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('tax_id')->nullable();
            $t->string('email')->nullable();
            $t->string('phone')->nullable();
            $t->text('address')->nullable();
            $t->timestamps();
        });
        Schema::create('customers', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('tax_id')->nullable();
            $t->string('contact')->nullable();
            $t->string('email')->nullable();
            $t->string('phone')->nullable();
            $t->text('address')->nullable();
            $t->timestamps();
        });
        Schema::create('document_sequences', function (Blueprint $t) {
            $t->string('key')->primary();
            $t->unsignedBigInteger('next_number')->default(1);
        });
        Schema::create('quotes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('customer_id')->constrained()->restrictOnDelete();
            $t->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $t->unsignedBigInteger('number')->nullable()->unique();
            $t->string('subject');
            $t->string('currency', 3);
            $t->string('status')->default('draft')->index();
            $t->date('valid_until');
            $t->json('lines');
            $t->decimal('subtotal_minor', 20, 0);
            $t->decimal('discount_minor', 20, 0);
            $t->decimal('tax_minor', 20, 0);
            $t->decimal('total_minor', 20, 0);
            $t->text('terms')->nullable();
            $t->json('snapshot')->nullable();
            $t->string('pdf_path')->nullable();
            $t->string('pdf_sha256', 64)->nullable();
            $t->timestampTz('issued_at')->nullable();
            $t->timestamps();
        });
        Schema::create('audit_events', function (Blueprint $t) {
            $t->id();
            $t->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('action');
            $t->string('subject_type');
            $t->unsignedBigInteger('subject_id');
            $t->json('metadata');
            $t->timestampTz('created_at')->useCurrent();
            $t->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        foreach (['audit_events', 'quotes', 'document_sequences', 'customers', 'companies'] as $n) {
            Schema::dropIfExists($n);
        }Schema::table('users', function (Blueprint $t) {
            $t->dropColumn(['role', 'is_active']);
        });
    }
};
