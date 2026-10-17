<div class="ff-card-avatars" title="{{ $names }}">
    @foreach ($visible as $person)
        @if ($person['url'])
            <img
                src="{{ $person['url'] }}"
                alt="{{ $person['name'] }}"
                class="ff-card-avatar"
            />
        @else
            <span
                class="ff-card-avatar"
                aria-label="{{ $person['name'] }}"
            >{{ $person['initials'] }}</span>
        @endif
    @endforeach

    @if ($overflow > 0)
        <span class="ff-card-avatars-more">+{{ $overflow }}</span>
    @endif
</div>
