@php
    use Filament\Actions\Action;
    use Filament\Actions\ActionGroup;
    use Filament\Schemas\Components\Component;

    $gap = $getGap();
    $wrap = $shouldWrap();
    $justify = $getJustify();
    $align = $getAlign();
@endphp

<div
    {{
        $attributes
            ->merge($getExtraAttributes(), escape: false)
            ->class([
                'ff-card-flex',
                "ff-card-flex--gap-{$gap}",
                'ff-card-flex--nowrap' => ! $wrap,
                "ff-card-flex--justify-{$justify}",
                "ff-card-flex--align-{$align}",
            ])
    }}
>
    @foreach ($getChildSchema()->getComponents() as $component)
        @if (($component instanceof Action) || ($component instanceof ActionGroup))
            <div class="ff-card-flex-item--fixed">
                {{ $component }}
            </div>
        @else
            @php
                $hiddenJs = $component->getHiddenJs();
                $visibleJs = $component->getVisibleJs();
                $componentStatePath = $component->getStatePath();
            @endphp

            <div
                x-data="filamentSchemaComponent({
                    path: @js($componentStatePath),
                    containerPath: @js($statePath),
                    isLive: @js($schemaComponent->isLive()),
                    $wire,
                })"
                @if ($afterStateUpdatedJs = $schemaComponent->getAfterStateUpdatedJs())
                    x-init="{!! implode(';', array_map(
                        fn (string $js): string => '$wire.watch(' . Js::from($componentStatePath) . ', ($state, $old) => ($state !== undefined) && eval(' . Js::from($js) . '))',
                        $afterStateUpdatedJs,
                    )) !!}"
                @endif
                @if (filled($visibilityJs = match ([filled($hiddenJs), filled($visibleJs)]) {
                     [true, true] => "(! ({$hiddenJs})) && ({$visibleJs})",
                     [true, false] => "! ({$hiddenJs})",
                     [false, true] => $visibleJs,
                     default => null,
                 }))
                    x-bind:class="{ 'fi-hidden': ! ({!! $visibilityJs !!}) }"
                    x-cloak
                @endif
                @class([
                    'ff-card-flex-item--fixed' => ! ($component instanceof Component && $component->canGrow()),
                    'ff-card-flex-item--grow' => ($component instanceof Component) && $component->canGrow(),
                ])
            >
                {{ $component }}
            </div>
        @endif
    @endforeach
</div>
