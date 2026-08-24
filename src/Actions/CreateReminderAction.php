<?php

namespace Spiggle\RulesEngine\Actions;

use Illuminate\Database\Eloquent\Model;
use Spiggle\RulesEngine\Contracts\ActionHandlerInterface;
use Spiggle\RulesEngine\Contracts\SubjectResolverInterface;
use Spiggle\RulesEngine\Enums\ActionType;
use Spiggle\RulesEngine\Models\TriggerRule;

class CreateReminderAction implements ActionHandlerInterface
{
    public function type(): string
    {
        return ActionType::CreateReminder->value;
    }

    public function execute(
        array $config,
        Model $subject,
        ?Model $scope,
        TriggerRule $rule,
        SubjectResolverInterface $resolver,
    ): array {
        $callback = config('rules-engine.create_reminder');

        if (! is_callable($callback)) {
            return ['created' => false, 'skipped' => 'No create_reminder callback configured.'];
        }

        $reminderId = $callback($scope, $subject, $rule, $config);

        return ['reminder_id' => $reminderId];
    }
}
