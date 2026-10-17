@php
    $board = $getLivewire()->getBoard();
    $title = data_get($getRecord(), $board->getRecordTitleAttribute());
@endphp

<h4 {{ $attributes->merge($getExtraAttributes(), escape: false)->class(['ff-card-title']) }}>
    {{ $title }}
</h4>
