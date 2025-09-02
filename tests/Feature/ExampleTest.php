<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Account;
use App\Models\Phone;
use Illuminate\Database\Eloquent\Collection;

uses(RefreshDatabase::class);

it('returns a successful response', function () {
    $response = $this->get('/');


    expect(Phone::all())->toBeInstanceOf(Collection::class)->dump();
    $response->assertStatus(200);
});
