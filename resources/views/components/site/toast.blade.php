<div
    x-data="{ show: false, message: '', timer: null,
        flash(msg) { this.message = msg; this.show = true; clearTimeout(this.timer); this.timer = setTimeout(() => this.show = false, 3500); } }"
    x-init="@if (session('toast')) $nextTick(() => flash(@js(session('toast')))) @endif"
    @toast.window="flash($event.detail.message ?? $event.detail)"
    class="no-print pointer-events-none fixed inset-x-0 bottom-6 z-[60] flex justify-center px-4"
    aria-live="polite"
>
    <div x-cloak x-show="show" x-transition.opacity.duration.200ms class="pointer-events-auto flex items-center gap-3 rounded-full bg-ink py-2.5 pr-5 pl-3 text-sm font-medium text-white shadow-2xl shadow-ink/30">
        <span class="grid size-6 place-items-center rounded-full bg-accent"><x-heroicon-m-check class="size-4" /></span>
        <span x-text="message"></span>
    </div>
</div>
