<?php

use App\Services\VinDecoder;

beforeEach(fn () => $this->decoder = new VinDecoder);

it('accepts a VIN whose check digit matches', function () {
    expect($this->decoder->isValid('1HGCM82633A004352'))->toBeTrue();
});

it('rejects a VIN with a single character changed', function () {
    expect($this->decoder->isValid('1HGCM82633A004353'))->toBeFalse()
        ->and($this->decoder->expectedCheckDigit('1HGCM82633A004353'))->toBe('5');
});

it('rejects the letters I, O and Q and wrong lengths', function (string $vin) {
    expect($this->decoder->hasValidFormat($vin))->toBeFalse();
})->with(['1HGCM82633A00435', '1HGCM82633A0043521', 'IHGCM82633A004352', '1HGCM82633AO04352']);

it('normalises spacing, dashes and case', function () {
    expect(VinDecoder::normalize(' 1hgcm-8263 3a004352 '))->toBe('1HGCM82633A004352');
});

it('decodes the model year using the 7th character to pick the 30-year cycle', function () {
    expect($this->decoder->modelYear('1HGCM82633A004352', 2026))->toBe(2003)
        ->and($this->decoder->modelYear('WP0AB2A90KS123456', 2026))->toBe(2019)
        ->and($this->decoder->modelYear('5YJ3E1EB0MF123456', 2026))->toBe(2021);
});

it('identifies the manufacturer and country from the WMI', function () {
    $decoded = $this->decoder->decode('WP0AB2A90KS123456');

    expect($decoded['make'])->toBe('Porsche')->and($decoded['country'])->toBe('Germany');
});

it('can repair a check digit', function () {
    $fixed = $this->decoder->withCheckDigit('1HGCM82603A004352');

    expect($fixed)->toBe('1HGCM82633A004352')->and($this->decoder->isValid($fixed))->toBeTrue();
});
