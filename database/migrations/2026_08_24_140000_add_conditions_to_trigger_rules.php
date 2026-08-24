<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $prefix = config('rules-engine.table_prefix', 're_');

        Schema::table($prefix.'trigger_rules', function (Blueprint $table) {
            $table->json('conditions')->nullable()->after('applies_to');
        });
    }

    public function down(): void
    {
        $prefix = config('rules-engine.table_prefix', 're_');

        Schema::table($prefix.'trigger_rules', function (Blueprint $table) {
            $table->dropColumn('conditions');
        });
    }
};
