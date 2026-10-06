<?php

namespace App\Notifications;

use App\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DocumentExpiring extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Document $document) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    private function headline(): string
    {
        $verb = $this->document->expires_on->isPast() ? 'expired' : 'expires';

        return "{$this->document->type->getLabel()} for your {$this->document->vehicle->displayName()} {$verb} ".$this->document->expires_on->format('M j');
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->headline())
            ->line(md($this->headline()).' (“'.md($this->document->name).'”).')
            ->line('Renew it and upload the new copy so everything stays in one place.')
            ->action('View documents', route('vehicles.documents', $this->document->vehicle_id));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->headline(),
            'body' => $this->document->name,
            'url' => route('vehicles.documents', $this->document->vehicle_id),
            'tone' => 'warning',
        ];
    }
}
