<?php

namespace Spiggle\RulesEngine\Contracts;

use Illuminate\Database\Eloquent\Model;
use Spiggle\RulesEngine\Models\TriggerRule;

interface ActionHandlerInterface
{
    public function type(): string;

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    public function execute(
        array $config,
        Model $subject,
        ?Model $scope,
        TriggerRule $rule,
        SubjectResolverInterface $resolver,
    ): array;
}
