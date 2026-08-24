<?php

namespace Spiggle\RulesEngine\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ExecutionStatus: string implements HasColor, HasLabel
{
    case Success = 'success';
    case Failed = 'failed';
    case Skipped = 'skipped';
    case DryRun = 'dry_run';

    public function getLabel(): string
    {
        return match ($this) {
            self::Success => 'Success',
            self::Failed => 'Failed',
            self::Skipped => 'Skipped',
            self::DryRun => 'Dry run',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Success => 'success',
            self::Failed => 'danger',
            self::Skipped => 'gray',
            self::DryRun => 'info',
        };
    }
}
