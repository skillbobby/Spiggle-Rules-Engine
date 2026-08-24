<?php

namespace Spiggle\RulesEngine\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Spiggle\RulesEngine\Models\RuleExecutionBatch;
use Spiggle\RulesEngine\Models\TriggerRule;
use Spiggle\RulesEngine\Services\RuleEvaluator;
use Spiggle\RulesEngine\Support\AuthorizesRulesEngine;
use UnitEnum;

class RulesMonitorPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static ?string $navigationLabel = 'Rules monitor';

    protected static ?string $title = 'Rules monitor';

    protected static ?string $slug = 'rules-monitor';

    protected static ?int $navigationSort = 12;

    protected string $view = 'rules-engine::pages.rules-monitor';

    public ?int $lastMatched = null;

    public ?int $lastExecuted = null;

    public ?int $lastFailed = null;

    public ?int $lastSkipped = null;

    public ?string $lastRunAt = null;

    public ?string $lastSource = null;

    public ?string $nextScheduledAt = null;

    public ?string $scheduleDescription = null;

    public int $activeRuleCount = 0;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return config('rules-engine.navigation_group', 'Insights');
    }

    public static function canAccess(): bool
    {
        return AuthorizesRulesEngine::canManage();
    }

    public function mount(): void
    {
        $this->refreshMonitor();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('runNow')
                ->label('Run now')
                ->icon(Heroicon::OutlinedPlay)
                ->action(function (RuleEvaluator $evaluator): void {
                    $tenant = Filament::getTenant();
                    $result = $evaluator->evaluate($tenant, null, false, 'manual');

                    Notification::make()
                        ->title('Rules evaluated')
                        ->body("Matched {$result['matched']}, executed {$result['executed']}, failed {$result['failed']}, skipped {$result['skipped']}.")
                        ->success()
                        ->send();

                    $this->refreshMonitor();
                }),
        ];
    }

    protected function refreshMonitor(): void
    {
        $this->loadLastBatch();
        $this->loadScheduleInfo();
        $this->loadActiveRuleCount();
    }

    protected function loadLastBatch(): void
    {
        $tenant = Filament::getTenant();

        if (! $tenant) {
            return;
        }

        $batch = RuleExecutionBatch::query()
            ->where('scope_type', $tenant->getMorphClass())
            ->where('scope_id', $tenant->getKey())
            ->latest('started_at')
            ->first();

        if (! $batch) {
            $this->lastMatched = null;
            $this->lastExecuted = null;
            $this->lastFailed = null;
            $this->lastSkipped = null;
            $this->lastRunAt = null;
            $this->lastSource = null;

            return;
        }

        $this->lastMatched = $batch->matched;
        $this->lastExecuted = $batch->executed;
        $this->lastFailed = $batch->failed;
        $this->lastSkipped = $batch->skipped;
        $this->lastRunAt = $batch->started_at?->toDateTimeString();
        $this->lastSource = $batch->source;
    }

    protected function loadScheduleInfo(): void
    {
        $configured = config('rules-engine.schedule_description');
        $this->scheduleDescription = is_string($configured) && $configured !== ''
            ? $configured
            : 'Laravel scheduler → host maintenance command (daily)';

        $next = config('rules-engine.next_run_resolver');

        if (is_callable($next)) {
            $resolved = $next();
            $this->nextScheduledAt = $resolved instanceof Carbon
                ? $resolved->toDateTimeString()
                : (is_string($resolved) ? $resolved : null);

            return;
        }

        // Default: assume daily at 01:15 (common host pattern); next occurrence from last run or now.
        $hour = (int) config('rules-engine.schedule_hour', 1);
        $minute = (int) config('rules-engine.schedule_minute', 15);
        $candidate = now()->copy()->setTime($hour, $minute, 0);

        if ($candidate->lessThanOrEqualTo(now())) {
            $candidate->addDay();
        }

        $this->nextScheduledAt = $candidate->toDateTimeString();
    }

    protected function loadActiveRuleCount(): void
    {
        $tenant = Filament::getTenant();

        if (! $tenant) {
            $this->activeRuleCount = 0;

            return;
        }

        $this->activeRuleCount = TriggerRule::query()
            ->where('is_active', true)
            ->where('scope_type', $tenant->getMorphClass())
            ->where('scope_id', $tenant->getKey())
            ->count();
    }
}
