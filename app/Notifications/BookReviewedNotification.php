<?php

namespace App\Notifications;

use App\Models\Book;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class BookReviewedNotification extends Notification
{
    use Queueable;

    private $book;

    /**
     * Create a new notification instance.
     */
    public function __construct(Book $book)
    {
        $this->book = $book;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'title' => "あなたの書籍にレビューが投稿されました",
            'body' => "『{$this->book->title}』に新しいレビューがあります。",
        ];
    }
}
