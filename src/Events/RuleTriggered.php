<?php

namespace Spiggle\RulesEngine\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Spiggle\RulesEngine\Models\TriggerRule;

class RuleTriggered
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        public TriggerRule $rule,
        public Model $subject,
        public ?Model $scope,
        public array $config = [],
    ) {}
}
