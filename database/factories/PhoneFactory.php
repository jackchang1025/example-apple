<?php

namespace Database\Factories;

use App\Models\Phone;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Phone>
 */
class PhoneFactory extends Factory
{
    protected $model = Phone::class;

    public function definition(): array
    {
        return [
            'phone'             => $this->faker->phoneNumber,
            'phone_address'     => $this->faker->city,
            'country_code'      => $this->faker->countryCode,
            'country_dial_code' => $this->faker->numberBetween(1, 999),
            'status'            => $this->faker->randomElement(['normal', 'invalid', 'bound']),
        ];
    }
}
