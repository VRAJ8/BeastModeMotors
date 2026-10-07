<?php

namespace App\Services;

use Anthropic\Client;
use Anthropic\Core\Exceptions\APIException;
use App\Enums\ProviderType;
use App\Enums\ServiceCategory;
use App\Models\Vehicle;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use JsonException;

/**
 * Reads a photo or PDF of a repair invoice with Claude, so logging work starts from the receipt instead of
 * a blank form. It only ever pre-fills: the owner checks every field and saves the record themselves.
 */
class ReceiptReader
{
    /** Image types the API accepts. HEIC (iPhone default) isn't one of them. */
    public const IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

    /** The API's limit for a single image. */
    public const MAX_IMAGE_BYTES = 5 * 1024 * 1024;

    public const MAX_PDF_BYTES = 30 * 1024 * 1024;

    private const SYSTEM = <<<'PROMPT'
        You read car repair and maintenance receipts and invoices so an owner can log the work in their car's service history.
        Report only what the document shows. When a field isn't on it, use an empty string, or 0 for numbers, rather than a guess: the owner fills those in.
        - performed_on: the date the work was done (the invoice or service date), as YYYY-MM-DD.
        - mileage: the odometer reading recorded on the invoice, in miles. If it's in kilometres, convert to miles.
        - provider_type: "dealer" for a franchise dealership, "independent" for any other shop, "diy" for a parts-store receipt with no labour.
        - title: what was done, in a few words, the way an owner would put it, e.g. "Front brake pads & rotors".
        - line_items: one per line on the invoice. kind is "part", "labor" or "fee"; put taxes and shop supplies in as fees, so the lines add up to the total.
        - tasks: which of the car's maintenance items this work completed, copied exactly from the list you're given. Leave it empty if none clearly match.
        - vin: the VIN if the invoice prints one.
        PROMPT;

    public function __construct(private Client $client) {}

    public static function enabled(): bool
    {
        return (bool) config('passport.receipts.scanner');
    }

    /**
     * Whether a file is something the reader can send: a supported image or a PDF, within the API's limits.
     */
    public static function canRead(?string $mime, int $bytes): bool
    {
        return match (true) {
            in_array($mime, self::IMAGE_TYPES, true) => $bytes <= self::MAX_IMAGE_BYTES,
            $mime === 'application/pdf' => $bytes <= self::MAX_PDF_BYTES,
            default => false,
        };
    }

    /**
     * @return array{performed_on: ?string, mileage: ?int, provider_type: string, shop_name: string, shop_email: ?string, title: string, category: string, line_items: list<array{description: string, kind: string, amount: float}>, total: ?float, tasks: list<string>, vin: ?string, vin_mismatch: bool}|null
     *                                                                                                                                                                                                                                                                                                           Null when the receipt couldn't be read.
     */
    public function read(string $contents, string $mime, Vehicle $vehicle): ?array
    {
        if (! self::canRead($mime, strlen($contents))) {
            return null;
        }

        $file = $mime === 'application/pdf'
            ? ['type' => 'document', 'source' => ['type' => 'base64', 'mediaType' => 'application/pdf', 'data' => base64_encode($contents)]]
            : ['type' => 'image', 'source' => ['type' => 'base64', 'mediaType' => $mime, 'data' => base64_encode($contents)]];

        $tasks = $vehicle->reminders()->pluck('task')->all();

        try {
            $message = $this->client->beta->messages->create(
                model: config('passport.receipts.model'),
                maxTokens: 16000,
                system: self::SYSTEM,
                messages: [[
                    'role' => 'user',
                    'content' => [
                        $file,
                        ['type' => 'text', 'text' => "The car is a {$vehicle->title()}.\nIts maintenance items: ".($tasks ? implode('; ', $tasks) : '(none)').'.'],
                    ],
                ]],
                outputConfig: [
                    'effort' => config('passport.receipts.effort'),
                    'format' => ['type' => 'json_schema', 'schema' => self::schema()],
                ],
                // If the model declines for policy reasons, let the API retry on its default fallback model.
                betas: ['server-side-fallback-2026-07-01'],
                fallbacks: 'default',
            );
        } catch (APIException $e) {
            Log::warning('Receipt scan failed', ['vehicle_id' => $vehicle->getKey(), 'error' => $e->getMessage()]);

            return null;
        }

        if (in_array($message->stopReason, ['refusal', 'max_tokens'], true)) {
            Log::warning('Receipt scan stopped early', ['vehicle_id' => $vehicle->getKey(), 'stop_reason' => $message->stopReason]);

            return null;
        }

        foreach ($message->content as $block) {
            if ($block->type === 'text') {
                try {
                    return self::normalize(json_decode($block->text, true, 16, JSON_THROW_ON_ERROR), $vehicle, $tasks);
                } catch (JsonException) {
                    return null;
                }
            }
        }

        return null;
    }

