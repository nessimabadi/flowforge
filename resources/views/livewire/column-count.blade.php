@props(['column', 'columnId'])

@php
    $limit = $column['limit'] ?? null;
    $total = $limit !== null ? $column['limitTotal'] : ($column['total'] ?? 0);
    $suffix = $limit !== null ? "/{$limit}" : '';
    $isFull = $limit !== null && $column['isFull'];
@endphp

{{-- Alpine keeps the first x-text expression it saw, so a morph cannot update a total baked into one. --}}
<span
    @class(['ff-count shrink-0 text-sm tabular-nums', 'ff-count--full' => $isFull, 'ff-muted' => ! $isFull])
    data-total="{{ $total }}"
><span x-show="! dragSource">{{ $total }}{{ $suffix }}</span><span
    x-cloak
    x-show="dragSource"
    x-text="displayCount(@js((string) $columnId), Number($el.parentElement.dataset.total)) + @js($suffix)"
></span></span>
