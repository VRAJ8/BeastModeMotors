<?php

namespace App\Notifications;

use App\Models\Deal;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Something happened in a deal room: a message, an offer, an agreement, a completed sale…
 */
class DealUpdate extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Deal $deal,
        public string $title,
        public ?string $body = null,
        public bool $email = true,
        public string $tone = 'info',
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return $this->email ? ['mail', 'database'] : ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->title)
            ->line(md($this->title).'.')
            ->when($this->body, fn (MailMessage $mail) => $mail->line(md($this->body)))
            ->action('Open the deal room', route('deals.show', $this->deal))
            ->line('Keep payments and paperwork inside the deal room so you both have a record.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
            'url' => route('deals.show', $this->deal),
            'tone' => $this->tone,
            'deal_id' => $this->deal->getKey(),
        ];
    }
}
