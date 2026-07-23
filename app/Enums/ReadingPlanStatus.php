<?php

namespace App\Enums;

enum ReadingPlanStatus: int
{
    case Completed = 1;
    case Progress = 2;
    case Expired = 3;

    public function label(): string
    {
        return match($this) {
            self::Completed => '読了',
            self::Progress => '進行中',
            self::Expired => '期限切れ',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Completed => 'bg-green-200',
            self::Progress => 'bg-yellow-200',
            self::Expired => 'bg-red-200',
        };
    }
}