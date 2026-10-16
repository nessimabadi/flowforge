@props(['columnId', 'record', 'laneId' => null])

@php
    use Relaticle\Flowforge\Components\CardTitle;
    use function Filament\Support\generate_href_html;

    $board = $this->getBoard();
    $recordActions = $board->getBoardRecordActions($record);
    $link = $board->getCardLink($recordActions);
    $href = $link['url'] ? generate_href_html($link['url'], $link['openInNewTab'], hasNestedClickEventHandler: true) : null;
    $mountAction = $link['action'];
    $isDraggable = $board->getPositionIdentifierAttribute() !== null;
    $hasCardTitle = $record['schema'] !== null
        && collect($record['schema']->getFlatComponents())->contains(fn (mixed $component): bool => $component instanceof CardTitle);
@endphp

<div
    @class([
        'flowforge-card ff-card relative mb-2',
        'cursor-grab active:cursor-grabbing' => $isDraggable,
        'cursor-pointer' => ! $isDraggable && ($href || $mountAction),
    ])
    wire:key="card-{{ $laneId ?? 'board' }}-{{ $record['id'] }}"
    x-sortable-item="{{ $record['id'] }}"
    data-card-id="{{ $record['id'] }}"
    @if($isDraggable) x-sortable-handle @endif
    data-position="{{ $record['position'] ?? '' }}"
>
    @if (count($recordActions) > 0)
        <div class="ff-card-actions absolute right-2 top-2">
            <x-filament-actions::group :actions="$recordActions" />
        </div>
    @endif

    <div
        @class(['flowforge-card-content flex flex-col gap-2 p-3', 'cursor-pointer' => $mountAction])
        @if($mountAction) wire:click="mountAction('{{ $mountAction }}', [], @js(['recordKey' => $record['id']]))" @endif
    >
        @unless ($hasCardTitle)
            <h4 class="pr-6 text-sm font-medium text-gray-950 dark:text-white">
                @if ($href)
                    <a {{ $href }} class="block">{{ $record['title'] }}</a>
                @else
                    {{ $record['title'] }}
                @endif
            </h4>
        @endunless

        @if (filled($record['schema']))
            @if ($href)
                <a {{ $href }} class="block">{{ $record['schema'] }}</a>
            @else
                {{ $record['schema'] }}
            @endif
        @endif
    </div>
</div>
