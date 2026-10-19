@props(['column', 'columnId'])

@php
    $limit = $column['limit'] ?? null;
    $total = $limit !== null ? $column['limitTotal'] : ($column['total'] ?? 0);
    $suffix = $limit !== null ? "/{$limit}" : '';
@endphp

{{-- Alpine keeps the first x-text expression it saw, so a morph cannot update a total baked into one. --}}
<span
    class="ff-count"
    data-total="{{ $total }}"
><span x-show="! dragSource && ! pendingMoves">{{ $total }}{{ $suffix }}</span><span
    x-cloak
    x-show="dragSource || pendingMoves"
    x-text="displayCount(@js((string) $columnId), Number($el.parentElement.dataset.total)) + @js($suffix)"
></span></span>
