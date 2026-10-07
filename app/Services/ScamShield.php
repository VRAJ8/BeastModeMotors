<?php

namespace App\Services;

/**
 * Flags the patterns behind most private-sale car scams so the other person sees a warning.
 *
 * Rule-based on purpose: every flag can be explained to the user in one sentence.
 */
class ScamShield
{
    public const HIGH = 'high';

    public const MEDIUM = 'medium';

    public const LOW = 'low';

    /**
     * @return list<array{key: string, severity: string, label: string, advice: string}>
     */
    private function rules(): array
    {
        return [
            [
                'key' => 'untraceable_payment',
                'severity' => self::HIGH,
                'pattern' => '/\b(gift ?cards?|itunes|steam card|western union|moneygram|bitcoin|crypto|usdt|btc|wire (the )?(money|funds|payment))\b/i',
                'label' => 'Asks for an untraceable payment method',
                'advice' => 'Gift cards, crypto and money transfers can\'t be reversed. Pay by bank transfer or cashier\'s check at the buyer\'s bank, in person.',
            ],
            [
                'key' => 'fake_escrow',
                'severity' => self::HIGH,
                'pattern' => '/\b((escrow|shipping|transport) (agent|company|service)|ebay (motors )?(protection|guarantee)|vehicle purchase protection)\b/i',
                'label' => 'Mentions a third-party escrow or shipping agent',
                'advice' => 'Scammers invent "escrow" and "shipping" companies to collect payment. Beast Mode Motors never holds money for you.',
            ],
            [
                'key' => 'remote_seller',
                'severity' => self::HIGH,
                'pattern' => '/\b(deployed|stationed overseas|out of (the )?country|i\'?m overseas|relocated abroad|military base)\b/i',
                'label' => 'Says they can\'t meet in person',
                'advice' => 'Someone who is "deployed" or "abroad" and wants to deal through an agent is the most common car-sale scam script. Only deal in person, and never pay for a car you haven\'t seen.',
            ],
            [
                'key' => 'verification_code',
                'severity' => self::HIGH,
                'pattern' => '/\b((verification|security|confirmation) code|6[- ]digit code|google voice)\b/i',
                'label' => 'Asks for a verification code',
                'advice' => 'Never share a code sent to your phone — it lets the other person take over your accounts or number.',
            ],
            [
                'key' => 'overpayment',
                'severity' => self::HIGH,
                'pattern' => '/\b(send (me |us )?(back )?the (difference|rest|extra)|over ?pay|refund the (difference|extra))\b/i',
                'label' => 'Overpayment and refund request',
                'advice' => 'Checks that overpay and ask you to send back the difference bounce days later. Accept only the agreed amount.',
            ],
            [
                'key' => 'deposit_unseen',
                'severity' => self::MEDIUM,
                'pattern' => '/\b(deposit|hold (it|the car))\b.{0,60}\b(before|without)\b.{0,20}\b(see|seeing|view|viewing|inspect|inspection)\b|\bsend (a |the )?deposit\b/i',
                'label' => 'Deposit before viewing',
                'advice' => 'Don\'t send a deposit until you\'ve seen the car and checked its VIN against the title.',
            ],
            [
                'key' => 'pressure',
                'severity' => self::LOW,
                'pattern' => '/\b(other buyers? (are|is) (interested|waiting)|first come,? first served|today only|act fast|urgent(ly)?)\b/i',
                'label' => 'Pressure to decide quickly',
                'advice' => 'Urgency is a pressure tactic. Take the time you need for an inspection.',
            ],
            [
                'key' => 'contact_details',
                'severity' => self::LOW,
                'pattern' => '/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}|\(?\b\d{3}\)?[\s.-]?\d{3}[\s.-]?\d{4}\b/i',
                'label' => 'Shares contact details',
                'advice' => 'Keeping the conversation here keeps a record and the scam warnings. Swap numbers once you\'ve agreed to meet.',
            ],
        ];
    }

    /**
     * @return list<array{key: string, severity: string, label: string, advice: string}>
     */
    public function scan(string $text): array
    {
        return collect($this->rules())
            ->filter(fn (array $rule) => preg_match($rule['pattern'], $text) === 1)
            ->map(fn (array $rule) => collect($rule)->except('pattern')->all())
            ->values()
            ->all();
    }

    /**
     * @param  list<array{severity: string}>|null  $flags
     */
    public static function highestSeverity(?array $flags): ?string
    {
        $severities = array_column($flags ?? [], 'severity');

        foreach ([self::HIGH, self::MEDIUM, self::LOW] as $severity) {
            if (in_array($severity, $severities, true)) {
                return $severity;
            }
        }

        return null;
    }
}
