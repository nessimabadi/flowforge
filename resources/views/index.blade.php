@php use Filament\Support\Facades\FilamentAsset; @endphp
@props(['columns', 'config', 'lanes' => [], 'hasMoreLanes' => false, 'groupings' => []])

<div
    class="ff-board w-full h-full flex flex-col relative"
    data-lanes-writable="{{ $config['lanesWritable'] ? 'true' : 'false' }}"
    x-load
    x-load-src="{{ FilamentAsset::getAlpineComponentSrc('flowforge', package: 'relaticle/flowforge') }}"
    x-data="flowforge({
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
        @include('flowforge::components.filters')
    @endunless

    <x-flowforge::grouping-picker :groupings="$groupings" />

    <!-- Board Content -->
    <div class="flex-1 overflow-hidden h-full">
        @if ($config['grouping'] !== null)
            <x-flowforge::lanes :columns="$columns" :lanes="$lanes" :config="$config" :hasMoreLanes="$hasMoreLanes" />
        @else
            <div class="flex flex-row h-full overflow-x-auto overflow-y-hidden gap-x-5">
                @foreach($columns as $columnId => $column)
                    <x-flowforge::column
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
