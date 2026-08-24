<?php

namespace Spiggle\RulesEngine\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Spiggle\RulesEngine\Contracts\ActionHandlerInterface;
use Spiggle\RulesEngine\Contracts\SubjectResolverInterface;
use Spiggle\RulesEngine\Enums\ActionType;
use Spiggle\RulesEngine\Models\TriggerRule;

class SetMemberMetaAction implements ActionHandlerInterface
{
    public function type(): string
    {
        return ActionType::SetMemberMeta->value;
    }

    public function execute(
        array $config,
        Model $subject,
        ?Model $scope,
        TriggerRule $rule,
        SubjectResolverInterface $resolver,
    ): array {
        if (! $scope) {
            throw new \RuntimeException('Scope required for set_member_meta.');
        }

        $meta = $resolver->getPivotMeta($scope, $subject);
        $merged = array_merge($meta, $config);
        $resolver->setPivotMeta($scope, $subject, $merged);

        return ['meta' => $merged];
    }
}
