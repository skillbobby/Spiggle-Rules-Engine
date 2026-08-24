<div class="space-y-6">
    <x-filament::section>
        <x-slot name="heading">Schedule</x-slot>

        <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div>
                <dt class="text-sm text-gray-500 dark:text-gray-400">Active rules (this family)</dt>
                <dd class="text-lg font-semibold">{{ $activeRuleCount }}</dd>
            </div>
            <div>
                <dt class="text-sm text-gray-500 dark:text-gray-400">Scheduler</dt>
                <dd class="text-lg font-semibold">{{ $scheduleDescription }}</dd>
            </div>
            <div>
                <dt class="text-sm text-gray-500 dark:text-gray-400">Next expected run</dt>
                <dd class="text-lg font-semibold">{{ $nextScheduledAt ?? '—' }}</dd>
            </div>
        </dl>
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">Last run</x-slot>

        @if ($lastRunAt)
            <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                <div>
                    <dt class="text-sm text-gray-500 dark:text-gray-400">Started</dt>
                    <dd class="text-lg font-semibold">{{ $lastRunAt }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500 dark:text-gray-400">Source</dt>
                    <dd class="text-lg font-semibold">{{ $lastSource ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500 dark:text-gray-400">Matched</dt>
                    <dd class="text-lg font-semibold">{{ $lastMatched ?? 0 }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500 dark:text-gray-400">Executed</dt>
                    <dd class="text-lg font-semibold">{{ $lastExecuted ?? 0 }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500 dark:text-gray-400">Failed / Skipped</dt>
                    <dd class="text-lg font-semibold">{{ ($lastFailed ?? 0) }} / {{ ($lastSkipped ?? 0) }}</dd>
                </div>
            </dl>
        @else
            <p class="text-sm text-gray-500 dark:text-gray-400">No runs yet. Use <strong>Run now</strong> or wait for the scheduled maintenance command.</p>
        @endif
    </x-filament::section>
</div>
