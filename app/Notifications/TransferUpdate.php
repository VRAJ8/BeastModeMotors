<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells an owner what happened to a transfer link they sent. It carries plain text, because after a transfer
 * the car is no longer theirs to link to.
 */
class TransferUpdate extends Notification implements ShouldQueueAfterCommit
{
    use Queueable;

    public function __construct(public string $title, public string $body, public string $tone = 'info') {}

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
            ->action('Open your garage', route('garage'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return ['title' => $this->title, 'body' => $this->body, 'url' => route('garage'), 'tone' => $this->tone];
    }
}
