<?php

use Anthropic\Client;
use App\Livewire\RecordForm;
use App\Models\ServiceRecord;
use App\Models\Shop;
use App\Models\Vehicle;
use App\Services\ReceiptReader;
use App\Services\ShopVerifier;
use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

/**
 * A ReceiptReader whose API calls go to canned responses; $sent collects the request bodies.
 */
function readerAnswering(array $responses, ?ArrayObject &$sent = null): ReceiptReader
{
    $sent = new ArrayObject;
    $stack = HandlerStack::create(new MockHandler($responses));
    $stack->push(Middleware::history($sent));

    return new ReceiptReader(new Client(apiKey: 'test-key', requestOptions: ['transporter' => new Guzzle(['handler' => $stack]), 'maxRetries' => 0]));
}

function apiReply(array $extracted, string $stopReason = 'end_turn'): Response
{
    return new Response(200, ['Content-Type' => 'application/json'], json_encode([
        'id' => 'msg_test', 'type' => 'message', 'role' => 'assistant', 'model' => 'claude-opus-5-5',
        'content' => [['type' => 'text', 'text' => json_encode($extracted)]],
        'stop_reason' => $stopReason, 'stop_sequence' => null,
        'usage' => ['input_tokens' => 1200, 'output_tokens' => 180],
    ]));
}

function extracted(array $overrides = []): array
{
    return array_merge([
        'performed_on' => now()->subDays(3)->toDateString(),
        'mileage' => 30450,
        'provider_type' => 'independent',
        'shop_name' => 'Northside Garage',
        'shop_email' => 'desk@northside.test',
        'title' => 'Front brake pads & rotors',
        'category' => 'repair',
        'line_items' => [
            ['description' => 'Brake pads (front)', 'kind' => 'part', 'amount' => 89.5],
            ['description' => 'Labor', 'kind' => 'labor', 'amount' => 120],
            ['description' => 'Sales tax', 'kind' => 'fee', 'amount' => 7.16],
        ],
        'total' => 216.66,
        'tasks' => ['brake fluid'],
        'vin' => '',
    ], $overrides);
}

beforeEach(function () {
    config(['passport.receipts.scanner' => true]);
    $this->vehicle = car(['current_mileage' => 30000]);
    $this->vehicle->reminders()->create(['task' => 'Brake fluid', 'interval_months' => 24]);
    $this->vehicle->reminders()->create(['task' => 'Engine oil & filter', 'interval_miles' => 5000]);
});

// --- The service, through the real SDK -------------------------------------------------------------------------

it('sends a photo as an image and asks for the receipt as structured JSON', function () {
    $reader = readerAnswering([apiReply(extracted())], $sent);

    $scan = $reader->read(UploadedFile::fake()->image('receipt.jpg')->get(), 'image/jpeg', $this->vehicle);

    $body = json_decode((string) $sent[0]['request']->getBody(), true);
    expect($body['model'])->toBe('claude-opus-5-5')
        ->and($body['messages'][0]['content'][0]['type'])->toBe('image')
        ->and($body['messages'][0]['content'][0]['source']['media_type'])->toBe('image/jpeg')
        ->and($body['messages'][0]['content'][1]['text'])->toContain('Brake fluid; Engine oil & filter')
        ->and($body['output_config']['format']['type'])->toBe('json_schema')
        ->and($body['output_config']['effort'])->toBe('low')
        ->and($body['fallbacks'])->toBe('default')
        ->and($sent[0]['request']->getHeaderLine('anthropic-beta'))->toContain('server-side-fallback-2026-07-01');

    expect($scan)->toMatchArray([
        'mileage' => 30450, 'shop_name' => 'Northside Garage', 'category' => 'repair', 'total' => 216.66,
        // Matched to the car's own item, whatever case the model used.
        'tasks' => ['Brake fluid'],
        'vin_mismatch' => false,
    ])->and($scan['line_items'])->toHaveCount(3);
});

it('sends a PDF as a document', function () {
    $reader = readerAnswering([apiReply(extracted())], $sent);

    $reader->read("%PDF-1.4\n%fake", 'application/pdf', $this->vehicle);

    $block = json_decode((string) $sent[0]['request']->getBody(), true)['messages'][0]['content'][0];
    expect($block['type'])->toBe('document')->and($block['source']['media_type'])->toBe('application/pdf');
});

