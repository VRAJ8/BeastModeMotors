<?php

namespace App\Notifications;

use App\Models\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Alerts the sales team about a new enquiry, offer or trade-in.
 */
class NewLeadReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Lead $lead) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $lead = $this->lead->loadMissing('vehicle.brand');

        return (new MailMessage)
            ->subject("New {$lead->type->getLabel()} lead from {$lead->name}")
            ->line("**{$lead->name}** <{$lead->email}> ".($lead->phone ? "· {$lead->phone}" : ''))
            ->when($lead->vehicle, fn (MailMessage $m) => $m->line('Vehicle: '.$lead->vehicle->title))
            ->when($lead->offer_amount, fn (MailMessage $m) => $m->line('Offer: '.money($lead->offer_amount)))
            ->when($lead->message, fn (MailMessage $m) => $m->line('> '.$lead->message))
            ->action('Open in back-office', url('/admin/leads/'.$lead->id.'/edit'));
    }
}
