<?php

namespace App\Models;

use App\Enums\DocumentType;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;

/**
 * A private file (receipt, title, insurance card…) stored on the local disk.
 */
class Document extends Model
{
    protected $fillable = [
        'vehicle_id', 'ownership_id', 'service_record_id', 'uploaded_by', 'type', 'name', 'path', 'mime', 'size',
        'expires_on', 'expiry_notified_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => DocumentType::class,
            'expires_on' => 'date',
            'expiry_notified_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Vehicle, $this>
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * @return BelongsTo<ServiceRecord, $this>
     */
    public function record(): BelongsTo
    {
        return $this->belongsTo(ServiceRecord::class, 'service_record_id');
    }

    /**
     * @param  Builder<Document>  $query
     */
    public function scopeTransferable(Builder $query): void
    {
        $types = collect(DocumentType::cases())->filter->transfersWithCar()->map->value->all();

        $query->whereIn('type', $types);
    }

    public function isImage(): bool
    {
        return str_starts_with((string) $this->mime, 'image/');
    }

    public function humanSize(): string
    {
        return Number::fileSize($this->size);
    }

    public function expiryState(): ?string
    {
        if (! $this->expires_on) {
            return null;
        }

        return match (true) {
            $this->expires_on->isPast() => 'expired',
            $this->expires_on->lte(now()->addDays(30)) => 'soon',
            default => 'ok',
        };
    }

    public static function disk(): Filesystem
    {
        return Storage::disk(config('passport.disks.documents'));
    }

    protected static function booted(): void
    {
        // The file goes once the delete has committed: a rolled-back transaction must not leave a row without its file.
        static::deleted(fn (Document $document) => DB::afterCommit(fn () => static::disk()->delete($document->path)));
    }
}