    /**
     * Turn the model's answer into values the record form accepts: anything implausible is dropped, not trusted.
     *
     * @param  array<string, mixed>  $raw
     * @param  list<string>  $tasks
     */
    public static function normalize(array $raw, Vehicle $vehicle, array $tasks): array
    {
        $text = fn (string $key, int $max) => Str::limit(trim((string) ($raw[$key] ?? '')), $max, '');
        $money = fn ($value) => is_numeric($value) && $value >= 0 && $value <= 1_000_000 ? round((float) $value, 2) : null;

        $date = null;
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', (string) ($raw['performed_on'] ?? ''), $m) && checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
            $parsed = Carbon::create((int) $m[1], (int) $m[2], (int) $m[3]);
            $date = $parsed->lte(today()) && $parsed->year >= $vehicle->year - 1 ? $parsed->toDateString() : null;
        }

        $mileage = is_numeric($raw['mileage'] ?? null) ? (int) $raw['mileage'] : 0;
        $email = filter_var(trim((string) ($raw['shop_email'] ?? '')), FILTER_VALIDATE_EMAIL) ?: null;
        $vin = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) ($raw['vin'] ?? '')));
        $byName = collect($tasks)->keyBy(fn (string $task) => Str::lower($task));

        $items = collect($raw['line_items'] ?? [])
            ->filter(fn ($item) => is_array($item) && trim((string) ($item['description'] ?? '')) !== '' && $money($item['amount'] ?? null) !== null)
            ->take(30)
            ->map(fn (array $item) => [
                'description' => Str::limit(trim($item['description']), 120, ''),
                'kind' => in_array($item['kind'] ?? null, ['part', 'labor', 'fee'], true) ? $item['kind'] : 'part',
                'amount' => $money($item['amount']),
            ])
            ->values()
            ->all();

        return [
            'performed_on' => $date,
            'mileage' => $mileage > 0 && $mileage <= 2_000_000 ? $mileage : null,
            'provider_type' => ProviderType::tryFrom((string) ($raw['provider_type'] ?? ''))?->value ?? ProviderType::Independent->value,
            'shop_name' => $text('shop_name', 120),
            'shop_email' => $email,
            'title' => $text('title', 120),
            'category' => ServiceCategory::tryFrom((string) ($raw['category'] ?? ''))?->value ?? ServiceCategory::Maintenance->value,
            'line_items' => $items,
            'total' => ($total = $money($raw['total'] ?? null)) ? $total : null,
            'tasks' => collect($raw['tasks'] ?? [])->map(fn ($task) => $byName->get(Str::lower(trim((string) $task))))->filter()->unique()->values()->all(),
            'vin' => strlen($vin) === 17 ? $vin : null,
            'vin_mismatch' => strlen($vin) === 17 && $vin !== $vehicle->vin,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function schema(): array
    {
        $string = ['type' => 'string'];

        return [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => ['performed_on', 'mileage', 'provider_type', 'shop_name', 'shop_email', 'title', 'category', 'line_items', 'total', 'tasks', 'vin'],
            'properties' => [
                'performed_on' => $string,
                'mileage' => ['type' => 'integer'],
                'provider_type' => ['type' => 'string', 'enum' => array_column(ProviderType::cases(), 'value')],
                'shop_name' => $string,
                'shop_email' => $string,
                'title' => $string,
                'category' => ['type' => 'string', 'enum' => array_column(ServiceCategory::cases(), 'value')],
                'line_items' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'required' => ['description', 'kind', 'amount'],
                        'properties' => [
                            'description' => $string,
                            'kind' => ['type' => 'string', 'enum' => ['part', 'labor', 'fee']],
                            'amount' => ['type' => 'number'],
                        ],
                    ],
                ],
                'total' => ['type' => 'number'],
                'tasks' => ['type' => 'array', 'items' => $string],
                'vin' => $string,
            ],
        ];
    }
}
