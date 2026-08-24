<?php

namespace Spiggle\RulesEngine\Data;

use Spiggle\RulesEngine\Enums\ExecutionStatus;

class EvaluationResult
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public readonly ExecutionStatus $status,
        public readonly array $payload = [],
        public readonly ?string $error = null,
    ) {}

    public static function success(array $payload = []): self
    {
        return new self(ExecutionStatus::Success, $payload);
    }

    public static function failed(string $error, array $payload = []): self
    {
        return new self(ExecutionStatus::Failed, $payload, $error);
    }

    public static function skipped(string $reason = 'Already executed'): self
    {
        return new self(ExecutionStatus::Skipped, ['reason' => $reason]);
    }

    public static function dryRun(array $payload = []): self
    {
        return new self(ExecutionStatus::DryRun, $payload);
    }
}
