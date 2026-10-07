<?php

namespace App\Models;

use App\Enums\DealStatus;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable implements FilamentUser
{
    /** Shown in place of someone who has deleted their account (deals and listings outlive them). */
    public const DELETED = 'Deleted account';

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'city',
        'state',
        'password',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_admin;
    }

    /**
     * Cars this person currently owns.
     *
     * @return HasMany<Vehicle, $this>
     */
    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class)->orderBy('id');
    }

    /**
     * @return HasMany<Listing, $this>
     */
    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class, 'seller_id');
    }

    /**
     * @return BelongsToMany<Listing, $this>
     */
    public function savedListings(): BelongsToMany
    {
        return $this->belongsToMany(Listing::class, 'saved_listings')->withTimestamps();
    }

    /**
     * @return HasMany<SavedSearch, $this>
     */
    public function savedSearches(): HasMany
    {
        return $this->hasMany(SavedSearch::class)->latest('id');
    }

    /**
     * @return HasMany<Deal, $this>
     */
    public function purchases(): HasMany
    {
        return $this->hasMany(Deal::class, 'buyer_id');
    }

    /**
     * @return HasMany<Deal, $this>
     */
    public function sales(): HasMany
    {
        return $this->hasMany(Deal::class, 'seller_id');
    }

    public function completedSalesCount(): int
    {
        return $this->sales()->where('status', DealStatus::Completed)->count();
    }

    /**
     * First name plus last initial, which is all strangers on the marketplace see.
     */
    public function publicName(): string
    {
        if (! $this->exists) {
            return self::DELETED;
        }

        $parts = preg_split('/\s+/', trim(strip_tags($this->name))) ?: [];
        $first = array_shift($parts) ?: 'Member';

        // The initial comes from the last word that starts with a letter: "Mary-Jo O'Neil Jr." → "Mary-Jo J.".
        $initial = collect($parts)->reverse()->map(fn (string $word) => Str::substr($word, 0, 1))
            ->first(fn (string $char) => preg_match('/\pL/u', $char) === 1);

        return $initial ? $first.' '.Str::upper($initial).'.' : $first;
    }

    public function initials(): string
    {
        return collect(preg_split('/\s+/', trim($this->name)))
            ->filter()
            ->map(fn (string $part) => Str::upper(Str::substr($part, 0, 1)))
            ->take(2)
            ->implode('');
    }
}
