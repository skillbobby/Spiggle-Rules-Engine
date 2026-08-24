<?php

namespace Spiggle\RulesEngine\Enums;

use Filament\Support\Contracts\HasLabel;

enum ActionType: string implements HasLabel
{
    case SetMemberMeta = 'set_member_meta';
    case SetScopeSettings = 'set_scope_settings';
    case NotifyScopeAdmins = 'notify_scope_admins';
    case CreateReminder = 'create_reminder';
    case DispatchEvent = 'dispatch_event';

    public function getLabel(): string
    {
        return match ($this) {
            self::SetMemberMeta => 'Set member meta',
            self::SetScopeSettings => 'Set scope settings',
            self::NotifyScopeAdmins => 'Notify scope admins',
            self::CreateReminder => 'Create reminder',
            self::DispatchEvent => 'Dispatch event',
        };
    }
}
