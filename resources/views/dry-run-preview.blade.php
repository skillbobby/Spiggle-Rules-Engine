<div class="space-y-3 text-sm">
    @if (($result['matched'] ?? 0) === 0)
        <p class="text-gray-500 dark:text-gray-400">No matching subjects.</p>
    @else
        <p class="font-medium">{{ $result['matched'] }} match(es)</p>
        <ul class="list-disc space-y-2 ps-5">
            @foreach ($result['dry_run'] ?? [] as $row)
                <li>
                    <span class="font-medium">{{ $row['subject'] ?? ('#'.$row['subject_id']) }}</span>
                    <span class="text-gray-500"> — {{ $row['rule'] ?? 'rule' }}</span>
                    @if (! empty($row['actions']))
                        <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Actions: {{ collect($row['actions'])->pluck('type')->filter()->implode(', ') ?: 'configured' }}
                        </div>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif
</div>
