<?php

declare(strict_types=1);

use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Livewire\Livewire;
use Relaticle\Flowforge\Tests\Fixtures\Modern\TestBoard;
use Relaticle\Flowforge\Tests\Fixtures\Modern\TestContractBoard;

describe('header toolbar', function () {
    test('renders the page header actions without the header toolbar', function () {
        Livewire::test(TestBoard::class)
            ->assertStatus(200)
            ->assertSee('Export board');
    });

    test('renders the page header actions with the header toolbar', function () {
        Livewire::test(TestBoard::class, ['withHeaderToolbar' => true])
            ->assertStatus(200)
            ->assertSee('Export board')
            ->assertSeeHtml('fi-ta-search-field');
    });

    test('renders the page header render hooks with the header toolbar', function (string $hook) {
        FilamentView::registerRenderHook($hook, fn (): string => "hook-marker:{$hook}", scopes: TestBoard::class);

        Livewire::test(TestBoard::class, ['withHeaderToolbar' => true])
            ->assertStatus(200)
            ->assertSee("hook-marker:{$hook}");
    })->with([
        PanelsRenderHook::PAGE_HEADER_HEADING_BEFORE,
        PanelsRenderHook::PAGE_HEADER_HEADING_AFTER,
        PanelsRenderHook::PAGE_HEADER_ACTIONS_BEFORE,
        PanelsRenderHook::PAGE_HEADER_ACTIONS_AFTER,
    ]);

    test('shows the filter count badge only while a filter is active', function () {
        $component = Livewire::test(TestContractBoard::class, ['withHeaderToolbar' => true, 'filtersLayout' => 'Dropdown'])
            ->assertDontSeeHtml('fi-icon-btn-badge-ctn');

        $component->set('tableFilters.priority.value', 'low')
            ->assertSeeHtml('fi-icon-btn-badge-ctn');
    });
});
