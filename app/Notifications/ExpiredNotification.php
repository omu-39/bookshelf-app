<?php

namespace App\Notifications;

use App\Models\ReadingPlan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ExpiredNotification extends Notification
{
    use Queueable;

    private $plan;

    /**
     * Create a new notification instance.
     */
    public function __construct(ReadingPlan $plan)
    {
        $this->plan = $plan;
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
            'timing' => 'three_days_after',
            'title'  => '読書計画の期限を過ぎました',
            'body'   => "『{$this->plan->book->title}』の期限が過ぎました。",
        ];
    }
}
