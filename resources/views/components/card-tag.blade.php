<span class="ff-card-tags">
    @foreach ($tags as $tag)
        <span class="ff-card-tag">
            <span class="ff-card-tag-dot" style="{{ $tag['colorStyle'] }}"></span>
            {{ $tag['label'] }}
        </span>
    @endforeach
</span>
