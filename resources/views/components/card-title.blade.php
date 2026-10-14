@php
    use function Filament\Support\generate_href_html;

    $record = $getRecord();
    $board = $getLivewire()->getBoard();
    $link = $board->getCardLink($board->getBoardRecordActions(['model' => $record]));
    $title = data_get($record, $board->getRecordTitleAttribute());
@endphp

<h4 {{ $attributes->merge($getExtraAttributes(), escape: false)->class(['pr-6 text-sm font-medium text-gray-950 dark:text-white']) }}>
    @if ($link['url'])
        <a {{ generate_href_html($link['url'], $link['openInNewTab'], hasNestedClickEventHandler: true) }} class="block">{{ $title }}</a>
    @else
        {{ $title }}
    @endif
</h4>
