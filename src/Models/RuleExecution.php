<?php

namespace Spiggle\RulesEngine\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spiggle\RulesEngine\Enums\ExecutionStatus;

class RuleExecution extends Model
{
    protected $fillable = [
        'trigger_rule_id',
        'batch_id',
        'subject_type',
        'subject_id',
        'fingerprint',
        'status',
        'payload',
        'error',
        'executed_at',
        'retried_at',
    ];

    public function getTable(): string
    {
        return config('rules-engine.table_prefix', 're_').'rule_executions';
    }

    protected function casts(): array
    {
        return [
            'status' => ExecutionStatus::class,
            'payload' => 'array',
            'executed_at' => 'datetime',
            'retried_at' => 'datetime',
        ];
    }

    public function triggerRule(): BelongsTo
    {
        return $this->belongsTo(TriggerRule::class, 'trigger_rule_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(RuleExecutionBatch::class, 'batch_id');
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
