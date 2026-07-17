<?php

namespace App\Enums;

enum ReadingPlanStatus: int
{
    case Completed = 1;
    case Unread = 2;

    public function label(): string
    {
        return match($this) {
            self::Completed => '読了',
            self::Unread => '未読',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Unread => 'bg-yellow-100 text-yellow-800',
            self::Completed => 'bg-green-100 text-green-800',
        };
    }
}