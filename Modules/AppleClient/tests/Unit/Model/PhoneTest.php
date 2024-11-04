<?php

use Illuminate\Foundation\Testing\TestCase;

uses(TestCase::class);


beforeEach(function () {

    $this->phone = \App\Models\Phone::factory()->create();

});

it('can create a new phone', function () {


    dd($this->phone->national_number, $this->phone->toArray());
});
