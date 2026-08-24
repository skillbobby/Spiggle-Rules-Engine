<?php

namespace Spiggle\RulesEngine\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Spiggle\RulesEngine\Contracts\ScopeResolverInterface;
use Spiggle\RulesEngine\Contracts\SubjectResolverInterface;
use Spiggle\RulesEngine\Data\EvaluationResult;
use Spiggle\RulesEngine\Enums\ExecutionStatus;
use Spiggle\RulesEngine\Enums\RuleFrequency;
use Spiggle\RulesEngine\Models\RuleExecution;
use Spiggle\RulesEngine\Models\RuleExecutionBatch;
use Spiggle\RulesEngine\Models\TriggerRule;
use Throwable;

class RuleEvaluator
{
    public function __construct(
        protected PresetRegistry $presets,
        protected ActionRegistry $actions,
        protected IdempotencyGuard $idempotency,
        protected SubjectLocker $locker,
        protected ConditionMatcher $conditions,
        protected ?SubjectResolverInterface $subjectResolver = null,
        protected ?ScopeResolverInterface $scopeResolver = null,
    ) {}

    /**
     * @return array{matched: int, executed: int, failed: int, skipped: int, batch_id: int|null}
     */
    public function evaluateAllActiveRules(string $source = 'scheduled'): array
    {
        $totals = ['matched' => 0, 'executed' => 0, 'failed' => 0, 'skipped' => 0, 'batch_id' => null];

        $scopes = $this->resolveScopes(null);

        foreach ($scopes as $scope) {
            $result = $this->evaluate($scope, null, false, $source);
            $totals['matched'] += $result['matched'];
            $totals['executed'] += $result['executed'];
            $totals['failed'] += $result['failed'];
            $totals['skipped'] += $result['skipped'];
            $totals['batch_id'] = $result['batch_id'];
        }

        return $totals;
    }

    /**
     * @return array{matched: int, executed: int, failed: int, skipped: int, batch_id: int|null, dry_run?: list<array<string, mixed>>}
     */
    public function evaluateForSubject(Model $subject, string $source = 'manual', bool $dryRun = false): array
    {
        $totals = ['matched' => 0, 'executed' => 0, 'failed' => 0, 'skipped' => 0, 'batch_id' => null, 'dry_run' => []];

        foreach ($this->resolveScopes(null) as $scope) {
            $result = $this->evaluate($scope, $subject, $dryRun, $source);
            $totals['matched'] += $result['matched'];
            $totals['executed'] += $result['executed'];
            $totals['failed'] += $result['failed'];
            $totals['skipped'] += $result['skipped'];
            $totals['batch_id'] = $result['batch_id'];

            if ($dryRun) {
                $totals['dry_run'] = array_merge($totals['dry_run'], $result['dry_run'] ?? []);
            }
        }

        if (! $dryRun) {
            unset($totals['dry_run']);
        }

        return $totals;
    }

    /**
     * @return array{matched: int, executed: int, failed: int, skipped: int, batch_id: int|null, dry_run?: list<array<string, mixed>>}
     */
    public function evaluate(
        ?Model $scope,
        ?Model $subject = null,
        bool $dryRun = false,
        string $source = 'manual',
        bool $allowRetry = false,
        ?TriggerRule $onlyRule = null,
    ): array {
        $resolver = $this->subjectResolver();
        $rules = $onlyRule
            ? collect([$onlyRule])->filter(fn (TriggerRule $r): bool => $r->is_active)
            : $this->activeRulesForScope($scope);

        $batch = null;
        $matched = 0;
        $executed = 0;
        $failed = 0;
        $skipped = 0;
        $dryRunResults = [];

        $ensureBatch = function () use (&$batch, $scope, $source, $dryRun): void {
            if ($dryRun || $batch) {
                return;
            }

            $batch = RuleExecutionBatch::query()->create([
                'scope_type' => $scope?->getMorphClass(),
                'scope_id' => $scope?->getKey(),
                'source' => $source,
                'started_at' => now(),
            ]);
        };

        foreach ($rules as $rule) {
            if (! $this->frequencyApplies($rule, $source, $dryRun)) {
                continue;
            }

            $subjects = $resolver->resolve($scope, $rule, $subject);

            foreach ($subjects as $ruleSubject) {
                $preset = $this->presets->get($rule->preset_key->value ?? (string) $rule->preset_key);

                if (! $preset || ! $preset->matches($ruleSubject, $rule, $scope, $resolver)) {
                    continue;
                }

                if (! $this->conditions->matches($ruleSubject, $rule, $resolver)) {
                    continue;
                }

                $matched++;
                $ensureBatch();
                $segment = $preset->fingerprintSegment($ruleSubject, $rule).'|'.$this->periodSegment($rule);
                $fingerprint = $this->idempotency->fingerprint($rule->getKey(), $ruleSubject, $segment);

                if ($this->idempotency->shouldSkip($rule->getKey(), $ruleSubject, $fingerprint, $allowRetry)) {
                    $skipped++;

                    if (! $dryRun) {
                        $this->logExecution($rule, $ruleSubject, $fingerprint, EvaluationResult::skipped(), $batch?->getKey());
                    }

                    continue;
                }

                if ($dryRun) {
                    $dryRunResults[] = [
                        'rule' => $rule->name,
                        'rule_id' => $rule->getKey(),
                        'subject_id' => $ruleSubject->getKey(),
                        'subject' => $ruleSubject->name ?? (string) $ruleSubject->getKey(),
                        'actions' => $rule->actions,
                    ];
                    $executed++;

                    continue;
                }

                $result = $this->executeRule($rule, $ruleSubject, $scope, $fingerprint, $resolver, $allowRetry, $batch?->getKey());

                match ($result->status) {
                    ExecutionStatus::Success => $executed++,
                    ExecutionStatus::Failed => $failed++,
                    ExecutionStatus::Skipped => $skipped++,
                    default => null,
                };
            }
        }

        if ($batch) {
            $batch->update([
                'matched' => $matched,
                'executed' => $executed,
                'failed' => $failed,
                'skipped' => $skipped,
                'finished_at' => now(),
            ]);
        }

        $payload = [
            'matched' => $matched,
            'executed' => $executed,
            'failed' => $failed,
            'skipped' => $skipped,
            'batch_id' => $batch?->getKey(),
        ];

        if ($dryRun) {
            $payload['dry_run'] = $dryRunResults;
        }

        return $payload;
    }

