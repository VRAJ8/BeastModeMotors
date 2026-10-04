<?php

namespace App\Livewire\Vehicle;

use App\Enums\DocumentType;
use App\Livewire\Concerns\ManagesVehicle;
use App\Models\Vehicle;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

class Documents extends Component
{
    use ManagesVehicle, WithFileUploads;

    #[Locked]
    public Vehicle $vehicle;

    public $file = null;

    public string $type = 'title';

    public string $name = '';

    public string $expires_on = '';

    public function updatedFile(): void
    {
        if ($this->file && $this->name === '') {
            $this->name = str($this->file->getClientOriginalName())->beforeLast('.')->limit(150)->toString();
        }
    }

    public function upload(): void
    {
        $this->validate([
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp,heic', 'max:'.config('passport.max_upload_kb')],
            'type' => ['required', Rule::enum(DocumentType::class)],
            'name' => ['required', 'string', 'max:160'],
            'expires_on' => ['nullable', 'date'],
        ]);

        $this->vehicle->documents()->create([
            'ownership_id' => $this->vehicle->currentOwnership?->getKey(),
            'uploaded_by' => Auth::id(),
            'type' => $this->type,
            'name' => $this->name,
            'path' => $this->file->store("vehicles/{$this->vehicle->getKey()}/documents", 'local'),
            'mime' => $this->file->getMimeType(),
            'size' => $this->file->getSize(),
            'expires_on' => $this->expires_on ?: null,
        ]);

        $this->reset('file', 'name', 'expires_on');
        $this->dispatch('toast', message: 'Document stored.');
    }

    public function delete(int $id): void
    {
        $this->vehicle->documents()->whereKey($id)->firstOrFail()->delete();
        $this->dispatch('toast', message: 'Document deleted.');
    }

    public function render()
    {
        $documents = $this->vehicle->documents()->with('record')->get();

        return view('livewire.vehicle.documents', [
            'groups' => $documents->groupBy(fn ($d) => $d->type->transfersWithCar() ? 'car' : 'personal'),
            'types' => DocumentType::options(),
            'expiring' => DocumentType::tryFrom($this->type)?->expires() ?? false,
        ]);
    }
}
