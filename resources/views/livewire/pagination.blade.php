@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination" class="flex items-center justify-between gap-4">
        <button type="button" wire:click="previousPage('{{ $paginator->getPageName() }}')" @disabled($paginator->onFirstPage()) class="btn-outline px-4 py-2" x-on:click="window.scrollTo({ top: 0, behavior: 'smooth' })">← Prev</button>

        <div class="hidden items-center gap-1 sm:flex">
            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="px-2 text-mist">{{ $element }}</span>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        <button type="button" wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')" x-on:click="window.scrollTo({ top: 0, behavior: 'smooth' })"
                                wire:key="page-{{ $page }}"
                                @class(['h-10 min-w-10 rounded-md px-3 text-sm font-semibold transition', 'bg-gold text-ink' => $page == $paginator->currentPage(), 'text-silver hover:bg-white/5' => $page != $paginator->currentPage()])
                                @if ($page == $paginator->currentPage()) aria-current="page" @endif>{{ $page }}</button>
                    @endforeach
                @endif
            @endforeach
        </div>

        <span class="text-sm text-mist sm:hidden">Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}</span>

        <button type="button" wire:click="nextPage('{{ $paginator->getPageName() }}')" @disabled(! $paginator->hasMorePages()) class="btn-outline px-4 py-2" x-on:click="window.scrollTo({ top: 0, behavior: 'smooth' })">Next →</button>
    </nav>
@endif
