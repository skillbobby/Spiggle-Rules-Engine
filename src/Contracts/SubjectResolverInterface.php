<?php

namespace Spiggle\RulesEngine\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Spiggle\RulesEngine\Models\TriggerRule;

interface SubjectResolverInterface
{
    /**
     * @return Collection<int, Model>
     */
    public function resolve(?Model $scope, TriggerRule $rule, ?Model $subject = null): Collection;

    public function getAttribute(Model $subject, string $key): mixed;

    public function getPivotMeta(Model $scope, Model $subject): array;

    public function setPivotMeta(Model $scope, Model $subject, array $meta): void;

    public function getPivotCreatedAt(Model $scope, Model $subject): ?\DateTimeInterface;
}
