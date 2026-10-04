<x-layouts.site title="Buying & selling a car privately, safely" description="A practical guide to private car sales: avoiding scams, safe payment, inspections, paperwork and the handover.">
    <section class="grain border-b border-line">
        <div class="container-x max-w-4xl py-16">
            <p class="eyebrow">Safety guide</p>
            <h1 class="display mt-3 text-4xl sm:text-5xl">Buying or selling privately, without getting burned.</h1>
            <p class="mt-4 max-w-2xl text-lg text-ink-soft">Most private-sale scams follow a handful of scripts. Learn them once and you'll spot them every time.</p>
        </div>
    </section>

    <div class="container-x max-w-4xl py-16">
        <h2 class="display text-2xl">Red flags</h2>
        <div class="mt-6 grid gap-4 sm:grid-cols-2">
            @foreach ([
                ['The seller is "deployed" or abroad', 'The car is supposedly in storage or with a shipping company, and you\'re asked to pay before seeing it. There is no car.'],
                ['An escrow or shipping company you\'ve never heard of', 'Scammers build convincing websites for fake escrow and transport firms. Beast Mode Motors never holds money and never will.'],
                ['Gift cards, crypto or wire transfers', 'These payments can\'t be reversed or traced. No legitimate seller needs them.'],
                ['"Send me the code we just texted you"', 'It\'s a verification code for your account or phone number. Sharing it hands over control.'],
                ['A check for more than the price', 'The buyer "accidentally" overpays and asks for the difference back. The check bounces days later.'],
                ['A price far below everything similar', 'If it\'s too good to be true, it\'s bait. Compare the Passport Score and history, not just the price.'],
            ] as [$title, $text])
                <div class="card card-pad"><p class="flex items-center gap-2 font-semibold"><x-heroicon-s-exclamation-triangle class="size-5 text-danger" /> {{ $title }}</p><p class="mt-2 text-sm leading-relaxed text-ink-soft">{{ $text }}</p></div>
            @endforeach
        </div>

        <h2 class="display mt-16 text-2xl">A safe sale, step by step</h2>
        <ol class="mt-6 space-y-5">
            @foreach ([
                ['Talk in the deal room', 'It keeps a record of what was agreed and runs every message through our scam checks.'],
                ['Meet in daylight, somewhere public', 'Many police stations offer "safe exchange zones". Bring a friend.'],
                ['Match the VIN', 'Check the VIN on the dash, the door jamb, the title and the passport. All four should agree.'],
                ['Inspect before you pay', 'An independent pre-purchase inspection costs a fraction of one surprise repair. Record it in the deal room checklist.'],
                ['Pay traceably, in person', 'A bank transfer at the buyer\'s bank or a cashier\'s check you watch being issued. Sellers: wait until funds have cleared.'],
                ['Sign the paperwork together', 'Download the bill of sale from the deal room. Sign the title over. Check whether your state needs an odometer disclosure or notarisation.'],
                ['Confirm the handover', 'When both of you confirm, the passport moves to the buyer. Sellers: cancel your insurance and remove your plates if your state requires it.'],
            ] as $i => [$title, $text])
                <li class="flex gap-4"><span class="num grid size-8 shrink-0 place-items-center rounded-full bg-ink text-xs font-semibold text-white">{{ $i + 1 }}</span><div><p class="font-semibold">{{ $title }}</p><p class="mt-0.5 text-sm text-ink-soft">{{ $text }}</p></div></li>
            @endforeach
        </ol>

        <div class="mt-16 rounded-2xl bg-ink p-8 text-white">
            <p class="font-display text-xl font-semibold">Something feel wrong?</p>
            <p class="mt-2 text-white/70">Use “Report” on any listing. Our trust & safety team reviews every report and removes listings that break the rules.</p>
        </div>
    </div>
</x-layouts.site>
