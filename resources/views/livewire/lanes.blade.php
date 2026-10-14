@props(['columns', 'lanes', 'config', 'hasMoreLanes'])

@php
    use Relaticle\Flowforge\Support\ColorResolver;
    use function Filament\Support\get_color_css_variables;

    $isSortable = $this->getBoard()->getPositionIdentifierAttribute() !== null;
@endphp

<div class="h-full overflow-auto">
    <div class="grid gap-x-5 gap-y-2 pb-4" style="grid-template-columns: repeat({{ count($columns) }}, 300px);">
        @foreach ($columns as $columnId => $column)
            <div
                class="sticky top-0 z-10 bg-white pt-1 dark:bg-gray-900"
                data-column-root="{{ $columnId }}"
                data-column-total="{{ $column['limitTotal'] ?? $column['total'] }}"
                @if (($column['limit'] ?? null) !== null) data-column-limit="{{ $column['limit'] }}" @endif
            >
                <x-flowforge::column-header :columnId="$columnId" :column="$column" />
            </div>
        @endforeach

        @foreach ($lanes as $lane)
            @php $laneColor = ColorResolver::resolve($lane['color']); @endphp

            <div class="flex items-center gap-2 pt-4" style="grid-column: 1 / -1;">
                @if ($lane['avatar'])
                    <img src="{{ $lane['avatar'] }}" alt="" class="size-6 rounded-full object-cover" />
                @elseif ($lane['icon'])
                    <x-filament::icon :icon="$lane['icon']" class="ff-muted size-4" />
                @elseif ($laneColor)
                    <span class="size-2 rounded-full" style="{{ get_color_css_variables($laneColor, shades: [500]) }}; background-color: var(--color-500);"></span>
                @endif

                <h4 class="text-sm font-medium text-gray-950 dark:text-white">{{ $lane['label'] }}</h4>
                <span class="ff-muted text-sm tabular-nums">{{ $lane['total'] }}</span>
            </div>

            @foreach ($columns as $columnId => $column)
                @php $cell = $lane['cells'][$columnId] ?? ['items' => [], 'total' => 0]; @endphp

                <div class="ff-tray flex min-h-24 flex-col p-2">
                    <div
                        class="flowforge-column-content min-h-16 flex-1"
                        data-column-id="{{ $columnId }}"
                        data-lane-id="{{ $lane['id'] }}"
                        @if ($isSortable)
                            x-sortable
                            x-sortable-group="cards"
                            @end.stop="dragOverColumn = null; handleSortableEnd($event)"
                        @endif
                    >
                        @foreach ($cell['items'] as $record)
                            <x-flowforge::card :record="$record" :columnId="$columnId" :laneId="$lane['id']" />
                        @endforeach
                    </div>

                    @if ($cell['total'] > count($cell['items']))
                        <button
                            type="button"
                            class="ff-add-row rounded-lg px-3 py-2 text-left text-sm"
                            wire:click="loadMoreItems(@js($columnId), null, @js($lane['id']))"
                        >
                            {{ __('flowforge::flowforge.show_more_cards', ['count' => $cell['total'] - count($cell['items'])]) }}
                        </button>
                    @endif
                </div>
            @endforeach
        @endforeach

        @if ($hasMoreLanes)
            <button
                type="button"
                class="ff-add-row rounded-lg px-3 py-2 text-left text-sm"
                style="grid-column: 1 / -1;"
                wire:click="loadMoreLanes"
            >
                {{ __('flowforge::flowforge.show_more_lanes') }}
            </button>
        @endif
    </div>
</div>
