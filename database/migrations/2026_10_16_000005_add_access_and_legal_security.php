<?php

use App\Modules\Access\Services\Permissions;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('type');
            $t->morphs('notifiable');
            $t->text('data');
            $t->timestamp('read_at')->nullable();
            $t->timestamps();
        });
        Schema::create('access_roles', function (Blueprint $t) {
            $t->id();
            $t->string('key', 50)->unique();
            $t->string('name', 100);
            $t->json('permissions');
            $t->boolean('system')->default(false);
            $t->timestamps();
        });
        foreach (Permissions::defaults() as $key => [$name,$permissions]) {
            DB::table('access_roles')->insert(['key' => $key, 'name' => $name, 'permissions' => json_encode($permissions), 'system' => true, 'created_at' => now(), 'updated_at' => now()]);
        }
        Schema::table('users', function (Blueprint $t) {
            $t->timestampTz('suspended_at')->nullable();
            $t->softDeletesTz();
            $t->unsignedInteger('session_version')->default(0);
            $t->text('app_authentication_secret')->nullable();
            $t->text('app_authentication_recovery_codes')->nullable();
        });
        Schema::table('sales_orders', fn (Blueprint $t) => $t->foreignId('assigned_to')->nullable()->constrained('users')->restrictOnDelete());
        Schema::create('legal_documents', function (Blueprint $t) {
            $t->id();
            $t->string('kind', 30);
            $t->string('purpose_key', 80)->default('general');
            $t->string('title', 200);
            $t->string('version', 50);
            $t->text('body');
            $t->string('sha256', 64);
            $t->json('controller_snapshot');
            $t->string('effect', 30);
            $t->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $t->timestampTz('published_at')->nullable();
            $t->string('review_reference', 1000)->nullable();
            $t->timestamps();
            $t->unique(['kind', 'purpose_key', 'version']);
        });
        Schema::create('legal_events', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->restrictOnDelete();
            $t->foreignId('legal_document_id')->constrained()->restrictOnDelete();
            $t->string('action', 30);
            $t->string('document_sha256', 64);
            $t->string('purpose', 200);
            $t->timestampTz('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('legal_events');
        Schema::dropIfExists('legal_documents');
        Schema::table('sales_orders', fn (Blueprint $t) => $t->dropConstrainedForeignId('assigned_to'));
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn(['suspended_at', 'deleted_at', 'session_version', 'app_authentication_secret', 'app_authentication_recovery_codes']));
        Schema::dropIfExists('access_roles');
    }
};
