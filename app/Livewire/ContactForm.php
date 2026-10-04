<?php

namespace App\Livewire;

use App\Enums\LeadType;
use App\Livewire\Concerns\GuardsPublicForms;
use App\Models\Lead;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ContactForm extends Component
{
    use GuardsPublicForms;

    public const TOPICS = [
        'general' => 'General question',
        'finance' => 'Financing & leasing',
        'sourcing' => 'Find me a specific car',
        'service' => 'Service & detailing',
    ];

    public string $topic = 'general';

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $message = '';

    public bool $sent = false;

    public function mount(string $topic = 'general'): void
    {
        $this->prefillContactDetails();
        $this->topic = array_key_exists((string) $topic, self::TOPICS) ? $topic : 'general';
    }

    public function submit(): void
    {
        $this->validate([
            'topic' => 'required|in:'.implode(',', array_keys(self::TOPICS)),
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:32',
            'message' => 'required|string|min:10|max:2000',
        ]);

        if (! $this->isSpam()) {
            $this->throttle('contact');

            Lead::create([
                'type' => $this->topic === 'finance' ? LeadType::Finance : LeadType::General,
                'user_id' => Auth::id(),
                'name' => $this->name,
                'email' => $this->email,
                'phone' => $this->phone ?: null,
                'message' => $this->message,
                'meta' => ['topic' => self::TOPICS[$this->topic]],
            ]);
        }

        $this->sent = true;
    }

    public function render()
    {
        return view('livewire.contact-form');
    }
}
