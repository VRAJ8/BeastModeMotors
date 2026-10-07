<div class="space-y-6">
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ([
            ['Total since '.$ownership->started_on->format('M Y'), money($report['total']), $report['months'].' months owned'],
            ['Per month', money($report['per_month']), 'all running costs'],
            ['Per mile', $report['per_mile'] !== null ? '$'.number_format($report['per_mile'], 2) : '—', number_format($report['miles']).' mi driven'],
            ['Fuel economy', $report['economy'] ? $report['economy']['value'].' '.$report['economy']['unit'] : '—', $report['economy'] ? 'from '.$report['economy']['fills'].' fill-ups' : 'log fill-ups with odometer + volume'],
        ] as [$label, $value, $sub])
            <div class="card card-pad">
                <p class="eyebrow">{{ $label }}</p>
                <p class="num mt-2 text-2xl font-semibold">{{ $value }}</p>
                <p class="mt-1 text-xs text-muted">{{ $sub }}</p>
            </div>
        @endforeach
    </div>

    <div class="grid gap-6 lg:grid-cols-[1fr_340px]">
        <div class="space-y-6">
            <section class="card card-pad">
                <h2 class="panel-title">Last 12 months</h2>
                <x-chart.bars class="mt-8" :bars="$report['monthly']" />
            </section>

            <section class="card card-pad">
                <h2 class="panel-title">Where the money goes</h2>
                @if ($report['total'] === 0)
                    <p class="mt-3 text-sm text-muted">Add expenses and service costs to see the breakdown.</p>
                @else
                    <ul class="mt-4 space-y-3">
                        @foreach ($report['by_category'] as $key => $cents)
                            <li>
                                <div class="flex justify-between text-sm"><span>{{ $labels[$key] ?? $key }}</span><span class="num font-medium">{{ money($cents) }}</span></div>
                                <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-paper-deep"><div class="h-full rounded-full {{ $key === 'maintenance' ? 'bg-accent' : 'bg-ink' }}" style="width: {{ $cents / $report['total'] * 100 }}%"></div></div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            <section class="card overflow-hidden">
                <h2 class="panel-title px-5 pt-5 sm:px-6">Expenses</h2>
                @if ($expenses->isEmpty())
                    <p class="px-6 pt-2 pb-6 text-sm text-muted">No expenses logged yet. Service costs come from your history automatically.</p>
                @else
                    <table class="mt-3 w-full text-sm">
                        <tbody class="divide-y divide-line">
                            @foreach ($expenses as $expense)
                                <tr wire:key="expense-{{ $expense->id }}">
                                    <td class="num py-3 pl-5 text-muted sm:pl-6">{{ $expense->spent_on->format('M j') }}</td>
                                    <td class="py-3"><span class="font-medium">{{ $expense->category->getLabel() }}</span>
                                        <span class="block text-xs text-muted">{{ collect([$expense->odometer ? miles($expense->odometer) : null, $expense->volume ? $expense->volume.' '.$expense->category->volumeUnit() : null, $expense->notes])->filter()->implode(' · ') }}</span></td>
                                    <td class="num py-3 text-right font-medium">{{ money($expense->amount_cents, true) }}</td>
                                    <td class="w-10 py-3 pr-3 text-right"><button wire:click="delete({{ $expense->id }})" class="rounded p-1 text-muted hover:text-danger" title="Delete"><x-heroicon-m-x-mark class="size-4" /></button></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="border-t border-line px-5 py-3">{{ $expenses->links() }}</div>
                @endif
            </section>
        </div>

        <aside>
            <form wire:submit="add" class="card card-pad sticky top-24">
                <h3 class="panel-title">Add an expense</h3>
                <div class="mt-4 space-y-4">
                    <div>
                        <label class="label" for="exp-category">Category</label>
                        <select id="exp-category" wire:model.live="category" class="input">
                            @foreach ($categories as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="label" for="amount">Amount</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-3 grid place-items-center text-sm text-muted">$</span>
                                <input id="amount" inputmode="decimal" wire:model="amount" class="input num pl-7">
                            </div>
                            @error('amount') <p class="error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label" for="spent_on">Date</label>
                            <input id="spent_on" type="date" wire:model="spent_on" class="input">
                            @error('spent_on') <p class="error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    @if ($unit)
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="label" for="volume">Volume ({{ $unit }})</label>
                                <input id="volume" inputmode="decimal" wire:model="volume" class="input num">
                                @error('volume') <p class="error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="label" for="odometer">Odometer</label>
                                <input id="odometer" type="number" wire:model="odometer" class="input num">
                                @error('odometer') <p class="error">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        <p class="hint -mt-2">Fill the tank each time and add the odometer to track real-world {{ $unit === 'gal' ? 'mpg' : 'efficiency' }}.</p>
                    @endif
                    <div>
                        <label class="label" for="notes">Note</label>
                        <input id="notes" wire:model="notes" class="input" placeholder="optional">
                        @error('notes') <p class="error">{{ $message }}</p> @enderror
                    </div>
                </div>
                <button class="btn-primary mt-6 w-full">Add</button>
                <p class="mt-3 flex items-center gap-1.5 text-xs text-muted"><x-heroicon-m-eye-slash class="size-3.5" /> Costs are private to you and never transfer to the next owner.</p>
            </form>
        </aside>
    </div>
</div>
