<?php

namespace Spiggle\RulesEngine\Presets;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Spiggle\RulesEngine\Contracts\PresetEvaluatorInterface;
use Spiggle\RulesEngine\Contracts\SubjectResolverInterface;
use Spiggle\RulesEngine\Models\TriggerRule;

class TenureMilestonePreset implements PresetEvaluatorInterface
{
    public function key(): string
    {
        return 'tenure_milestone';
    }

    public function matches(Model $subject, TriggerRule $rule, ?Model $scope, SubjectResolverInterface $resolver): bool
    {
        if (! $scope) {
            return false;
        }

        $joined = $resolver->getPivotCreatedAt($scope, $subject);

        if (! $joined) {
            return false;
        }

        $days = (int) ($rule->trigger_config['days'] ?? 365);

        return Carbon::parse($joined)->startOfDay()->addDays($days)->lessThanOrEqualTo(now()->startOfDay());
    }

    public function fingerprintSegment(Model $subject, TriggerRule $rule): string
    {
        $days = (int) ($rule->trigger_config['days'] ?? 365);

        return "tenure_milestone:{$days}";
    }
}
