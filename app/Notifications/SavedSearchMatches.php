<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Symfony\Component\Mime\Email;

/**
 * The daily email of new cars matching a buyer's saved searches. It carries plain values rather than models,
 * so a listing withdrawn before the email goes out can't break it.
 */
class SavedSearchMatches extends Notification implements ShouldQueueAfterCommit
{
    use Queueable;

    /** Cars shown per search; the rest are a click away. */
    public const SHOWN = 5;

    /**
     * @param  list<array{label: string, url: string, total: int, cars: list<array{title: string, url: string, details: string}>}>  $searches
     */
    public function __construct(public array $searches, public int $cars, public string $unsubscribeUrl) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    private function headline(): string
    {
        return $this->cars.' new '.str('car')->plural($this->cars).' '.($this->cars === 1 ? 'matches' : 'match').' your saved '.str('search')->plural(count($this->searches));
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)->subject($this->headline())->line(md($this->headline()).'.');

        foreach ($this->searches as $search) {
            $mail->line('**'.md($search['label']).'**');

            foreach ($search['cars'] as $car) {
                $mail->line('• ['.md($car['title']).']('.$car['url'].') — '.md($car['details']));
            }

            if ($search['total'] > count($search['cars'])) {
                $more = $search['total'] - count($search['cars']);
                $mail->line("[See {$more} more]({$search['url']})");
            }
        }

        // One-click unsubscribe for mail clients (RFC 8058), as well as the link in the footer.
        return $mail
            ->action('Open the marketplace', route('marketplace'))
            ->line('[Stop these emails]('.$this->unsubscribeUrl.') · [Manage saved searches]('.route('saved').')')
            ->withSymfonyMessage(function (Email $message) {
                $message->getHeaders()->addTextHeader('List-Unsubscribe', '<'.$this->unsubscribeUrl.'>');
                $message->getHeaders()->addTextHeader('List-Unsubscribe-Post', 'List-Unsubscribe=One-Click');
            });
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->headline(),
            'body' => collect($this->searches)->pluck('label')->implode(' / '),
            'url' => count($this->searches) === 1 ? $this->searches[0]['url'] : route('saved'),
            'tone' => 'info',
        ];
    }
}
