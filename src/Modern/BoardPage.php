<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Modern;

use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Livewire\Attributes\Url;
use Relaticle\Flowforge\Contracts\HasBoard;
use Relaticle\Flowforge\Modern\Concerns\BaseBoard;
use Relaticle\Flowforge\Modern\Concerns\InteractsWithBoard;

/**
 * Board page for standard Filament pages.
 * Extends Filament's base Page class with kanban board functionality.
 *
 * Requires the `filament/filament` package (Panel Builder).
 * For standalone usage, use the InteractsWithBoard trait directly.
 *
 * @see InteractsWithBoard
 */
abstract class BoardPage extends Page implements HasActions, HasBoard, HasForms
{
    use BaseBoard;

    protected string $view = 'flowforge::modern.filament.pages.board-page';

    /**
     * @var array<string, mixed>|null
     */
    #[Url(as: 'filters')]
    public ?array $tableFilters = null;

    /**
     * @return array<string, array<string, mixed>>
     */
    protected function queryString(): array
    {
        return [
            'tableSearch' => ['as' => 'search', 'except' => ''],
        ];
    }
}
