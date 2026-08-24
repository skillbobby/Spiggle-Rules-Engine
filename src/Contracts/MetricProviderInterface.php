<?php

namespace Spiggle\RulesEngine\Contracts;

use Illuminate\Database\Eloquent\Model;

interface MetricProviderInterface
{
    public function value(Model $subject, ?Model $scope, string $metric): ?float;
}
