<?php

namespace App\Notifications;

use App\Enums\VerificationStatus;
use App\Models\ShopVerification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Throwable;

/**
 * Sent to a repair shop's email address. The shop answers through a signed link — no account needed.
 */
class VerifyServiceRecord extends Notification implements ShouldQueueAfterCommit
{
    use Queueable;

    public function __construct(public ShopVerification $verification) {}

    /**
     * The shop never got the email, so don't leave a request open that blocks the owner from asking again.
     */
    public function failed(Throwable $e): void
    {
        if ($this->verification->fresh()?->isAnswerable()) {
            $this->verification->update(['status' => VerificationStatus::Cancelled]);
        }
    }

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
            ->greeting('Hello '.md($this->verification->shop_name).',')
            ->line(md($this->verification->requester?->name).' has logged work your shop did on their '.md($vehicle->title()).' and asked you to confirm it.')
            ->line('**'.md($record->title).'** · '.$record->performed_on->format('M j, Y').' · '.number_format($record->mileage).' miles'.($record->cost_cents ? ' · '.money($record->cost_cents) : ''))
            ->action('Review the record', $this->verification->signedUrl())
            ->line('It takes one click to confirm, or you can tell us if something doesn\'t match your records. No account is needed.')
            ->line('Confirmed records help your customer sell their car for a fair price — and show buyers your shop looks after cars properly.')
            ->salutation('Thanks, '.config('passport.name'));
    }
}
