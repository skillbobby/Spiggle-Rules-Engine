<?php

namespace Spiggle\RulesEngine\Presets;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Spiggle\RulesEngine\Contracts\PresetEvaluatorInterface;
use Spiggle\RulesEngine\Contracts\SubjectResolverInterface;
use Spiggle\RulesEngine\Models\TriggerRule;

class InactivityPreset implements PresetEvaluatorInterface
{
    public function key(): string
    {
        return 'inactivity';
    }

    public function matches(Model $subject, TriggerRule $rule, ?Model $scope, SubjectResolverInterface $resolver): bool
    {
        $lastLogin = $resolver->getAttribute($subject, 'last_login_at')
            ?? $subject->updated_at;

        if (! $lastLogin) {
            return false;
        }

        $days = (int) ($rule->trigger_config['days'] ?? 90);

        return Carbon::parse($lastLogin)->startOfDay()->addDays($days)->lessThanOrEqualTo(now()->startOfDay());
    }

    public function fingerprintSegment(Model $subject, TriggerRule $rule): string
    {
        $days = (int) ($rule->trigger_config['days'] ?? 90);

        return "inactivity:{$days}";
    }
}
