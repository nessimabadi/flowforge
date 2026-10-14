@props(['column', 'columnId'])

@if (($column['limit'] ?? null) !== null)
    <span @class(['ff-count shrink-0 text-sm tabular-nums', 'ff-count--full' => $column['isFull'], 'ff-muted' => ! $column['isFull']])>{{ $column['limitTotal'] }}/{{ $column['limit'] }}</span>
@else
    <span class="ff-count ff-muted shrink-0 text-sm tabular-nums">{{ $column['total'] ?? 0 }}</span>
@endif
