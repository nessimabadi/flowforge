@props(['columnId', 'cardLabel', 'disabled' => false])

@php
    $createAction = $this->getBoardCreateCardAction($columnId);
@endphp

@if ($createAction?->isVisible())
    @php
        $isDisabled = $disabled || $createAction->isDisabled();
    @endphp
    <button
        type="button"
        class="ff-add-row flex w-full items-center gap-1.5 rounded-lg px-3 py-2 text-sm disabled:cursor-not-allowed disabled:opacity-60"
        wire:click="mountAction(@js($createAction->getName()), @js(['column' => $columnId]))"
        @disabled($isDisabled)
        @if ($disabled) title="{{ __('flowforge::flowforge.limit_reached') }}" @endif
    >
        <x-filament::icon icon="heroicon-m-plus" class="size-4 shrink-0" />
        {{ __('flowforge::flowforge.add_card', ['card' => $cardLabel]) }}
    </button>
@endif
