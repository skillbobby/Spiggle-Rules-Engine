<?php

namespace Spiggle\RulesEngine\Enums;

use Filament\Support\Contracts\HasLabel;

enum RuleFrequency: string implements HasLabel
{
    case OnMaintain = 'on_maintain';
    case Daily = 'daily';
    case Weekly = 'weekly';

    public function getLabel(): string
    {
        return match ($this) {
            self::OnMaintain => 'On maintenance run',
            self::Daily => 'Daily',
            self::Weekly => 'Weekly',
        };
    }
}
