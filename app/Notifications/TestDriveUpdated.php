<?php

namespace App\Notifications;

use App\Enums\TestDriveStatus;
use App\Models\TestDrive;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to the customer when a test drive is booked or its status changes.
 */
class TestDriveUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public TestDrive $testDrive) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return $notifiable instanceof \App\Models\User ? ['mail', 'database'] : ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $drive = $this->testDrive->loadMissing('vehicle.brand');
        $when = $drive->scheduled_at->format('l, F j \a\t g:i A');

        $mail = (new MailMessage)
            ->subject($this->headline().' — '.$drive->reference)
            ->greeting("Hi {$drive->name},");

        $mail = match ($drive->status) {
            TestDriveStatus::Pending => $mail
                ->line("We've received your test drive request for the {$drive->vehicle->title} on {$when}.")
                ->line('A product specialist will confirm your slot shortly.'),
            TestDriveStatus::Confirmed => $mail
                ->line("You're confirmed! Your {$drive->vehicle->title} will be fuelled and waiting on {$when}.")
                ->line('Please bring a valid driving licence.'),
            TestDriveStatus::Completed => $mail
                ->line("Thanks for driving the {$drive->vehicle->title} with us. We'd love to hear what you thought."),
            TestDriveStatus::Cancelled => $mail
                ->line("Your test drive of the {$drive->vehicle->title} on {$when} has been cancelled.")
                ->line('You can book another slot any time.'),
        };

        return $mail
            ->action('View the car', route('vehicles.show', $drive->vehicle))
            ->line('Reference: '.$drive->reference);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'test_drive_id' => $this->testDrive->id,
            'status' => $this->testDrive->status->value,
            'url' => route('garage'),
            'message' => $this->headline().': '.$this->testDrive->vehicle->title.' on '.$this->testDrive->scheduled_at->format('M j, g:i A'),
        ];
    }

    protected function headline(): string
    {
        return match ($this->testDrive->status) {
            TestDriveStatus::Pending => 'Test drive requested',
            TestDriveStatus::Confirmed => 'Test drive confirmed',
            TestDriveStatus::Completed => 'Thanks for visiting',
            TestDriveStatus::Cancelled => 'Test drive cancelled',
        };
    }
}
