<?php

namespace Spiggle\RulesEngine\Services;

use Spiggle\RulesEngine\Actions\CreateReminderAction;
use Spiggle\RulesEngine\Actions\DispatchEventAction;
use Spiggle\RulesEngine\Actions\NotifyScopeAdminsAction;
use Spiggle\RulesEngine\Actions\SetMemberMetaAction;
use Spiggle\RulesEngine\Actions\SetScopeSettingsAction;
use Spiggle\RulesEngine\Contracts\ActionHandlerInterface;

class ActionRegistry
{
    /** @var array<string, ActionHandlerInterface> */
    protected array $handlers = [];

    public function __construct()
    {
        foreach ([
            new SetMemberMetaAction,
            new SetScopeSettingsAction,
            new NotifyScopeAdminsAction,
            new CreateReminderAction,
            new DispatchEventAction,
        ] as $handler) {
            $this->register($handler);
        }
    }

    public function register(ActionHandlerInterface $handler): void
    {
        $this->handlers[$handler->type()] = $handler;
    }

    public function get(string $type): ?ActionHandlerInterface
    {
        return $this->handlers[$type] ?? null;
    }

    /** @return array<string, ActionHandlerInterface> */
    public function all(): array
    {
        return $this->handlers;
    }
}
