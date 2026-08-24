<?php

namespace Spiggle\RulesEngine\Presets;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Spiggle\RulesEngine\Contracts\PresetEvaluatorInterface;
use Spiggle\RulesEngine\Contracts\SubjectResolverInterface;
use Spiggle\RulesEngine\Models\TriggerRule;

class AgeMilestonePreset implements PresetEvaluatorInterface
{
    public function key(): string
    {
        return 'age_milestone';
    }

    public function matches(Model $subject, TriggerRule $rule, ?Model $scope, SubjectResolverInterface $resolver): bool
    {
        $dob = $resolver->getAttribute($subject, 'date_of_birth');

        if (! $dob) {
            return false;
        }

        $age = (int) ($rule->trigger_config['age'] ?? 16);
        $birthday = Carbon::parse($dob);
        $today = now()->startOfDay();

        $milestoneDate = $birthday->copy()->addYears($age)->startOfDay();

        return $today->greaterThanOrEqualTo($milestoneDate);
    }

    public function fingerprintSegment(Model $subject, TriggerRule $rule): string
    {
        $age = (int) ($rule->trigger_config['age'] ?? 16);

        return "age_milestone:{$age}";
    }
}
