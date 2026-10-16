<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cost_profiles', function (Blueprint $t) {
            $t->unsignedSmallInteger('extra_tax_rate')->default(0);
            $t->string('extra_vat_treatment', 25)->default('non_recoverable');
        });
    }

    public function down(): void
    {
        Schema::table('cost_profiles', fn (Blueprint $t) => $t->dropColumn(['extra_tax_rate', 'extra_vat_treatment']));
    }
};
