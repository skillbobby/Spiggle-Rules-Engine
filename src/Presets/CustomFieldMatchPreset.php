<?php

namespace Spiggle\RulesEngine\Presets;

use Illuminate\Database\Eloquent\Model;
use Spiggle\RulesEngine\Contracts\PresetEvaluatorInterface;
use Spiggle\RulesEngine\Contracts\SubjectResolverInterface;
use Spiggle\RulesEngine\Models\TriggerRule;

class CustomFieldMatchPreset implements PresetEvaluatorInterface
{
    public function key(): string
    {
        return 'custom_field_match';
    }

    public function matches(Model $subject, TriggerRule $rule, ?Model $scope, SubjectResolverInterface $resolver): bool
    {
        $attribute = (string) ($rule->trigger_config['attribute'] ?? '');
        $operator = (string) ($rule->trigger_config['operator'] ?? 'equals');
        $expected = $rule->trigger_config['value'] ?? null;

        if ($attribute === '') {
            return false;
        }

        $actual = $resolver->getAttribute($subject, $attribute);

        return match ($operator) {
            'equals' => (string) $actual === (string) $expected,
            'not_equals' => (string) $actual !== (string) $expected,
            'contains' => str_contains((string) $actual, (string) $expected),
            'gt' => is_numeric($actual) && is_numeric($expected) && (float) $actual > (float) $expected,
            'gte' => is_numeric($actual) && is_numeric($expected) && (float) $actual >= (float) $expected,
            'lt' => is_numeric($actual) && is_numeric($expected) && (float) $actual < (float) $expected,
            'lte' => is_numeric($actual) && is_numeric($expected) && (float) $actual <= (float) $expected,
            default => false,
        };
    }

    public function fingerprintSegment(Model $subject, TriggerRule $rule): string
    {
        $attribute = (string) ($rule->trigger_config['attribute'] ?? 'field');
        $operator = (string) ($rule->trigger_config['operator'] ?? 'equals');
        $value = json_encode($rule->trigger_config['value'] ?? null);

        return "custom_field_match:{$attribute}:{$operator}:{$value}";
    }
}
