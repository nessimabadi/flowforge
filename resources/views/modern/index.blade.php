@php
    use Filament\Pages\Page;
    use Filament\Support\Facades\FilamentAsset;
@endphp
@props(['columns', 'config', 'lanes' => [], 'hasMoreLanes' => false, 'groupings' => []])

<div style="display: contents">
@unless ($this instanceof Page)
    @include('flowforge::modern.stylesheet')
@endunless

<div
    class="ff-board"
    data-lanes-writable="{{ $config['lanesWritable'] ? 'true' : 'false' }}"
    x-load
    x-load-src="{{ FilamentAsset::getAlpineComponentSrc('flowforge-modern', package: 'relaticle/flowforge') }}"
    x-data="flowforgeModern({
        state: {
            columns: @js($columns),
            titleField: '{{ $config['recordTitleAttribute'] }}',
            columnField: '{{ $config['columnIdentifierAttribute'] }}',
            cardLabel: @js($config['cardLabel']),
            pluralCardLabel: @js($config['pluralCardLabel']),
        }
    })"
>

    @unless($config['headerToolbar'] ?? false)
        @include('flowforge::modern.components.filters')
    @endunless

    @unless ($config['headerToolbar'] ?? false)
        <x-flowforge::modern.grouping-picker :groupings="$groupings" />
    @endunless

    <div class="ff-board-body">
        @if ($config['grouping'] !== null)
            <x-flowforge::modern.lanes :columns="$columns" :lanes="$lanes" :config="$config" :hasMoreLanes="$hasMoreLanes" />
        @else
            <div class="ff-columns">
                @foreach($columns as $columnId => $column)
                    <x-flowforge::modern.column
                        :columnId="$columnId"
                        :column="$column"
                        :config="$config"
                        wire:key="column-{{ $columnId }}"
                    />
                @endforeach
            </div>
        @endif
    </div>

    <x-filament-actions::modals/>
</div>
</div>
