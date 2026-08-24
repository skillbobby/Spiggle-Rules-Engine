<?php

namespace Spiggle\RulesEngine\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spiggle\RulesEngine\Enums\RuleFrequency;
use Spiggle\RulesEngine\Enums\RulePreset;

class TriggerRule extends Model
{
    protected $fillable = [
        'user_id',
        'scope_type',
        'scope_id',
        'name',
        'preset_key',
        'trigger_config',
        'actions',
        'applies_to',
        'conditions',
        'frequency',
        'priority',
        'is_active',
        'notes',
    ];

    public function getTable(): string
    {
        return config('rules-engine.table_prefix', 're_').'trigger_rules';
    }

    protected function casts(): array
    {
        return [
            'preset_key' => RulePreset::class,
            'trigger_config' => 'array',
            'actions' => 'array',
            'applies_to' => 'array',
            'conditions' => 'array',
            'frequency' => RuleFrequency::class,
            'is_active' => 'boolean',
        ];
    }

    public function scope(): MorphTo
    {
        return $this->morphTo();
    }

    public function executions(): HasMany
    {
        return $this->hasMany(RuleExecution::class, 'trigger_rule_id');
    }
}
