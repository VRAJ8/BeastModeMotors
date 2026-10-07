<?php

use App\Models\User;

it('gives an existing account admin access, and takes it away', function () {
    $user = User::factory()->create(['email' => 'founder@example.com']);

    $this->artisan('passport:make-admin', ['email' => ' Founder@Example.com '])->assertSuccessful();
    expect($user->fresh()->is_admin)->toBeTrue();
    $this->actingAs($user->fresh())->get('/admin')->assertOk();

    $this->artisan('passport:make-admin', ['email' => 'founder@example.com', '--revoke' => true])->assertSuccessful();
    expect($user->fresh()->is_admin)->toBeFalse();

    $this->artisan('passport:make-admin', ['email' => 'nobody@example.com'])->assertFailed();
});
