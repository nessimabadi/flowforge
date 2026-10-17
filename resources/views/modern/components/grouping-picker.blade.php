@props(['groupings' => null])

@php
    $groupings ??= array_map(
        fn ($grouping): string => (string) $grouping->getLabel(),
        $this->getBoard()->getGroupings(),
    );
    $selectId = 'flowforge-group-by-' . $this->getId();
@endphp

@if (count($groupings) > 0)
    <div {{ $attributes->class(['flex items-center gap-2']) }}>
        <label for="{{ $selectId }}" class="ff-muted text-sm">{{ __('flowforge::flowforge.group_by') }}</label>

        <x-filament::input.wrapper class="w-48">
            <x-filament::input.select
                :id="$selectId"
                x-on:change="$wire.$set('groupBy', $event.target.value || null)"
            >
                <option value="" @selected(blank($this->groupBy))>{{ __('flowforge::flowforge.no_grouping') }}</option>

                @foreach ($groupings as $name => $label)
                    <option value="{{ $name }}" @selected($this->groupBy === (string) $name)>{{ $label }}</option>
                @endforeach
            </x-filament::input.select>
        </x-filament::input.wrapper>
    </div>
@endif
