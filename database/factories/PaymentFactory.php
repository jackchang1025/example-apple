<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\Account;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Payment 模型工厂
 * 
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Payment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'account_id' => Account::factory(),
            'payment_id' => fake()->uuid(),
            'payment_method_name' => fake()->randomElement([
                'Visa',
                'Mastercard',
                'American Express',
                'PayPal',
                'Apple Pay',
                'WeChat Pay',
                'Alipay'
            ]),
            'payment_method_detail' => fake()->creditCardNumber(),
            'partner_login' => fake()->userName(),
            'phone_number' => [
                'countryCode' => fake()->countryCode(),
                'nationalNumber' => fake()->phoneNumber(),
                'formattedNumber' => fake()->e164PhoneNumber(),
            ],
            'owner_name' => [
                'firstName' => fake()->firstName(),
                'lastName' => fake()->lastName(),
                'middleName' => fake()->optional()->firstName(),
                'fullName' => fake()->name(),
            ],
            'billing_address' => [
                'street' => fake()->streetAddress(),
                'city' => fake()->city(),
                'state' => fake()->state(),
                'postalCode' => fake()->postcode(),
                'country' => fake()->countryCode(),
                'countryName' => fake()->country(),
            ],
            'payment_account_country_code' => fake()->countryCode(),
            'type' => fake()->randomElement([
                'CREDIT_CARD',
                'DEBIT_CARD',
                'PAYPAL',
                'BANK_ACCOUNT',
                'DIGITAL_WALLET'
            ]),
            'is_primary' => fake()->boolean(30), // 30% chance of being primary
            'we_chat_pay' => fake()->boolean(10), // 10% chance
            'absolute_image_path' => fake()->optional()->imageUrl(100, 60),
            'absolute_image_path_2x' => fake()->optional()->imageUrl(200, 120),
            'payment_supported' => fake()->boolean(90), // 90% chance
            'family_card' => fake()->boolean(20), // 20% chance
            'expiration_supported' => fake()->boolean(80), // 80% chance
            'payment_method_option' => [
                'autoRenew' => fake()->boolean(),
                'currency' => fake()->currencyCode(),
                'supportedRegions' => fake()->randomElements([
                    'US',
                    'CN',
                    'JP',
                    'GB',
                    'DE',
                    'FR',
                    'AU'
                ], fake()->numberBetween(1, 3)),
                'minimumAmount' => fake()->randomFloat(2, 1, 100),
                'maximumAmount' => fake()->randomFloat(2, 100, 10000),
            ],
            'default_shipping_address' => [
                'street' => fake()->streetAddress(),
                'city' => fake()->city(),
                'state' => fake()->state(),
                'postalCode' => fake()->postcode(),
                'country' => fake()->countryCode(),
                'isDefault' => fake()->boolean(),
            ],
        ];
    }

    /**
     * 创建主要支付方式状态
     *
     * @return static
     */
    public function primary(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_primary' => true,
            'payment_supported' => true,
        ]);
    }

    /**
     * 创建信用卡支付方式状态
     *
     * @return static
     */
    public function creditCard(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => 'CREDIT_CARD',
            'payment_method_name' => fake()->randomElement(['Visa', 'Mastercard', 'American Express']),
            'payment_method_detail' => fake()->creditCardNumber(),
            'expiration_supported' => true,
        ]);
    }

    /**
     * 创建数字钱包支付方式状态
     *
     * @return static
     */
    public function digitalWallet(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => 'DIGITAL_WALLET',
            'payment_method_name' => fake()->randomElement(['Apple Pay', 'PayPal', 'WeChat Pay', 'Alipay']),
            'we_chat_pay' => fake()->boolean(50),
            'expiration_supported' => false,
        ]);
    }

    /**
     * 创建家庭卡状态
     *
     * @return static
     */
    public function familyCard(): static
    {
        return $this->state(fn(array $attributes) => [
            'family_card' => true,
            'is_primary' => false,
            'payment_supported' => true,
        ]);
    }

    /**
     * 创建不支持的支付方式状态
     *
     * @return static
     */
    public function unsupported(): static
    {
        return $this->state(fn(array $attributes) => [
            'payment_supported' => false,
            'is_primary' => false,
        ]);
    }

    /**
     * 为指定账户创建支付方式
     *
     * @param Account $account
     * @return static
     */
    public function forAccount(Account $account): static
    {
        return $this->state(fn(array $attributes) => [
            'account_id' => $account->id,
        ]);
    }
}