it('drops what it can\'t trust instead of filling it in', function () {
    $reader = readerAnswering([apiReply(extracted([
        'performed_on' => now()->addMonth()->toDateString(),
        'mileage' => 0,
        'provider_type' => 'garage',
        'shop_email' => 'not an email',
        'category' => 'spaceship',
        'line_items' => [['description' => '', 'kind' => 'part', 'amount' => 10], ['description' => 'Rotor', 'kind' => 'gizmo', 'amount' => -5]],
        'tasks' => ['Timing belt'],
    ]))]);

    $scan = $reader->read(UploadedFile::fake()->image('r.png')->get(), 'image/png', $this->vehicle);

    expect($scan)->toMatchArray([
        'performed_on' => null, 'mileage' => null, 'provider_type' => 'independent', 'shop_email' => null,
        'category' => 'maintenance', 'line_items' => [], 'tasks' => [],
    ]);
});

it('notices a receipt for a different car', function () {
    $reader = readerAnswering([apiReply(extracted(['vin' => '1FTFW1E50JFA00001']))]);

    $scan = $reader->read(UploadedFile::fake()->image('r.png')->get(), 'image/png', $this->vehicle);

    expect($scan['vin_mismatch'])->toBeTrue()->and($scan['vin'])->toBe('1FTFW1E50JFA00001');
});

it('gives up quietly when the API refuses, errors or answers nonsense', function (Response $response) {
    $reader = readerAnswering([$response]);

    expect($reader->read(UploadedFile::fake()->image('r.png')->get(), 'image/png', $this->vehicle))->toBeNull();
})->with([
    'refusal' => fn () => apiReply(extracted(), 'refusal'),
    'server error' => fn () => new Response(500, ['Content-Type' => 'application/json'], '{"type":"error","error":{"type":"api_error","message":"boom"}}'),
    'not JSON' => fn () => new Response(200, ['Content-Type' => 'application/json'], json_encode([
        'id' => 'msg', 'type' => 'message', 'role' => 'assistant', 'model' => 'claude-opus-5-5',
        'content' => [['type' => 'text', 'text' => 'sorry, blurry']], 'stop_reason' => 'end_turn', 'stop_sequence' => null,
        'usage' => ['input_tokens' => 1, 'output_tokens' => 1],
    ])),
]);

it('does not send files the API can\'t read', function () {
    $reader = readerAnswering([], $sent);

    expect($reader->read('heic bytes', 'image/heic', $this->vehicle))->toBeNull()
        ->and($reader->read(str_repeat('x', ReceiptReader::MAX_IMAGE_BYTES + 1), 'image/jpeg', $this->vehicle))->toBeNull()
        ->and($sent)->toHaveCount(0);
});

// --- The record form -------------------------------------------------------------------------------------------

/**
 * Swap the real reader for one that returns a fixed scan (or null), counting calls.
 */
function fakeReader(?array $scan): object
{
    $fake = new class($scan) extends ReceiptReader
    {
        public int $calls = 0;

        public function __construct(private ?array $scan) {}

        public function read(string $contents, string $mime, Vehicle $vehicle): ?array
        {
            $this->calls++;

            return $this->scan;
        }
    };
    app()->instance(ReceiptReader::class, $fake);

    return $fake;
}

it('fills the record form from a receipt, for the owner to check and save', function () {
    fakeReader(ReceiptReader::normalize(extracted(), $this->vehicle, ['Brake fluid', 'Engine oil & filter']));

    $form = Livewire::actingAs($this->vehicle->owner)->test(RecordForm::class, ['vehicle' => $this->vehicle])
        ->set('receipts', [UploadedFile::fake()->image('northside-invoice.jpg')])
        ->assertSee('Fill from receipt')
        ->call('scanReceipt', 0)
        ->assertHasNoErrors()
        ->assertSet('title', 'Front brake pads & rotors')
        ->assertSet('category', 'repair')
        ->assertSet('mileage', 30450)
        ->assertSet('provider_name', 'Northside Garage')
        ->assertSet('provider_email', 'desk@northside.test')
        ->assertSet('cost', '')
        ->assertSee('Filled in from northside-invoice.jpg');

    expect($form->get('items'))->toHaveCount(3)
        ->and($form->get('items')[2])->toBe(['description' => 'Sales tax', 'kind' => 'fee', 'amount' => '7.16'])
        ->and($form->get('reminderIds'))->toBe([$this->vehicle->reminders()->firstWhere('task', 'Brake fluid')->id]);

    // Nothing is saved until the owner says so; then the receipt is attached as evidence.
    expect(ServiceRecord::count())->toBe(0);
    Notification::fake();
    $form->set('requestVerification', false)->call('save')->assertHasNoErrors();

    $record = ServiceRecord::first();
    expect($record->title)->toBe('Front brake pads & rotors')
        ->and($record->cost_cents)->toBe(21666)
        ->and($record->documents()->count())->toBe(1);
});

