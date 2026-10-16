<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Components;

use Filament\Infolists\Components\TextEntry;
use Illuminate\Support\Collection;
use Relaticle\Flowforge\Support\ColorResolver;

use function Filament\Support\get_color_css_variables;

class CardTag extends TextEntry
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->hiddenLabel();
    }

    public function isHidden(): bool
    {
        return parent::isHidden() || $this->getTagStates() === [];
    }

    public function toEmbeddedHtml(): string
    {
        $tags = array_map(fn (mixed $state): array => [
            'label' => $this->formatState($state),
            'colorStyle' => get_color_css_variables(
                ColorResolver::resolve($this->getColor($state)) ?? 'gray',
                shades: [500],
            ),
        ], $this->getTagStates());

        if ($tags === []) {
            return '';
        }

        /** @var view-string $view */
        $view = 'flowforge::components.card-tag';

        return view($view, ['tags' => $tags])->render();
    }

    /**
     * @return array<int, mixed>
     */
    protected function getTagStates(): array
    {
        $state = $this->getState();

        $items = match (true) {
            $state instanceof Collection => $state->all(),
            is_array($state) => $state,
            default => [$state],
        };

        return array_values(array_filter($items, fn (mixed $item): bool => filled($item)));
    }
}
