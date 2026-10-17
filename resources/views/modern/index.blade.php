@php use Filament\Support\Facades\FilamentAsset; @endphp
@props(['columns', 'config', 'lanes' => [], 'hasMoreLanes' => false, 'groupings' => []])

<div style="display: contents">
<span hidden x-load-css="[@js(FilamentAsset::getStyleHref('flowforge-modern', package: 'relaticle/flowforge'))]"></span>

<div
    class="ff-board w-full h-full flex flex-col relative"
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
        <x-flowforge::modern.grouping-picker :groupings="$groupings" class="pb-3" />
    @endunless

    <!-- Board Content -->
    <div class="flex-1 overflow-hidden h-full">
        @if ($config['grouping'] !== null)
            <x-flowforge::modern.lanes :columns="$columns" :lanes="$lanes" :config="$config" :hasMoreLanes="$hasMoreLanes" />
        @else
            <div class="flex flex-row h-full overflow-x-auto overflow-y-hidden gap-x-5">
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
