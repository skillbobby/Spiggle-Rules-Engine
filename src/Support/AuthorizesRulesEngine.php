<?php

namespace Spiggle\RulesEngine\Support;

use Illuminate\Database\Eloquent\Model;
use Spiggle\RulesEngine\Models\TriggerRule;

class AuthorizesRulesEngine
{
    public static function canManage(?Model $scope = null): bool
    {
        $callback = config('rules-engine.can_manage_rules');

        if (! is_callable($callback)) {
            return true;
        }

        $user = auth()->user();

        if (! $user) {
            return false;
        }

        return (bool) $callback($user, $scope ?? filament()->getTenant());
    }

    public static function scopeModel(): ?string
    {
        return config('rules-engine.scope_model');
    }

    public static function tenantScopeQuery(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        $tenant = filament()->getTenant();

        if (! $tenant) {
            return $query->whereRaw('0 = 1');
        }

        return $query
            ->where('scope_type', $tenant->getMorphClass())
            ->where('scope_id', $tenant->getKey());
    }

    public static function assignScope(array $data): array
    {
        $tenant = filament()->getTenant();

        if ($tenant) {
            $data['scope_type'] = $tenant->getMorphClass();
            $data['scope_id'] = $tenant->getKey();
        }

        $data['user_id'] ??= auth()->id();

        return $data;
    }
}
