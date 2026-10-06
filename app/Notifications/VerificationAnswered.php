<?php

namespace App\Notifications;

use App\Enums\VerificationStatus;
use App\Models\ShopVerification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VerificationAnswered extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public ShopVerification $verification) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    private function confirmed(): bool
    {
        return $this->verification->status === VerificationStatus::Confirmed;
    }

    private function headline(): string
    {
        return $this->confirmed()
            ? "{$this->verification->shop_name} confirmed “{$this->verification->record->title}”"
            : "{$this->verification->shop_name} disputed “{$this->verification->record->title}”";
    }

    private function url(): string
    {
        return route('vehicles.history', $this->verification->record->vehicle_id);
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->headline())
            ->line(md($this->headline()).'.')
            ->when($this->verification->response_note, fn (MailMessage $mail) => $mail->line('Their note: “'.md($this->verification->response_note).'”'))
            ->line($this->confirmed()
                ? 'The record now carries a Shop Verified stamp on your passport.'
                : 'The record is marked as disputed. Check the details and correct or remove it.')
            ->action('Open history', $this->url());
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->headline(),
            'body' => $this->verification->response_note,
            'url' => $this->url(),
            'tone' => $this->confirmed() ? 'success' : 'danger',
        ];
    }
}