it('picks a directory shop by its email without showing the address', function () {
    Notification::fake();
    $other = ServiceRecord::factory()->create(['vehicle_id' => car()->id]);
    $v = app(ShopVerifier::class)->request($other, $other->vehicle->owner, 'Northside', 'desk@northside.test');
    app(ShopVerifier::class)->answer($v, true, 'Dee', null, '127.0.0.1', businessName: 'Northside Garage & Tire');
    $shop = tap(Shop::firstWhere('email', 'desk@northside.test'))->update(['vetted_at' => now()]);
    fakeReader(ReceiptReader::normalize(extracted(), $this->vehicle, []));

    Livewire::actingAs($this->vehicle->owner)->test(RecordForm::class, ['vehicle' => $this->vehicle])
        ->set('receipts', [UploadedFile::fake()->image('r.jpg')])
        ->call('scanReceipt', 0)
        ->assertSet('shopId', $shop->id)
        ->assertSet('provider_name', 'Northside Garage & Tire')
        ->assertSet('provider_email', '')
        ->assertDontSee('desk@northside.test');
});

it('warns when the receipt is for another car', function () {
    fakeReader(ReceiptReader::normalize(extracted(['vin' => '1FTFW1E50JFA00001']), $this->vehicle, []));

    Livewire::actingAs($this->vehicle->owner)->test(RecordForm::class, ['vehicle' => $this->vehicle])
        ->set('receipts', [UploadedFile::fake()->image('r.jpg')])
        ->call('scanReceipt', 0)
        ->assertSee('1FTFW1E50JFA00001')
        ->assertSee("isn't this car's");
});

it('says so when a receipt can\'t be read, and keeps the file attached', function () {
    fakeReader(null);

    Livewire::actingAs($this->vehicle->owner)->test(RecordForm::class, ['vehicle' => $this->vehicle])
        ->set('receipts', [UploadedFile::fake()->image('blurry.jpg')])
        ->call('scanReceipt', 0)
        ->assertHasErrors('receipts')
        ->assertSee('We couldn&#039;t read this receipt', false)
        ->assertCount('receipts', 1);
});

it('caps how many receipts one person can have read each day', function () {
    $fake = fakeReader(ReceiptReader::normalize(extracted(), $this->vehicle, []));
    foreach (range(1, config('passport.receipts.scans_per_day')) as $i) {
        RateLimiter::hit('receipt-scan:'.$this->vehicle->user_id, 86400);
    }

    Livewire::actingAs($this->vehicle->owner)->test(RecordForm::class, ['vehicle' => $this->vehicle])
        ->set('receipts', [UploadedFile::fake()->image('r.jpg')])
        ->call('scanReceipt', 0)
        ->assertHasErrors('receipts');

    expect($fake->calls)->toBe(0);
});

it('stays out of the way when scanning is off or the record is locked', function () {
    $fake = fakeReader(ReceiptReader::normalize(extracted(), $this->vehicle, []));
    config(['passport.receipts.scanner' => false]);

    Livewire::actingAs($this->vehicle->owner)->test(RecordForm::class, ['vehicle' => $this->vehicle])
        ->set('receipts', [UploadedFile::fake()->image('r.jpg')])
        ->assertDontSee('Fill from receipt')
        ->call('scanReceipt', 0);

    config(['passport.receipts.scanner' => true]);
    $verified = ServiceRecord::factory()->verified()->create(['vehicle_id' => $this->vehicle->id, 'title' => 'Timing belt']);
    Livewire::actingAs($this->vehicle->owner)->test(RecordForm::class, ['vehicle' => $this->vehicle, 'record' => $verified])
        ->set('receipts', [UploadedFile::fake()->image('r.jpg')])
        ->assertDontSee('Fill from receipt')
        ->call('scanReceipt', 0)
        ->assertSet('title', 'Timing belt');

    expect($fake->calls)->toBe(0);
});
