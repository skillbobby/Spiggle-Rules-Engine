<?php

namespace Spiggle\RulesEngine\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class SubjectLocker
{
    public function withLock(Model $subject, callable $callback): mixed
    {
        return DB::transaction(function () use ($subject, $callback) {
            $locked = $subject->newQuery()->whereKey($subject->getKey())->lockForUpdate()->first();

            return $callback($locked ?? $subject);
        });
    }
}
