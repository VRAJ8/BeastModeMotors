<?php

namespace App\Notifications;

use App\Models\Vehicle;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PriceDropped extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Vehicle $vehicle) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $vehicle = $this->vehicle;

        return (new MailMessage)
            ->subject("Price drop: {$vehicle->title}")
            ->greeting("Good news, {$notifiable->name}!")
            ->line("A car in your garage just got cheaper. The {$vehicle->title} is now ".money($vehicle->price).' (was '.money($vehicle->previous_price).').')
            ->action('View the car', route('vehicles.show', $vehicle))
            ->line('Cars like this rarely stay on the floor for long.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'vehicle_id' => $this->vehicle->id,
            'title' => $this->vehicle->title,
            'price' => $this->vehicle->price,
            'previous_price' => $this->vehicle->previous_price,
            'url' => route('vehicles.show', $this->vehicle),
            'message' => "Price drop on the {$this->vehicle->title}: now ".money($this->vehicle->price),
        ];
    }
}
