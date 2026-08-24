<?php

namespace Spiggle\RulesEngine\Services;

use Spiggle\RulesEngine\Contracts\PresetEvaluatorInterface;
use Spiggle\RulesEngine\Presets\AgeMilestonePreset;
use Spiggle\RulesEngine\Presets\CustomFieldMatchPreset;
use Spiggle\RulesEngine\Presets\InactivityPreset;
use Spiggle\RulesEngine\Presets\MetricThresholdPreset;
use Spiggle\RulesEngine\Presets\TenureMilestonePreset;

class PresetRegistry
{
    /** @var array<string, PresetEvaluatorInterface> */
    protected array $presets = [];

    public function __construct(?MetricThresholdPreset $metricThreshold = null)
    {
        $this->register(new AgeMilestonePreset);
        $this->register(new TenureMilestonePreset);
        $this->register(new InactivityPreset);
        $this->register(new CustomFieldMatchPreset);

        if ($metricThreshold) {
            $this->register($metricThreshold);
        }
    }

    public function register(PresetEvaluatorInterface $preset): void
    {
        $this->presets[$preset->key()] = $preset;
    }

    public function get(string $key): ?PresetEvaluatorInterface
    {
        return $this->presets[$key] ?? null;
    }

    /** @return array<string, PresetEvaluatorInterface> */
    public function all(): array
    {
        return $this->presets;
    }
}
