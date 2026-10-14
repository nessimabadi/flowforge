<span class="ff-card-tags inline-flex flex-wrap items-center gap-x-3 gap-y-1">
    @foreach ($tags as $tag)
        <span class="ff-card-tag ff-muted inline-flex items-center gap-1.5 text-xs">
            <span class="size-1.5 shrink-0 rounded-full" style="{{ $tag['colorStyle'] }}; background-color: var(--color-500);"></span>
            {{ $tag['label'] }}
        </span>
    @endforeach
</span>
