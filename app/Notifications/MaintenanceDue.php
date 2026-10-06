<?php

namespace App\Notifications;

use App\Models\Reminder;
use App\Models\Vehicle;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class MaintenanceDue extends Notification implements ShouldQueueAfterCommit
{
    use Queueable;

    /**
     * @param  Collection<int, Reminder>  $reminders
     */
    public function __construct(public Vehicle $vehicle, public Collection $reminders) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    private function headline(): string
    {
        $count = $this->reminders->count();

        return "{$count} maintenance ".str('item')->plural($count)." due on your {$this->vehicle->displayName()}";
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)->subject($this->headline())->line(md($this->headline()).':');

        foreach ($this->reminders as $reminder) {
            $state = $reminder->status($this->vehicle->current_mileage) === Reminder::OVERDUE ? 'overdue' : 'due soon';
            $mail->line('• **'.md($reminder->task)."** — {$state} (".md($reminder->dueLabel($this->vehicle->current_mileage)).')');
        }

        return $mail
            ->line('When it\'s done, log it with the receipt — it keeps your Passport Score up.')
            ->action('View maintenance', route('vehicles.maintenance', $this->vehicle));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->headline(),
            'body' => $this->reminders->pluck('task')->implode(', '),
            'url' => route('vehicles.maintenance', $this->vehicle),
            'tone' => 'warning',
        ];
    }
}
