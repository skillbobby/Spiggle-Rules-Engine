<?php

namespace Spiggle\RulesEngine\Services;

use Illuminate\Database\Eloquent\Model;
use Spiggle\RulesEngine\Contracts\SubjectResolverInterface;
use Spiggle\RulesEngine\Models\TriggerRule;

class ConditionMatcher
{
    /**
     * All conditions must pass (AND). Empty conditions = pass.
     */
    public function matches(Model $subject, TriggerRule $rule, SubjectResolverInterface $resolver): bool
    {
        $conditions = $rule->conditions ?? [];

        if ($conditions === [] || $conditions === null) {
            return true;
        }

        foreach ($conditions as $condition) {
            $attribute = (string) ($condition['attribute'] ?? '');
            $operator = (string) ($condition['operator'] ?? 'equals');
            $expected = $condition['value'] ?? null;

            if ($attribute === '') {
                continue;
            }

            $actual = $resolver->getAttribute($subject, $attribute);

            $ok = match ($operator) {
                'equals' => (string) $actual === (string) $expected,
                'not_equals' => (string) $actual !== (string) $expected,
                'contains' => str_contains((string) $actual, (string) $expected),
                'gt' => is_numeric($actual) && is_numeric($expected) && (float) $actual > (float) $expected,
                'gte' => is_numeric($actual) && is_numeric($expected) && (float) $actual >= (float) $expected,
                'lt' => is_numeric($actual) && is_numeric($expected) && (float) $actual < (float) $expected,
                'lte' => is_numeric($actual) && is_numeric($expected) && (float) $actual <= (float) $expected,
                'is_set' => filled($actual),
                'is_empty' => blank($actual),
                default => false,
            };

            if (! $ok) {
                return false;
            }
        }

        return true;
    }
}
