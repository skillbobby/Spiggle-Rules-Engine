<?php

namespace Spiggle\RulesEngine\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Spiggle\RulesEngine\Contracts\ActionHandlerInterface;
use Spiggle\RulesEngine\Contracts\SubjectResolverInterface;
use Spiggle\RulesEngine\Enums\ActionType;
use Spiggle\RulesEngine\Events\RuleTriggered;
use Spiggle\RulesEngine\Models\TriggerRule;

class DispatchEventAction implements ActionHandlerInterface
{
    public function type(): string
    {
        return ActionType::DispatchEvent->value;
    }

    public function execute(
        array $config,
        Model $subject,
        ?Model $scope,
        TriggerRule $rule,
        SubjectResolverInterface $resolver,
    ): array {
        $eventClass = $config['event'] ?? RuleTriggered::class;

        if (class_exists($eventClass)) {
            Event::dispatch(new $eventClass($rule, $subject, $scope, $config));
        } else {
            Event::dispatch(new RuleTriggered($rule, $subject, $scope, $config));
        }

        return ['event' => $eventClass];
    }
}
