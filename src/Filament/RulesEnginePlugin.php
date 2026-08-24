<?php

namespace Spiggle\RulesEngine\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Spiggle\RulesEngine\Filament\Pages\RulesMonitorPage;
use Spiggle\RulesEngine\Filament\Resources\RuleExecutions\RuleExecutionResource;
use Spiggle\RulesEngine\Filament\Resources\TriggerRules\TriggerRuleResource;

class RulesEnginePlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }

    public function getId(): string
    {
        return 'spiggle-rules-engine';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->resources([
                TriggerRuleResource::class,
                RuleExecutionResource::class,
            ])
            ->pages([
                RulesMonitorPage::class,
            ]);
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
