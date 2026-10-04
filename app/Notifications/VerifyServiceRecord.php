<?php

namespace App\Notifications;

use App\Models\ShopVerification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to a repair shop's email address. The shop answers through a signed link — no account needed.
 */
class VerifyServiceRecord extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public ShopVerification $verification) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $record = $this->verification->record;
        $vehicle = $record->vehicle;

        return (new MailMessage)
            ->subject("Can you confirm work on a {$vehicle->title()}?")
            ->greeting("Hello {$this->verification->shop_name},")
            ->line("{$this->verification->requester?->name} has logged work your shop did on their {$vehicle->title()} and asked you to confirm it.")
            ->line("**{$record->title}** · ".$record->performed_on->format('M j, Y').' · '.number_format($record->mileage).' miles'.($record->cost_cents ? ' · '.money($record->cost_cents) : ''))
            ->action('Review the record', $this->verification->signedUrl())
            ->line('It takes one click to confirm, or you can tell us if something doesn\'t match your records. No account is needed.')
            ->line('Confirmed records help your customer sell their car for a fair price — and show buyers your shop looks after cars properly.')
            ->salutation('Thanks, '.config('passport.name'));
    }
}
