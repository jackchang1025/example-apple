<?php

namespace Database\Factories;

use App\Models\Account;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Account>
 */
class AccountFactory extends Factory
{
    protected $model = Account::class;

    public function definition(): array
    {
        return [
            'account' => $this->faker->unique()->safeEmail,
            'password' => bcrypt('password'),
            'bind_phone' => $this->faker->phoneNumber,
            'bind_phone_address' => $this->faker->city,
        ];
    }
}
