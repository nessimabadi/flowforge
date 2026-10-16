@php
    $board = $getLivewire()->getBoard();
    $title = data_get($getRecord(), $board->getRecordTitleAttribute());
@endphp

<h4 {{ $attributes->merge($getExtraAttributes(), escape: false)->class(['pr-6 text-sm font-medium text-gray-950 dark:text-white']) }}>
    {{ $title }}
</h4>
