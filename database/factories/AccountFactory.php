<?php

namespace Database\Factories;

use App\Apple\Enums\AccountStatus;
use App\Apple\Enums\AccountType;
use App\Models\Account;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Account>
 */
class AccountFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Account::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'appleid' => fake()->unique()->safeEmail(),
            'password' => fake()->password(8, 20),
            'bind_phone' => null,
            'bind_phone_address' => null,
            'country_code' => fake()->countryCode(),
            'status' => AccountStatus::LOGIN_SUCCESS,
            'type' => AccountType::USER_SUBMITTED,
            'dsid' => fake()->unique()->randomNumber(8),
        ];
    }

    /**
     * Indicate that the account is bound successfully.
     */
    public function boundSuccessfully(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => AccountStatus::BIND_SUCCESS,
            'bind_phone' => fake()->phoneNumber(),
            'bind_phone_address' => fake()->url(),
        ]);
    }

    /**
     * Indicate that the account binding is in progress.
     */
    public function bindingInProgress(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => AccountStatus::BIND_ING,
        ]);
    }

    /**
     * Indicate that the account binding failed.
     */
    public function bindingFailed(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => AccountStatus::BIND_FAIL,
        ]);
    }

    /**
     * Indicate that the account has theft protection enabled.
     */
    public function theftProtection(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => AccountStatus::THEFT_PROTECTION,
        ]);
    }

    /**
     * Indicate that the account login failed.
     */
    public function loginFailed(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => AccountStatus::LOGIN_FAIL,
        ]);
    }

    /**
     * Indicate that the account has authentication success.
     */
    public function authSuccess(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => AccountStatus::AUTH_SUCCESS,
        ]);
    }

    /**
     * Indicate that the account authentication failed.
     */
    public function authFailed(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => AccountStatus::AUTH_FAIL,
        ]);
    }

    /**
     * Indicate that the account is imported type.
     */
    public function imported(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => AccountType::IMPORTED,
        ]);
    }

    /**
     * Indicate that the account has a phone bound.
     */
    public function withPhone(): static
    {
        return $this->state(fn(array $attributes) => [
            'bind_phone' => fake()->phoneNumber(),
            'bind_phone_address' => fake()->url(),
        ]);
    }
}
