<?php

use App\Services\ScamShield;

beforeEach(fn () => $this->shield = new ScamShield);

it('lets ordinary messages through', function () {
    expect($this->shield->scan('Hi, is it still available? Could I see it on Saturday morning?'))->toBe([]);
});

it('flags the classic scam scripts', function (string $message, string $key) {
    expect(collect($this->shield->scan($message))->pluck('key'))->toContain($key);
})->with([
    ['Can I pay with Apple gift cards?', 'untraceable_payment'],
    ['I will pay in bitcoin', 'untraceable_payment'],
    ['My escrow service will hold the funds', 'fake_escrow'],
    ['I am deployed overseas right now', 'remote_seller'],
    ['Please send me the 6 digit code I texted you', 'verification_code'],
    ["I'll mail a check, just send back the difference", 'overpayment'],
    ['Send a deposit to hold it before viewing', 'deposit_unseen'],
    ['Other buyers are interested so act fast', 'pressure'],
    ['Text me at 305-555-0182', 'contact_details'],
]);

it('ranks severity', function () {
    $flags = $this->shield->scan('Act fast — pay with gift cards');

    expect(ScamShield::highestSeverity($flags))->toBe(ScamShield::HIGH)
        ->and(ScamShield::highestSeverity([]))->toBeNull();
});
