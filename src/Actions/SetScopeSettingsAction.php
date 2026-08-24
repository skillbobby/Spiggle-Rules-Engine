<?php

namespace Spiggle\RulesEngine\Actions;

use Illuminate\Database\Eloquent\Model;
use Spiggle\RulesEngine\Contracts\ActionHandlerInterface;
use Spiggle\RulesEngine\Contracts\SubjectResolverInterface;
use Spiggle\RulesEngine\Enums\ActionType;
use Spiggle\RulesEngine\Models\TriggerRule;

class SetScopeSettingsAction implements ActionHandlerInterface
{
    public function type(): string
    {
        return ActionType::SetScopeSettings->value;
    }

    public function execute(
        array $config,
        Model $subject,
        ?Model $scope,
        TriggerRule $rule,
        SubjectResolverInterface $resolver,
    ): array {
        if (! $scope) {
            throw new \RuntimeException('Scope required for set_scope_settings.');
        }

        $column = config('rules-engine.scope_settings_column', 'settings');
        $current = $scope->{$column} ?? [];
        $merged = array_merge(is_array($current) ? $current : [], $config);
        $scope->forceFill([$column => $merged])->save();

        return ['settings' => $merged];
    }
}
