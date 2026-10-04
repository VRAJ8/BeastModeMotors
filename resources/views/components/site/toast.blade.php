{{-- Flash + Livewire toast. Dispatch with: $this->dispatch('toast', message: '...') --}}
<div x-data="{
        toasts: [],
        add(message, type = 'success') {
            const id = Date.now() + Math.random();
            this.toasts.push({ id, message, type });
            setTimeout(() => this.toasts = this.toasts.filter(t => t.id !== id), 4000);
        }
     }"
     x-init="@if (session('status')) add(@js(session('status'))) @endif"
     @toast.window="add($event.detail.message, $event.detail.type)"
     class="pointer-events-none fixed inset-x-0 bottom-4 z-[60] flex flex-col items-center gap-2 px-4 sm:items-end sm:px-6"
     aria-live="polite">
    <template x-for="toast in toasts" :key="toast.id">
        <div x-transition.opacity.duration.300ms
             class="pointer-events-auto flex w-full max-w-sm items-center gap-3 rounded-lg border px-4 py-3 text-sm shadow-2xl shadow-black/50"
             :class="toast.type === 'error' ? 'border-ember/40 bg-ember/15 text-white' : 'border-gold/30 bg-graphite text-white'">
            <span class="h-2 w-2 shrink-0 rounded-full" :class="toast.type === 'error' ? 'bg-ember' : 'bg-gold'"></span>
            <span x-text="toast.message"></span>
        </div>
    </template>
</div>
