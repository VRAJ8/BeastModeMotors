<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Symfony\Component\Mime\Email;

/**
 * A buyer's daily email: price cuts on cars they saved, and new cars matching their saved searches. It carries
 * plain values rather than models, so a listing withdrawn before the email goes out can't break it.
 */
class BuyerAlerts extends Notification implements ShouldQueueAfterCommit
{
    use Queueable;

    /** Cars shown per search; the rest are a click away. */
    public const SHOWN = 5;

    /**
     * @param  list<array{title: string, url: string, was: int, now: int, details: string}>  $drops
     * @param  list<array{label: string, url: string, total: int, cars: list<array{title: string, url: string, details: string}>}>  $searches
     * @param  int  $cars  New cars across all searches, each counted once.
     */
    public function __construct(public array $drops, public array $searches, public int $cars, public string $unsubscribeUrl) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    private function headline(): string
    {
        $drops = count($this->drops);
        $dropped = $drops === 1 ? 'A car you saved dropped its price' : "{$drops} cars you saved dropped their price";
        $new = $this->cars.' new '.str('car')->plural($this->cars).' '.($this->cars === 1 ? 'matches' : 'match').' your saved '.str('search')->plural(count($this->searches));

        return match (true) {
            $drops > 0 && $this->cars > 0 => $dropped.', and '.lcfirst($new),
            $drops > 0 => $dropped,
            default => $new,
        };
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)->subject($this->headline())->line(md($this->headline()).'.');

        if ($this->drops) {
            $mail->line('**Price drops**');

            foreach ($this->drops as $car) {
                $mail->line('• ['.md($car['title']).']('.$car['url'].') — now '.md(money($car['now'])).', was '.md(money($car['was'])).' · '.md($car['details']));
            }
        }

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
            ->line('[Stop these emails]('.$this->unsubscribeUrl.') · [Manage alerts]('.route('saved').')')
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
        $parts = [...array_column($this->drops, 'title'), ...array_column($this->searches, 'label')];

        return [
            'title' => $this->headline(),
            'body' => implode(' / ', $parts),
            'url' => match (true) {
                count($this->drops) === 1 && $this->searches === [] => $this->drops[0]['url'],
                $this->drops === [] && count($this->searches) === 1 => $this->searches[0]['url'],
                default => route('saved'),
            },
            'tone' => $this->drops ? 'success' : 'info',
        ];
    }
}
