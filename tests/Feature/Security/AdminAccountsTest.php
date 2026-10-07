<?php

use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\User;
use Livewire\Livewire;

it('grants and revokes staff access from the admin panel', function () {
    config(['passport.demo' => false]);
    $admin = User::factory()->admin()->create();
    $member = User::factory()->create();

    Livewire::actingAs($admin)->test(ManageUsers::class)
        ->callTableAction('edit', $member, ['name' => $member->name, 'email' => $member->email, 'is_admin' => true]);
    expect($member->fresh()->is_admin)->toBeTrue();

    Livewire::actingAs($admin)->test(ManageUsers::class)
        ->callTableAction('edit', $member, ['name' => $member->name, 'email' => $member->email, 'is_admin' => false]);
    expect($member->fresh()->is_admin)->toBeFalse();
});

it('keeps real accounts read-only and masked in the public demo', function () {
    config(['passport.demo' => true]);
    $admin = User::factory()->admin()->create(['email' => 'admin@beastmodemotors.test']);
    $visitor = User::factory()->create(['email' => 'jane.real@gmail.com']);

    Livewire::actingAs($admin)->test(ManageUsers::class)
        ->assertTableActionHidden('edit', $visitor)
        ->assertTableActionHidden('delete', $visitor)
        ->assertActionHidden('create')
        ->assertDontSee('jane.real@gmail.com')
        ->assertSee('j•••@gmail.com');
});
