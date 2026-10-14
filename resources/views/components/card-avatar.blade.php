<div class="ff-card-avatars flex items-center -space-x-1.5" title="{{ $names }}">
    @foreach ($visible as $person)
        @if ($person['url'])
            <img
                src="{{ $person['url'] }}"
                alt="{{ $person['name'] }}"
                class="ff-card-avatar size-6 rounded-full object-cover ring-2 ring-white dark:ring-gray-900"
            />
        @else
            <span
                class="ff-card-avatar flex size-6 items-center justify-center rounded-full bg-gray-200 text-[10px] font-medium text-gray-700 ring-2 ring-white dark:bg-gray-700 dark:text-gray-200 dark:ring-gray-900"
                aria-label="{{ $person['name'] }}"
            >{{ $person['initials'] }}</span>
        @endif
    @endforeach

    @if ($overflow > 0)
        <span class="ff-muted pl-2.5 text-xs">+{{ $overflow }}</span>
    @endif
</div>
