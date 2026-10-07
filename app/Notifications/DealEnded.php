<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * A conversation ended because the deal itself is gone (the seller deleted their account and the car with it),
 * so this carries plain text rather than the deal, which a queued job could no longer load.
 */
class DealEnded extends Notification implements ShouldQueueAfterCommit
{
    use Queueable;

    public function __construct(public string $title, public string $body) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->title)
            ->line(md($this->title).'.')
            ->line(md($this->body))
            ->action('Browse the marketplace', route('marketplace'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return ['title' => $this->title, 'body' => $this->body, 'url' => route('marketplace'), 'tone' => 'danger'];
    }
}
