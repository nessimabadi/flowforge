@props(['columnId', 'cardLabel', 'disabled' => false])

@php
    $createAction = $this->getBoardCreateCardAction($columnId)?->disabled($disabled);
@endphp

@if ($createAction?->isVisible())
    @php
        $isDisabled = $createAction->isDisabled();
    @endphp
    <button
        type="button"
        class="ff-add-row"
        wire:click="mountAction(@js($createAction->getName()), @js(['column' => (string) $columnId]))"
        @disabled($isDisabled)
        @if ($disabled) title="{{ __('flowforge::flowforge.limit_reached') }}" @endif
    >
        <x-filament::icon icon="heroicon-m-plus" class="ff-add-row-icon" />
        {{ __('flowforge::flowforge.add_card', ['card' => $cardLabel]) }}
    </button>
@endif
