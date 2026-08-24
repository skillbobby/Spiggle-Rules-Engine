<?php

namespace Spiggle\RulesEngine\Services;

use Illuminate\Database\Eloquent\Model;
use Spiggle\RulesEngine\Enums\ExecutionStatus;
use Spiggle\RulesEngine\Models\RuleExecution;

class IdempotencyGuard
{
    public function shouldSkip(int $ruleId, Model $subject, string $fingerprint, bool $allowRetry = false): bool
    {
        $existing = RuleExecution::query()
            ->where('trigger_rule_id', $ruleId)
            ->where('subject_type', $subject->getMorphClass())
            ->where('subject_id', $subject->getKey())
            ->where('fingerprint', $fingerprint)
            ->first();

        if (! $existing) {
            return false;
        }

        if ($allowRetry && $existing->status === ExecutionStatus::Failed) {
            return false;
        }

        return in_array($existing->status, [ExecutionStatus::Success, ExecutionStatus::Skipped], true);
    }

    public function fingerprint(int $ruleId, Model $subject, string $segment): string
    {
        return hash('sha256', implode('|', [
            $ruleId,
            $subject->getMorphClass(),
            (string) $subject->getKey(),
            $segment,
        ]));
    }
}