    public function retryExecution(RuleExecution $execution): EvaluationResult
    {
        $rule = $execution->triggerRule;
        $subject = $execution->subject;

        if (! $rule || ! $subject) {
            return EvaluationResult::failed('Missing rule or subject.');
        }

        $scope = $rule->scope;

        $result = $this->executeRule(
            $rule,
            $subject,
            $scope,
            $execution->fingerprint,
            $this->subjectResolver(),
            true,
            $execution->batch_id,
        );

        $execution->update(['retried_at' => now()]);

        return $result;
    }

    protected function executeRule(
        TriggerRule $rule,
        Model $subject,
        ?Model $scope,
        string $fingerprint,
        SubjectResolverInterface $resolver,
        bool $allowRetry,
        ?int $batchId,
    ): EvaluationResult {
        try {
            return $this->locker->withLock($subject, function () use ($rule, $subject, $scope, $fingerprint, $resolver, $allowRetry, $batchId) {
                if ($this->idempotency->shouldSkip($rule->getKey(), $subject, $fingerprint, $allowRetry)) {
                    $result = EvaluationResult::skipped();

                    $this->logExecution($rule, $subject, $fingerprint, $result, $batchId);

                    return $result;
                }

                $actionResults = [];

                foreach ($rule->actions as $actionDef) {
                    $type = (string) ($actionDef['type'] ?? '');
                    $config = (array) ($actionDef['config'] ?? $actionDef);
                    unset($config['type']);

                    $handler = $this->actions->get($type);

                    if (! $handler) {
                        throw new \RuntimeException("Unknown action type: {$type}");
                    }

                    $actionResults[$type] = $handler->execute($config, $subject, $scope, $rule, $resolver);
                }

                $result = EvaluationResult::success(['actions' => $actionResults]);
                $this->logExecution($rule, $subject, $fingerprint, $result, $batchId);

                return $result;
            });
        } catch (Throwable $e) {
            $result = EvaluationResult::failed($e->getMessage(), [
                'exception' => $e::class,
                'trace' => $e->getTraceAsString(),
            ]);
            $this->logExecution($rule, $subject, $fingerprint, $result, $batchId);

            return $result;
        }
    }

    protected function logExecution(
        TriggerRule $rule,
        Model $subject,
        string $fingerprint,
        EvaluationResult $result,
        ?int $batchId,
    ): void {
        RuleExecution::query()->updateOrCreate(
            [
                'trigger_rule_id' => $rule->getKey(),
                'subject_type' => $subject->getMorphClass(),
                'subject_id' => $subject->getKey(),
                'fingerprint' => $fingerprint,
            ],
            [
                'batch_id' => $batchId,
                'status' => $result->status,
                'payload' => $result->payload,
                'error' => $result->error,
                'executed_at' => now(),
            ],
        );
    }

    /** @return Collection<int, TriggerRule> */
    protected function activeRulesForScope(?Model $scope): Collection
    {
        $query = TriggerRule::query()->where('is_active', true)->orderBy('priority');

        if ($scope) {
            $query->where('scope_type', $scope->getMorphClass())
                ->where('scope_id', $scope->getKey());
        }

        return $query->get();
    }

    protected function frequencyApplies(TriggerRule $rule, string $source, bool $dryRun): bool
    {
        if ($dryRun || in_array($source, ['manual', 'login', 'dry_run'], true)) {
            if ($rule->frequency === RuleFrequency::OnMaintain && $source === 'manual' && ! config('rules-engine.run_on_maintain_in_manual', true)) {
                return false;
            }

            return true;
        }

        // scheduled / family:maintain
        return true;
    }

    protected function periodSegment(TriggerRule $rule): string
    {
        $frequency = $rule->frequency instanceof RuleFrequency
            ? $rule->frequency
            : RuleFrequency::tryFrom((string) $rule->frequency);

        return match ($frequency) {
            RuleFrequency::Daily => 'day:'.now()->toDateString(),
            RuleFrequency::Weekly => 'week:'.now()->isoWeekYear().'-'.now()->isoWeek(),
            default => 'once',
        };
    }

    /** @return Collection<int, Model> */
    protected function resolveScopes(?Model $scope): Collection
    {
        if ($scope) {
            return collect([$scope]);
        }

        if ($this->scopeResolver) {
            return $this->scopeResolver->resolveAll();
        }

        return collect([null]);
    }

    protected function subjectResolver(): SubjectResolverInterface
    {
        if ($this->subjectResolver) {
            return $this->subjectResolver;
        }

        $class = config('rules-engine.subject_resolver');

        if ($class && class_exists($class)) {
            return app($class);
        }

        throw new \RuntimeException('rules-engine.subject_resolver is not configured.');
    }
}
