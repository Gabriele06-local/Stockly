<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Draft = 'draft';
    case Confirmed = 'confirmed';
    case Paid = 'paid';
    case Shipped = 'shipped';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Confirmed => 'blue',
            self::Paid => 'indigo',
            self::Shipped => 'amber',
            self::Completed => 'green',
            self::Cancelled => 'red',
        };
    }

    /** Statuses that reserve / consume stock. */
    public static function stockAffecting(): array
    {
        return [self::Confirmed, self::Paid, self::Shipped, self::Completed];
    }
}
