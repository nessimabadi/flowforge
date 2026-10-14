@props(['columnId', 'column', 'isCollapsed' => false])

@php
    use Relaticle\Flowforge\Support\ColorResolver;
    use function Filament\Support\get_color_css_variables;

    $actions = $this->getBoardColumnActions($columnId);
    $dotColor = ColorResolver::resolve($column['color'] ?? null);
    $isOpen = 'dragOverColumn === '.json_encode($columnId);
@endphp

<div
    @class([
        'flowforge-column-header flex items-center gap-2 px-1 pb-2',
        'justify-between' => ! $isCollapsed,
        'flex-col' => $isCollapsed,
    ])
    @if($isCollapsed)
        x-bind:class="{ 'justify-between': {{ $isOpen }}, 'flex-col': ! ({{ $isOpen }}) }"
    @endif
>
    <div
        @class([
            'flex min-w-0 items-center gap-2' => ! $isCollapsed,
            'contents' => $isCollapsed,
        ])
        @if($isCollapsed)
            x-bind:class="{ 'flex min-w-0 items-center gap-2': {{ $isOpen }}, 'contents': ! ({{ $isOpen }}) }"
        @endif
    >
        @if ($dotColor)
            <span
                class="ff-column-dot size-2 shrink-0 rounded-full"
                style="{{ get_color_css_variables($dotColor, shades: [500]) }}; background-color: var(--color-500);"
            ></span>
        @endif

        @if ($column['icon'] ?? null)
            <x-filament::icon :icon="$column['icon']" class="size-4 shrink-0 ff-muted" />
        @endif

        <h3
            @class([
                'text-sm font-medium text-gray-950 dark:text-white',
                'truncate' => ! $isCollapsed,
                'max-h-64 overflow-hidden text-ellipsis whitespace-nowrap [writing-mode:vertical-rl]' => $isCollapsed,
            ])
            @if($isCollapsed)
                x-bind:class="{ 'truncate': {{ $isOpen }}, 'max-h-64 [writing-mode:vertical-rl]': ! ({{ $isOpen }}) }"
            @endif
        >
            {{ $column['label'] }}
        </h3>

        <x-flowforge::column-count :column="$column" :columnId="$columnId" />
    </div>

    @if(count($actions) > 0)
        <div class="shrink-0">
            @if(count($actions) === 1)
                {{ $actions[0] }}
            @else
                <x-filament-actions::group :actions="$actions" />
            @endif
        </div>
    @endif
</div>
