<?php

namespace Spiggle\RulesEngine\Enums;

use Filament\Support\Contracts\HasLabel;

enum RulePreset: string implements HasLabel
{
    case AgeMilestone = 'age_milestone';
    case TenureMilestone = 'tenure_milestone';
    case Inactivity = 'inactivity';
    case MetricThreshold = 'metric_threshold';
    case CustomFieldMatch = 'custom_field_match';

    public function getLabel(): string
    {
        return match ($this) {
            self::AgeMilestone => 'Age milestone',
            self::TenureMilestone => 'Tenure anniversary',
            self::Inactivity => 'Inactivity / dormancy',
            self::MetricThreshold => 'Metric threshold',
            self::CustomFieldMatch => 'Custom field match',
        };
    }

    public function configFields(): array
    {
        return match ($this) {
            self::AgeMilestone => ['age' => 16],
            self::TenureMilestone => ['days' => 365],
            self::Inactivity => ['days' => 90],
            self::MetricThreshold => ['metric' => 'monthly_spend', 'threshold' => 500],
            self::CustomFieldMatch => ['attribute' => '', 'operator' => 'equals', 'value' => ''],
        };
    }
}
