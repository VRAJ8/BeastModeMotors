<?php

namespace App\Notifications;

use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to support when someone says the car behind an existing passport is theirs.
 */
class OwnershipReviewRequested extends Notification implements ShouldQueueAfterCommit
{
    use Queueable;

    public function __construct(public Vehicle $vehicle, public User $claimant) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Ownership review: {$this->vehicle->vin}")
            ->replyTo($this->claimant->email, $this->claimant->name)
            ->line(md($this->claimant->name).' ('.md($this->claimant->email).') says they own the car with VIN '.md($this->vehicle->vin).', which already has a passport on another account.')
            ->line('Passport created '.$this->vehicle->created_at->toFormattedDateString().' · '.$this->vehicle->ownerships()->count().' owner(s) on file · '.$this->vehicle->records()->count().' records.')
            ->line('Ask the claimant for their title or registration before changing anything. Reply to this email to reach them.')
            ->action('Open the admin', url('/admin'));
    }
}
