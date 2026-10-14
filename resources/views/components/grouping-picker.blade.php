@props(['groupings'])

@if (count($groupings) > 0)
    <div class="flex items-center gap-2 pb-3">
        <label for="flowforge-group-by" class="ff-muted text-sm">{{ __('flowforge::flowforge.group_by') }}</label>

        <x-filament::input.wrapper class="w-48">
            <x-filament::input.select id="flowforge-group-by" wire:model.live="groupBy">
                <option value="">{{ __('flowforge::flowforge.no_grouping') }}</option>

                @foreach ($groupings as $name => $label)
                    <option value="{{ $name }}">{{ $label }}</option>
                @endforeach
            </x-filament::input.select>
        </x-filament::input.wrapper>
    </div>
@endif
