<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use Filament\Panel;
use Livewire\Livewire;
use Relaticle\Flowforge\Board;
use Relaticle\Flowforge\Tests\Fixtures\Modern\TestBoard;
use Relaticle\Flowforge\Tests\Fixtures\Modern\TestBoardResourcePage;
use Relaticle\Flowforge\Tests\Fixtures\Modern\TestResource;
use Relaticle\Flowforge\Tests\Fixtures\Modern\TestStandaloneBoard;

describe('card labels', function () {
    test('names the cards after the resource of the board page', function () {
        $board = Livewire::test(TestBoardResourcePage::class)->instance()->getBoard();

        expect($board->getCardLabel())->toBe(TestResource::getModelLabel())
            ->and($board->getPluralCardLabel())->toBe(TestResource::getPluralModelLabel());
    });

    test('names the cards after the resource registered for the model', function (): void {
        $resource = new class extends TestResource
        {
            protected static ?string $modelLabel = 'Support ticket';

            protected static ?string $pluralModelLabel = 'Support tickets';
        };

        Filament::setCurrentPanel(Panel::make()->id('labels')->resources([$resource::class]));

        $component = Livewire::test(TestStandaloneBoard::class)
            ->assertSeeHtml('aria-label="To Do, 0 Support tickets"');

        $board = $component->instance()->getBoard();

        expect($board->getCardLabel())->toBe('Support ticket')
            ->and($board->getPluralCardLabel())->toBe('Support tickets');
    });

    test('explicit labels win over the model', function () {
        $board = Livewire::test(TestBoard::class)->instance()->getBoard()
            ->cardLabel('Ticket')
            ->pluralCardLabel('Tickets');

        expect($board->getCardLabel())->toBe('Ticket')
            ->and($board->getPluralCardLabel())->toBe('Tickets');
    });

    test('falls back to the translated label without a query', function () {
        $board = Livewire::test(TestBoard::class)->instance()->getBoard()->query(fn () => null);

        expect($board->getCardLabel())->toBe(__('flowforge::flowforge.card_label'))
            ->and($board->getPluralCardLabel())->toBe(__('flowforge::flowforge.plural_card_label'));
    });

    test('the column tray names the cards', function () {
        Livewire::test(TestBoard::class)
            ->assertStatus(200)
            ->assertSeeHtml('aria-label="To Do, 0 Tasks"');
    });

    test('renders custom labels escaped in the column tray', function (string $label): void {
        Board::configureUsing(
            fn (Board $board): Board => $board->cardLabel($label)->pluralCardLabel($label),
            during: function () use ($label): void {
                Livewire::test(TestBoard::class)
                    ->assertSeeHtml('aria-label="To Do, 0 ' . e($label) . '"')
                    ->assertDontSeeHtml('<New>');
            },
        );
    })->with([
        'apostrophe' => "Manager's task",
        'HTML and quotes' => '<New> "customer" & contacts',
        'backslash and newline' => "Sales\\Support\nTasks",
        'Unicode' => '顧客',
    ]);

    test('evaluates translated label closures for the current locale', function (): void {
        Board::configureUsing(
            fn (Board $board): Board => $board
                ->cardLabel(fn (): string => __('flowforge::flowforge.card_label'))
                ->pluralCardLabel(fn (): string => __('flowforge::flowforge.plural_card_label')),
            during: function (): void {
                app()->setLocale('de');

                Livewire::test(TestBoard::class)->assertSeeHtml('aria-label="To Do, 0 Datensätze"');

                app()->setLocale('es');

                Livewire::test(TestBoard::class)->assertSeeHtml('aria-label="To Do, 0 Registros"');
            },
        );
    });
});
