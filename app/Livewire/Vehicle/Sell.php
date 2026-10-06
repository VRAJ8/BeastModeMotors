<?php

namespace App\Livewire\Vehicle;

use App\Enums\ListingStatus;
use App\Livewire\Concerns\ManagesVehicle;
use App\Models\Listing;
use App\Models\Vehicle;
use App\Services\ListingPublisher;
use App\Support\ImageMetadata;
use App\Support\UsStates;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class Sell extends Component
{
    use ManagesVehicle, WithFileUploads;

    #[Locked]
    public Vehicle $vehicle;

    public string $price = '';

    public string $city = '';

    public string $state = '';

    public string $zip = '';

    public string $description = '';

    /** @var array<int, TemporaryUploadedFile> */
    public array $photos = [];

    public function mount(): void
    {
        $listing = $this->vehicle->openListing;
        $user = Auth::user();

        $this->price = $listing ? (string) ($listing->price_cents / 100) : '';
        $this->city = $listing->city ?? (string) $user->city;
        $this->state = $listing->state ?? (string) $user->state;
        $this->zip = (string) ($listing->zip ?? '');
        $this->description = (string) ($listing->description ?? '');
    }

    public function updatedPhotos(): void
    {
        $this->validate([
            'photos' => ['array', 'max:12'],
            'photos.*' => ['image', 'max:'.config('passport.max_upload_kb')],
        ]);

        $position = (int) $this->vehicle->photos()->max('position');

        foreach ($this->photos as $photo) {
            $this->vehicle->photos()->create([
                'path' => ImageMetadata::storeClean($photo, "vehicles/{$this->vehicle->getKey()}/photos", config('passport.disks.photos')),
                'position' => ++$position,
            ]);
        }

        $this->photos = [];
        $this->dispatch('toast', message: 'Photos added.');
    }

    public function makeCover(int $id): void
    {
        $this->vehicle->photos()->update(['position' => DB::raw('position + 1')]);
        $this->vehicle->photos()->whereKey($id)->update(['position' => 0]);
    }

    public function deletePhoto(int $id): void
    {
        $this->vehicle->photos()->whereKey($id)->firstOrFail()->delete();
    }

    public function save(ListingPublisher $publisher, bool $publish = false): void
    {
        $this->price = clean_amount($this->price);
        $this->validate([
            'price' => ['required', 'numeric', 'min:500', 'max:100000000'],
            'city' => ['required', 'string', 'max:80'],
            'state' => ['required', Rule::in(array_keys(UsStates::ALL))],
            'zip' => ['nullable', 'regex:/^\d{5}$/'],
            'description' => ['required', 'string', 'min:40', 'max:5000'],
        ], [
            'description.min' => 'Tell buyers a bit more — at least 40 characters.',
            'zip.regex' => 'Use a 5-digit ZIP code.',
        ]);

        $attributes = [
            'price_cents' => to_cents($this->price),
            'city' => $this->city,
            'state' => $this->state,
            'zip' => $this->zip ?: null,
            'description' => $this->description,
        ];

        $listing = $this->vehicle->openListing;

        if (! $listing && $this->removedListing()) {
            $this->addError('publish', 'Trust & safety removed your last listing for this car, so it can\'t be listed again. Contact support if you think that was a mistake.');

            return;
        }

        if ($listing) {
            $listing->update($attributes);
        } else {
            $listing = $this->vehicle->listings()->create($attributes + [
                'seller_id' => Auth::id(),
                'status' => ListingStatus::Draft,
                'mileage' => $this->vehicle->current_mileage,
            ]);
        }

        if ($publish && $listing->status === ListingStatus::Draft) {
            $publisher->publish($listing->fresh());
            $this->dispatch('toast', message: 'Your car is live on the marketplace.');
        } else {
            $this->dispatch('toast', message: 'Listing saved.');
        }

        $this->vehicle->unsetRelation('openListing');
    }

    public function publish(ListingPublisher $publisher): void
    {
        $this->save($publisher, publish: true);
    }

    public function withdraw(ListingPublisher $publisher): void
    {
        $listing = $this->vehicle->openListing;
        abort_unless($listing, 404);

        $publisher->withdraw($listing);
        $this->vehicle->unsetRelation('openListing');
        $this->dispatch('toast', message: 'Listing withdrawn.');
    }

    private function removedListing(): ?Listing
    {
        return $this->vehicle->listings()
            ->where('seller_id', Auth::id())
            ->where('status', ListingStatus::Removed)
            ->latest('updated_at')
            ->first();
    }

    public function render(ListingPublisher $publisher)
    {
        $listing = $this->vehicle->openListing()->with(['deals' => fn ($q) => $q->with('buyer', 'pendingOffer')])->first();
        $draft = $listing ?? new Listing(['price_cents' => to_cents($this->price) ?? 0, 'description' => $this->description]);
        $draft->setRelation('vehicle', $this->vehicle);

        return view('livewire.vehicle.sell', [
            'listing' => $listing,
            'removed' => $listing ? null : $this->removedListing(),
            'blockers' => $publisher->blockers($draft),
            'gallery' => $this->vehicle->photos()->get(),
            'states' => UsStates::ALL,
            'pastListings' => $this->vehicle->listings()->whereIn('status', [ListingStatus::Withdrawn, ListingStatus::Sold, ListingStatus::Removed])->where('seller_id', Auth::id())->get(),
        ]);
    }
}
