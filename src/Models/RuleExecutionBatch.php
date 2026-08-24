<?php

namespace Spiggle\RulesEngine\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RuleExecutionBatch extends Model
{
    protected $fillable = [
        'scope_type',
        'scope_id',
        'source',
        'matched',
        'executed',
        'failed',
        'skipped',
        'started_at',
        'finished_at',
    ];

    public function getTable(): string
    {
        return config('rules-engine.table_prefix', 're_').'rule_execution_batches';
    }

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function executions(): HasMany
    {
        return $this->hasMany(RuleExecution::class, 'batch_id');
    }
}
