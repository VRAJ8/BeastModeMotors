import './bootstrap';

// Alpine ships with Livewire (injected via @livewireScripts), so it is not imported here.

// Swap any broken remote car photo for the branded placeholder.
document.addEventListener(
    'error',
    (event) => {
        const img = event.target;
        if (img instanceof HTMLImageElement && img.dataset.fallback && img.src !== img.dataset.fallback) {
            img.src = img.dataset.fallback;
        }
    },
    true,
);
