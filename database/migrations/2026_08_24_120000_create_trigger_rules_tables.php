<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $prefix = config('rules-engine.table_prefix', 're_');

        Schema::create($prefix.'trigger_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('scope_type')->nullable();
            $table->unsignedBigInteger('scope_id')->nullable();
            $table->string('name');
            $table->string('preset_key');
            $table->json('trigger_config')->nullable();
            $table->json('actions');
            $table->json('applies_to')->nullable();
            $table->string('frequency')->default('on_maintain');
            $table->unsignedSmallInteger('priority')->default(100);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['scope_type', 'scope_id', 'is_active']);
        });

        Schema::create($prefix.'rule_execution_batches', function (Blueprint $table) {
            $table->id();
            $table->string('scope_type')->nullable();
            $table->unsignedBigInteger('scope_id')->nullable();
            $table->string('source')->default('scheduled');
            $table->unsignedInteger('matched')->default(0);
            $table->unsignedInteger('executed')->default(0);
            $table->unsignedInteger('failed')->default(0);
            $table->unsignedInteger('skipped')->default(0);
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });

        Schema::create($prefix.'rule_executions', function (Blueprint $table) use ($prefix) {
            $table->id();
            $table->foreignId('trigger_rule_id')->constrained($prefix.'trigger_rules')->cascadeOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained($prefix.'rule_execution_batches')->nullOnDelete();
            $table->string('subject_type');
            $table->unsignedBigInteger('subject_id');
            $table->string('fingerprint');
            $table->string('status');
            $table->json('payload')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->timestamp('retried_at')->nullable();
            $table->timestamps();
            $table->unique(['trigger_rule_id', 'subject_type', 'subject_id', 'fingerprint'], 're_rule_exec_unique');
            $table->index(['status', 'executed_at']);
        });
    }

    public function down(): void
    {
        $prefix = config('rules-engine.table_prefix', 're_');

        Schema::dropIfExists($prefix.'rule_executions');
        Schema::dropIfExists($prefix.'rule_execution_batches');
        Schema::dropIfExists($prefix.'trigger_rules');
    }
};
