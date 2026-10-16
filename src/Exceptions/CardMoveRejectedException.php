<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Exceptions;

final class CardMoveRejectedException extends FlowforgeException
{
    public static function because(string $message): self
    {
        return new self($message);
    }

    public static function unknownColumn(): self
    {
        return new self(__('flowforge::flowforge.unknown_column'));
    }

    public static function unknownCard(): self
    {
        return new self(__('flowforge::flowforge.unknown_card'));
    }

    public static function unknownLane(): self
    {
        return new self(__('flowforge::flowforge.unknown_lane'));
    }

    public static function lanesReadOnly(string $grouping): self
    {
        return new self(__('flowforge::flowforge.lanes_read_only', ['grouping' => $grouping]));
    }

    public static function columnFull(string $column, int $limit): self
    {
        return new self(__('flowforge::flowforge.column_full', ['column' => $column, 'limit' => $limit]));
    }

    public static function boardBusy(string $column): self
    {
        return new self(__('flowforge::flowforge.board_busy', ['column' => $column]));
    }
}
