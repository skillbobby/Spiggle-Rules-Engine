<?php

namespace Spiggle\RulesEngine\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

interface ScopeResolverInterface
{
    /**
     * @return Collection<int, Model>
     */
    public function resolveAll(): Collection;
}
