<?php

namespace App\Notifications;

use App\Models\Recall;
use App\Models\Vehicle;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class RecallsFound extends Notification implements ShouldQueueAfterCommit
{
    use Queueable;

    /**
     * @param  Collection<int, Recall>  $recalls
     */
    public function __construct(public Vehicle $vehicle, public Collection $recalls) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    private function headline(): string
    {
        $count = $this->recalls->count();

        return "{$count} new safety ".str('recall')->plural($count)." for your {$this->vehicle->displayName()}";
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)->subject($this->headline())->line(md($this->headline()).':');

        foreach ($this->recalls as $recall) {
            $mail->line('• **'.md($recall->component).'** (NHTSA '.md($recall->campaign_number).')');
        }

        return $mail
            ->line('Recall repairs are free at any franchise dealer for your make.')
            ->action('View recalls', route('vehicles.recalls', $this->vehicle));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->headline(),
            'body' => $this->recalls->pluck('component')->implode(' · '),
            'url' => route('vehicles.recalls', $this->vehicle),
            'tone' => 'danger',
        ];
    }
}
