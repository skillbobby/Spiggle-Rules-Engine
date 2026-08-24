<?php

namespace Spiggle\RulesEngine\Presets;

use Illuminate\Database\Eloquent\Model;
use Spiggle\RulesEngine\Contracts\MetricProviderInterface;
use Spiggle\RulesEngine\Contracts\PresetEvaluatorInterface;
use Spiggle\RulesEngine\Contracts\SubjectResolverInterface;
use Spiggle\RulesEngine\Models\TriggerRule;

class MetricThresholdPreset implements PresetEvaluatorInterface
{
    public function __construct(
        protected ?MetricProviderInterface $metrics = null,
    ) {}

    public function key(): string
    {
        return 'metric_threshold';
    }

    public function matches(Model $subject, TriggerRule $rule, ?Model $scope, SubjectResolverInterface $resolver): bool
    {
        $metrics = $this->metrics;

        if (! $metrics && app()->bound(MetricProviderInterface::class)) {
            $metrics = app(MetricProviderInterface::class);
        }

        if (! $metrics) {
            return false;
        }

        $metric = (string) ($rule->trigger_config['metric'] ?? '');
        $threshold = (float) ($rule->trigger_config['threshold'] ?? 0);
        $value = $metrics->value($subject, $scope, $metric);

        return $value !== null && $value >= $threshold;
    }

    public function fingerprintSegment(Model $subject, TriggerRule $rule): string
    {
        $metric = (string) ($rule->trigger_config['metric'] ?? 'metric');
        $threshold = (string) ($rule->trigger_config['threshold'] ?? '0');

        return "metric_threshold:{$metric}:{$threshold}";
    }
}
