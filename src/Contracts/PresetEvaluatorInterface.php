<?php

namespace Spiggle\RulesEngine\Contracts;

use Illuminate\Database\Eloquent\Model;
use Spiggle\RulesEngine\Models\TriggerRule;

interface PresetEvaluatorInterface
{
    public function key(): string;

    public function matches(Model $subject, TriggerRule $rule, ?Model $scope, SubjectResolverInterface $resolver): bool;

    public function fingerprintSegment(Model $subject, TriggerRule $rule): string;
}
