@props(['columns', 'lanes', 'config', 'hasMoreLanes'])

@php
    use Filament\Support\Enums\IconSize;
    use Relaticle\Flowforge\Support\ColorResolver;
    use function Filament\Support\get_color_css_variables;

    $isSortable = $this->getBoard()->getPositionIdentifierAttribute() !== null;
@endphp

<div class="ff-lanes">
    <div class="ff-lanes-grid" style="--_columns: {{ count($columns) }}">
        <div class="ff-lanes-heads" wire:key="column-headers">
            @foreach ($columns as $columnId => $column)
                <div
                    class="ff-column"
                    data-column-root="{{ $columnId }}"
                    data-column-total="{{ $column['limitTotal'] ?? $column['total'] }}"
                    @if (($column['limit'] ?? null) !== null) data-column-limit="{{ $column['limit'] }}" @endif
                    @if ($column['isFull'] ?? false) data-full @endif
                    x-bind:data-drop="columnDrop(@js((string) $columnId))"
                    wire:key="lane-header-{{ $columnId }}"
                >
                    <x-flowforge::modern.column-header :columnId="(string) $columnId" :column="$column" />
                </div>
            @endforeach
        </div>

        @foreach ($lanes as $lane)
            @php $laneColor = ColorResolver::resolve($lane['color']); @endphp

            <div class="ff-lane-header" wire:key="lane-{{ $lane['id'] }}">
                @if ($lane['avatar'])
                    <img src="{{ $lane['avatar'] }}" alt="" class="ff-lane-avatar" />
                @elseif ($lane['icon'])
                    <x-filament::icon :icon="$lane['icon']" :size="IconSize::Small" class="ff-lane-icon" />
                @elseif ($laneColor)
                    <span class="ff-lane-dot" style="{{ get_color_css_variables($laneColor, shades: [500]) }}"></span>
                @endif

                <h4 class="ff-lane-title">{{ $lane['label'] }}</h4>
                <span class="ff-lane-count">{{ $lane['total'] }}</span>
            </div>

            @foreach ($columns as $columnId => $column)
                @php $cell = $lane['cells'][$columnId] ?? ['items' => [], 'total' => 0]; @endphp

                <div
                    class="ff-cell"
                    x-bind:data-drop="cellDrop(@js((string) $columnId), @js((string) $lane['id']))"
                    wire:key="cell-{{ $lane['id'] }}-{{ $columnId }}"
                >
                    <div
                        class="ff-cell-cards"
                        data-column-id="{{ $columnId }}"
                        data-lane-id="{{ $lane['id'] }}"
                        @if ($isSortable)
                            x-sortable
                            x-sortable-group="cards"
                            @end.stop="handleSortableEnd($event)"
                            x-init="$nextTick(() => configureSortable($el))"
                        @endif
                    >
                        @foreach ($cell['items'] as $record)
                            <x-flowforge::modern.card :record="$record" :columnId="$columnId" :laneId="$lane['id']" />
                        @endforeach
                    </div>

                    @if ($cell['total'] > count($cell['items']))
                        <button
                            type="button"
                            class="ff-add-row"
                            wire:click="loadMoreItems(@js((string) $columnId), null, @js($lane['id']))"
                            wire:key="more-cards-{{ $lane['id'] }}-{{ $columnId }}"
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
                class="ff-add-row ff-lanes-more"
                wire:click="loadMoreLanes"
                wire:key="more-lanes"
            >
                {{ __('flowforge::flowforge.show_more_lanes') }}
            </button>
        @endif
    </div>
</div>
