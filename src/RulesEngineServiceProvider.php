<?php

namespace Spiggle\RulesEngine;

use Illuminate\Support\ServiceProvider;
use Spiggle\RulesEngine\Contracts\MetricProviderInterface;
use Spiggle\RulesEngine\Contracts\ScopeResolverInterface;
use Spiggle\RulesEngine\Contracts\SubjectResolverInterface;
use Spiggle\RulesEngine\Presets\MetricThresholdPreset;
use Spiggle\RulesEngine\Services\ActionRegistry;
use Spiggle\RulesEngine\Services\PresetRegistry;
use Spiggle\RulesEngine\Services\RuleEvaluator;

class RulesEngineServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/rules-engine.php', 'rules-engine');

        $this->app->singleton(PresetRegistry::class, function ($app) {
            $metrics = $app->bound(MetricProviderInterface::class)
                ? $app->make(MetricProviderInterface::class)
                : null;

            return new PresetRegistry(new MetricThresholdPreset($metrics));
        });

        $this->app->singleton(ActionRegistry::class);
        $this->app->singleton(RuleEvaluator::class, function ($app) {
            $subjectResolver = null;
            $subjectClass = config('rules-engine.subject_resolver');

            if ($subjectClass && class_exists($subjectClass)) {
                $subjectResolver = $app->make($subjectClass);
            }

            $scopeResolver = null;
            $scopeClass = config('rules-engine.scope_resolver');

            if ($scopeClass && class_exists($scopeClass)) {
                $scopeResolver = $app->make($scopeClass);
            }

            return new RuleEvaluator(
                $app->make(PresetRegistry::class),
                $app->make(ActionRegistry::class),
                $app->make(\Spiggle\RulesEngine\Services\IdempotencyGuard::class),
                $app->make(\Spiggle\RulesEngine\Services\SubjectLocker::class),
                $app->make(\Spiggle\RulesEngine\Services\ConditionMatcher::class),
                $subjectResolver instanceof SubjectResolverInterface ? $subjectResolver : null,
                $scopeResolver instanceof ScopeResolverInterface ? $scopeResolver : null,
            );
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/rules-engine.php' => config_path('rules-engine.php'),
        ], 'rules-engine-config');

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'rules-engine');
    }
}
