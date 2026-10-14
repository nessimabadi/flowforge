@props(['columnId', 'column', 'config'])

@php
    $total = $column['total'] ?? (isset($column['items']) ? count($column['items']) : 0);
    $isCollapsed = ($config['collapseEmptyColumns'] ?? false) && $total === 0;
    $isOpen = 'dragOverColumn === '.json_encode((string) $columnId);
    $plainLabel = strip_tags((string) $column['label']);
    $trayLabel = __('flowforge::flowforge.column_label', [
        'column' => $plainLabel,
        'count' => $total,
        'cards' => trans_choice('flowforge::flowforge.cards_count', $total, [
            'card' => $config['cardLabel'],
            'cards' => $config['pluralCardLabel'],
        ]),
    ]);
@endphp

<div
    @class([
        'flowforge-column flex max-h-full flex-shrink-0 flex-col',
        'w-[300px] min-w-[300px]' => ! $isCollapsed,
        'flowforge-column-collapsed w-12 min-w-12' => $isCollapsed,
    ])
    data-column-root="{{ $columnId }}"
    data-column-total="{{ $column['limitTotal'] ?? $total }}"
    @if (($column['limit'] ?? null) !== null) data-column-limit="{{ $column['limit'] }}" @endif
    @if($isCollapsed) title="{{ $plainLabel }}" @endif
    x-bind:class="{
        @if($isCollapsed)
            'w-12 min-w-12': ! ({{ $isOpen }}),
            'w-[300px] min-w-[300px]': {{ $isOpen }},
        @endif
        'ff-column--active': ({{ $isOpen }}) && ! isColumnBlocked(@js((string) $columnId)),
        'ff-column--blocked': ({{ $isOpen }}) && isColumnBlocked(@js((string) $columnId)),
    }"
>
    <x-flowforge::column-header :columnId="(string) $columnId" :column="$column" :isCollapsed="$isCollapsed" />

    <div
        class="flowforge-column-tray ff-tray flex min-h-0 flex-1 flex-col"
        role="region"
        aria-label="{{ $trayLabel }}"
    >
        @if (($column['limit'] ?? null) !== null)
            <p
                x-cloak
                x-show="({{ $isOpen }}) && isColumnBlocked(@js((string) $columnId))"
                class="ff-limit-hint px-3 pt-2 text-xs font-medium"
            >{{ __('flowforge::flowforge.limit_reached') }}</p>
        @endif

        <div
            data-column-id="{{ $columnId }}"
            @if($this->getBoard()->getPositionIdentifierAttribute())
                x-sortable
                x-sortable-group="cards"
                @end.stop="handleSortableEnd($event)"
                x-init="$nextTick(() => configureSortable($el))"
            @endif
            @if(isset($column['total']) && $column['total'] > count($column['items']))
                @scroll.throttle.100ms="handleColumnScroll($event, '{{ $columnId }}')"
            @endif
            @class([
                'flowforge-column-content kanban-cards min-h-16 flex-1 overflow-y-auto overflow-x-hidden overscroll-y-contain',
                'p-2' => ! $isCollapsed,
                'p-0' => $isCollapsed,
            ])
            @if($isCollapsed) x-bind:class="{ 'p-2': {{ $isOpen }}, 'p-0': ! ({{ $isOpen }}) }" @endif
            style="max-height: calc(100vh - 13rem);"
        >
            @foreach ($column['items'] ?? [] as $record)
                <x-flowforge::card
                    :record="$record"
                    :config="$config"
                    :columnId="$columnId"
                />
            @endforeach

            @if(isset($column['total']) && $column['total'] > count($column['items'] ?? []))
                <div class="py-3 text-center" x-intersect.margin.300px="handleSmoothScroll('{{ $columnId }}')">
                    <div
                        x-show="isLoadingColumn('{{ $columnId }}')"
                        x-transition
                        class="flex items-center justify-center gap-2 text-xs text-primary-600 dark:text-primary-400"
                    >
                        {{ __('flowforge::flowforge.loading_more_cards') }}
                    </div>
                </div>
            @endif
        </div>

        @unless($isCollapsed)
            <div class="px-2 pb-2">
                <x-flowforge::add-card-row :columnId="$columnId" :cardLabel="$config['cardLabel']" :disabled="$column['isFull'] ?? false" />
            </div>
        @endunless
    </div>
</div>
