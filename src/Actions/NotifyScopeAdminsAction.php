<?php

namespace Spiggle\RulesEngine\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Spiggle\RulesEngine\Contracts\ActionHandlerInterface;
use Spiggle\RulesEngine\Contracts\SubjectResolverInterface;
use Spiggle\RulesEngine\Enums\ActionType;
use Spiggle\RulesEngine\Models\TriggerRule;

class NotifyScopeAdminsAction implements ActionHandlerInterface
{
    public function type(): string
    {
        return ActionType::NotifyScopeAdmins->value;
    }

    public function execute(
        array $config,
        Model $subject,
        ?Model $scope,
        TriggerRule $rule,
        SubjectResolverInterface $resolver,
    ): array {
        if (! $scope) {
            throw new \RuntimeException('Scope required for notify_scope_admins.');
        }

        $callback = config('rules-engine.notify_scope_admins');

        if (! is_callable($callback)) {
            return ['notified' => 0, 'skipped' => 'No notify callback configured.'];
        }

        $title = (string) ($config['title'] ?? "Rule triggered: {$rule->name}");
        $body = (string) ($config['body'] ?? "Subject {$subject->getKey()} matched {$rule->name}.");

        $count = (int) $callback($scope, $subject, $rule, $title, $body);

        return ['notified' => $count];
    }
}
