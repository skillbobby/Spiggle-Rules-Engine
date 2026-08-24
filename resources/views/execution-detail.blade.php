<div class="space-y-4 text-sm">
    <div>
        <div class="font-medium">Status</div>
        <div>{{ $record->status?->getLabel() ?? $record->status }}</div>
    </div>
    @if ($record->error)
        <div>
            <div class="font-medium">Error</div>
            <pre class="overflow-x-auto whitespace-pre-wrap rounded-lg bg-gray-100 p-3 text-xs dark:bg-gray-800">{{ $record->error }}</pre>
        </div>
    @endif
    @if (! empty($record->payload['trace']))
        <div>
            <div class="font-medium">Stack trace</div>
            <pre class="max-h-80 overflow-auto whitespace-pre-wrap rounded-lg bg-gray-100 p-3 text-xs dark:bg-gray-800">{{ $record->payload['trace'] }}</pre>
        </div>
    @endif
    @if (! empty($record->payload))
        <div>
            <div class="font-medium">Payload</div>
            <pre class="max-h-64 overflow-auto whitespace-pre-wrap rounded-lg bg-gray-100 p-3 text-xs dark:bg-gray-800">{{ json_encode($record->payload, JSON_PRETTY_PRINT) }}</pre>
        </div>
    @endif
</div>
