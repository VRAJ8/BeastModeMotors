<?php

namespace App\Livewire;

use App\Enums\DocumentType;
use App\Enums\ProviderType;
use App\Enums\ServiceCategory;
use App\Livewire\Concerns\ManagesVehicle;
use App\Livewire\Concerns\PicksShop;
use App\Models\ServiceRecord;
use App\Models\Shop;
use App\Models\Vehicle;
use App\Services\MaintenancePlanner;
use App\Services\ReceiptReader;
use App\Services\ShopVerifier;
use App\Support\ImageMetadata;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class RecordForm extends Component
{
    use ManagesVehicle, PicksShop, WithFileUploads;

    #[Locked]
    public Vehicle $vehicle;

    #[Locked]
    public ?ServiceRecord $record = null;

    public string $category = 'maintenance';

    public string $title = '';

    public string $performed_on = '';

    public ?int $mileage = null;

    public string $cost = '';

    public string $provider_type = 'independent';

    public string $provider_name = '';

    public string $provider_email = '';

    public string $description = '';

    /** @var list<array{description: string, kind: string, amount: string}> */
    public array $items = [];

    /** @var list<int> */
    public array $reminderIds = [];

    /** @var array<int, TemporaryUploadedFile> */
    public array $receipts = [];

    public bool $requestVerification = true;

    public bool $confirmLowerMileage = false;

    /** Set after a receipt fills the form in, until the owner saves: a reminder to check what it read. */
    #[Locked]
    public ?string $scanNote = null;

    #[Locked]
    public ?string $scanWarning = null;

    public function mount(Vehicle $vehicle, ?ServiceRecord $record = null): void
    {
        $this->vehicle = $vehicle;
        $this->record = $record?->exists ? $record : null;

        if ($this->record) {
            abort_unless($record->isFromOwnership($vehicle->currentOwnership?->getKey()), 403);

            $this->fill([
                'category' => $record->category->value,
                'title' => $record->title,
                'performed_on' => $record->performed_on->toDateString(),
                'mileage' => $record->mileage,
                'cost' => $record->cost_cents ? number_format($record->cost_cents / 100, 2, '.', '') : '',
                'provider_type' => $record->provider_type->value,
                'provider_name' => (string) $record->provider_name,
                'provider_email' => $this->adoptKnownShop($record->provider_email),
                'description' => (string) $record->description,
                'items' => collect($record->line_items ?? [])->map(fn ($i) => [
                    'description' => $i['description'],
                    'kind' => $i['kind'],
                    'amount' => number_format($i['amount_cents'] / 100, 2, '.', ''),
                ])->all(),
                'reminderIds' => array_values($vehicle->reminders->whereIn('task', $record->tasks ?? [])->modelKeys()),
                'requestVerification' => false,
            ]);

            return;
        }

        $this->performed_on = now()->toDateString();
        $this->mileage = $vehicle->current_mileage ?: null;

        // Arriving from "Log it" on a maintenance reminder.
        if ($task = $vehicle->reminders->firstWhere('id', (int) request('reminder'))) {
            $this->title = $task->task;
            $this->reminderIds = [$task->getKey()];
        }
    }

    protected function shopSearchTerm(): string
    {
        return $this->provider_name;
    }

    protected function applyPickedShop(?Shop $shop): void
    {
        $this->provider_name = $shop->name ?? '';
        $this->provider_email = '';
    }

    private function frozen(): bool
    {
        return $this->record !== null && ($this->record->isLocked() || $this->record->pendingVerification()->exists());
    }

    /**
     * Fill the form in from an uploaded receipt. Nothing is saved: the owner checks each field and saves.
     */
    public function scanReceipt(int $index, ReceiptReader $reader): void
    {
        $file = $this->receipts[$index] ?? null;

        if (! $file instanceof TemporaryUploadedFile || $this->frozen() || ! ReceiptReader::enabled()) {
            return;
        }

        if (! ReceiptReader::canRead($file->getMimeType(), $file->getSize())) {
            $this->addError('receipts', 'This file can\'t be read automatically: photos (JPG, PNG, WebP) up to 5 MB and PDFs work. You can still attach it and fill the form in yourself.');

            return;
        }

        $key = 'receipt-scan:'.Auth::id();

        if (RateLimiter::tooManyAttempts($key, config('passport.receipts.scans_per_day'))) {
            $this->addError('receipts', 'You\'ve read a lot of receipts today. Fill this one in by hand, or try again tomorrow.');

            return;
        }

        RateLimiter::hit($key, 86400);
        $scan = $reader->read($file->get(), $file->getMimeType(), $this->vehicle);

        if ($scan === null) {
            $this->addError('receipts', 'We couldn\'t read this receipt. It\'s still attached; fill the form in from it yourself.');

            return;
        }

        $this->resetErrorBag();
        $this->category = $scan['category'];
        $this->provider_type = $scan['provider_type'];
        $this->title = $scan['title'] ?: $this->title;
        $this->performed_on = $scan['performed_on'] ?? $this->performed_on;
        $this->mileage = $scan['mileage'] ?? $this->mileage;

        $this->shopId = null;
        $this->provider_name = '';
        $this->provider_email = '';

        if ($scan['provider_type'] !== ProviderType::Diy->value) {
            // A shop already in the directory is picked by its email, which the owner then never sees.
            $this->provider_email = $this->adoptKnownShop($scan['shop_email']);
            $this->provider_name = $this->pickedShop->name ?? $scan['shop_name'];
        }

        $this->items = array_map(fn (array $item) => [
            'description' => $item['description'],
            'kind' => $item['kind'],
            'amount' => number_format($item['amount'], 2, '.', ''),
        ], $scan['line_items']);
        $this->cost = $this->items === [] && $scan['total'] !== null ? number_format($scan['total'], 2, '.', '') : '';

        $this->reminderIds = array_values($this->vehicle->reminders->whereIn('task', $scan['tasks'])->modelKeys());

        $this->scanNote = 'Filled in from '.$file->getClientOriginalName().'. Check each field against the receipt before saving.';
        $this->scanWarning = $scan['vin_mismatch']
            ? "The receipt shows VIN {$scan['vin']}, which isn't this car's ({$this->vehicle->vin}). Make sure it's the right receipt."
            : null;
    }

    public function addItem(): void
    {
        $this->items[] = ['description' => '', 'kind' => 'part', 'amount' => ''];
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function removeUpload(int $index): void
    {
        unset($this->receipts[$index]);
        $this->receipts = array_values($this->receipts);
    }

    public function deleteAttachment(int $documentId): void
    {
        $this->record?->documents()->whereKey($documentId)->first()?->delete();
        unset($this->attachments);
    }

    #[Computed]
    public function attachments()
    {
        return $this->record?->documents()->get() ?? collect();
    }

    #[Computed]
    public function itemsTotal(): ?int
    {
        $cents = collect($this->items)->sum(fn ($i) => to_cents($i['amount'] ?? '') ?? 0);

        return $cents > 0 ? $cents : null;
    }

    /**
     * The highest reading recorded before this date (excluding this record), if the new mileage is below it.
     *
     * @return array{reading: int, date: string}|null
     */
    #[Computed]
    public function mileageConflict(): ?array
    {
        if (! $this->mileage || ! $this->performed_on) {
            return null;
        }

        $previous = $this->vehicle->readings()
            ->whereDate('recorded_on', '<=', $this->performed_on)
            ->when($this->record, fn ($q) => $q->where(fn ($q) => $q->whereNull('service_record_id')->orWhere('service_record_id', '!=', $this->record->getKey())))
            ->reorder()->orderByDesc('reading')
            ->first();

        return $previous && $this->mileage < $previous->reading
            ? ['reading' => $previous->reading, 'date' => $previous->recorded_on->format('M j, Y')]
            : null;
    }

    public function save(MaintenancePlanner $planner, ShopVerifier $verifier)
    {
        // Verified records are locked; while a shop is reviewing one, its facts are frozen too,
        // so the shop confirms exactly what it was shown.
        $locked = $this->frozen();
        $this->cost = clean_amount($this->cost);
        $this->items = array_map(fn ($i) => ['amount' => clean_amount($i['amount'] ?? '')] + $i, $this->items);

        $this->validate([
            'category' => ['required', Rule::enum(ServiceCategory::class)],
            'title' => ['required', 'string', 'max:140'],
            'performed_on' => ['required', 'date', 'before_or_equal:today', 'after_or_equal:'.($this->vehicle->year - 1).'-01-01'],
            'mileage' => ['required', 'integer', 'min:0', 'max:2000000'],
            'cost' => ['nullable', 'numeric', 'min:0', 'max:10000000'],
            'provider_type' => ['required', Rule::enum(ProviderType::class)],
            'provider_name' => ['nullable', 'string', 'max:120', Rule::requiredIf(fn () => $this->provider_type !== ProviderType::Diy->value)],
            'provider_email' => ['nullable', 'email', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'items' => ['array', 'max:30'],
            'items.*.description' => ['required', 'string', 'max:120'],
            'items.*.kind' => ['required', 'in:part,labor,fee'],
            'items.*.amount' => ['required', 'numeric', 'min:0', 'max:1000000'],
            'reminderIds' => ['array'],
            'receipts' => ['array', 'max:8'],
            'receipts.*' => ['file', 'mimes:pdf,jpg,jpeg,png,webp,heic', 'max:'.config('passport.max_upload_kb')],
        ], [
            'performed_on.after_or_equal' => 'That\'s before the car was built.',
            'provider_name.required' => 'Who did the work?',
            'items.*.description.required' => 'Describe this line.',
            'items.*.amount.required' => 'Enter an amount (0 is fine).',
            'items.*.amount.numeric' => 'Enter an amount like 129.95.',
            'items.*.amount.max' => 'That\'s more than $1,000,000 for one line.',
            'receipts.*.mimes' => 'Receipts must be PDFs or photos.',
        ]);

        if ($this->mileageConflict && ! $this->confirmLowerMileage && ! $locked) {
            throw ValidationException::withMessages(['mileage' => 'This is lower than an earlier reading. Tick the box to confirm it\'s correct.']);
        }

        $diy = $this->provider_type === ProviderType::Diy->value;
        $shop = $diy ? null : $this->pickedShop;

        $facts = [
            'category' => $this->category,
            'title' => $this->title,
            'performed_on' => $this->performed_on,
            'mileage' => $this->mileage,
            'cost_cents' => $this->itemsTotal ?? to_cents($this->cost),
            'line_items' => $this->items ? collect($this->items)->map(fn ($i) => [
                'description' => $i['description'],
                'kind' => $i['kind'],
                'amount_cents' => to_cents($i['amount']),
            ])->all() : null,
            'provider_type' => $this->provider_type,
            'provider_name' => $diy ? null : ($this->provider_name ?: null),
            'provider_email' => $diy ? null : ($shop->email ?? ($this->provider_email ?: null)),
        ];

        if ($this->record) {
            // A shop-verified record keeps its facts; only the notes and attachments can change.
            if (! $locked) {
                $this->record->update($facts + ['description' => $this->description ?: null]);
            }
            $record = $this->record;
        } else {
            $record = $this->vehicle->records()->create($facts + [
                'description' => $this->description ?: null,
                'ownership_id' => $this->vehicle->currentOwnership?->getKey(),
                'logged_by' => Auth::id(),
            ]);
        }

        foreach ($this->receipts as $file) {
            $record->documents()->create([
                'vehicle_id' => $this->vehicle->getKey(),
                'ownership_id' => $this->vehicle->currentOwnership?->getKey(),
                'uploaded_by' => Auth::id(),
                'type' => DocumentType::Receipt,
                'name' => str($file->getClientOriginalName())->beforeLast('.')->limit(150)->toString() ?: 'Receipt',
                'path' => ImageMetadata::storeClean($file, "vehicles/{$this->vehicle->getKey()}/documents", config('passport.disks.documents')),
                'mime' => $file->getMimeType(),
                'size' => $file->getSize(),
            ]);
        }

        if (! $locked) {
            $planner->applyRecord($record, array_map('intval', $this->reminderIds));
        }

        $message = $this->record ? 'Record updated.' : 'Record added to the passport.';

        $verifyEmail = $shop->email ?? $this->provider_email;

        if ($this->requestVerification && ! $locked && $verifyEmail && $this->provider_type !== ProviderType::Diy->value) {
            try {
                $verifier->request($record->fresh(), Auth::user(), $this->provider_name, $verifyEmail);
                $message .= ' We\'ve asked '.$this->provider_name.' to confirm it.';
            } catch (ValidationException) {
                // Not eligible right now (e.g. already pending); the owner can retry from the history page.
            }
        }

        session()->flash('toast', $message);

        return $this->redirectRoute('vehicles.history', $this->vehicle);
    }

    public function render()
    {
        return view('livewire.record-form', [
            'categories' => ServiceCategory::options(),
            'providers' => ProviderType::options(),
            'reminders' => $this->vehicle->reminders,
            'locked' => $this->frozen(),
            'scanner' => ReceiptReader::enabled() && ! $this->frozen(),
            'pending' => $this->record?->pendingVerification,
        ]);
    }
}
