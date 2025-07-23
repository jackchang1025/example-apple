<?php

namespace Database\Factories;

use App\Models\Devices;
use App\Models\Account;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Devices 模型工厂
 * 
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Devices>
 */
class DevicesFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Devices::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'account_id' => Account::factory(),
            'device_id' => fake()->uuid(),
            'name' => fake()->randomElement([
                "John's iPhone",
                "iPhone",
                "iPad",
                "MacBook Pro",
                "Apple Watch",
                "AirPods Pro",
                "iMac"
            ]),
            'device_class' => fake()->randomElement([
                'iPhone',
                'iPad',
                'Mac',
                'AppleWatch',
                'AppleTV',
                'AirPods'
            ]),
            'qualified_device_class' => fake()->randomElement([
                'com.apple.iphone',
                'com.apple.ipad',
                'com.apple.mac',
                'com.apple.watch',
                'com.apple.tv',
                'com.apple.airpods'
            ]),
            'model_name' => fake()->randomElement([
                'iPhone 15 Pro',
                'iPhone 15',
                'iPad Pro',
                'iPad Air',
                'MacBook Pro',
                'MacBook Air',
                'iMac',
                'Apple Watch Series 9'
            ]),
            'os' => fake()->randomElement(['iOS', 'iPadOS', 'macOS', 'watchOS', 'tvOS']),
            'os_version' => fake()->randomElement([
                '17.2.1',
                '17.1.0',
                '14.2.1',
                '10.2.0',
                '17.2.0'
            ]),
            'supports_verification_codes' => fake()->boolean(80), // 80% chance
            'current_device' => fake()->boolean(20), // 20% chance of being current
            'unsupported' => fake()->boolean(5), // 5% chance of being unsupported
            'has_apple_pay_cards' => fake()->boolean(60), // 60% chance
            'has_active_surf_account' => fake()->boolean(30), // 30% chance
            'removal_pending' => fake()->boolean(5), // 5% chance
            'list_image_location' => fake()->optional()->imageUrl(120, 120),
            'list_image_location_2x' => fake()->optional()->imageUrl(240, 240),
            'list_image_location_3x' => fake()->optional()->imageUrl(360, 360),
            'infobox_image_location' => fake()->optional()->imageUrl(200, 200),
            'infobox_image_location_2x' => fake()->optional()->imageUrl(400, 400),
            'infobox_image_location_3x' => fake()->optional()->imageUrl(600, 600),
            'device_detail_uri' => fake()->optional()->url(),
            'device_detail_http_method' => fake()->randomElement(['GET', 'POST']),
            'imei' => fake()->optional()->numerify('###############'), // 15 digits
            'meid' => fake()->optional()->regexify('[A-F0-9]{14}'), // 14 hex digits
            'serial_number' => fake()->optional()->regexify('[A-Z0-9]{10,12}'), // 10-12 chars
        ];
    }

    /**
     * 创建当前设备状态
     *
     * @return static
     */
    public function currentDevice(): static
    {
        return $this->state(fn(array $attributes) => [
            'current_device' => true,
            'unsupported' => false,
            'supports_verification_codes' => true,
        ]);
    }

    /**
     * 创建iPhone设备状态
     *
     * @return static
     */
    public function iPhone(): static
    {
        return $this->state(fn(array $attributes) => [
            'device_class' => 'iPhone',
            'qualified_device_class' => 'com.apple.iphone',
            'model_name' => fake()->randomElement([
                'iPhone 15 Pro Max',
                'iPhone 15 Pro',
                'iPhone 15',
                'iPhone 14'
            ]),
            'os' => 'iOS',
            'supports_verification_codes' => true,
            'has_apple_pay_cards' => fake()->boolean(80),
        ]);
    }

    /**
     * 创建iPad设备状态
     *
     * @return static
     */
    public function iPad(): static
    {
        return $this->state(fn(array $attributes) => [
            'device_class' => 'iPad',
            'qualified_device_class' => 'com.apple.ipad',
            'model_name' => fake()->randomElement([
                'iPad Pro',
                'iPad Air',
                'iPad',
                'iPad mini'
            ]),
            'os' => 'iPadOS',
            'supports_verification_codes' => true,
        ]);
    }

    /**
     * 创建Mac设备状态
     *
     * @return static
     */
    public function mac(): static
    {
        return $this->state(fn(array $attributes) => [
            'device_class' => 'Mac',
            'qualified_device_class' => 'com.apple.mac',
            'model_name' => fake()->randomElement([
                'MacBook Pro',
                'MacBook Air',
                'iMac',
                'Mac mini',
                'Mac Studio'
            ]),
            'os' => 'macOS',
            'supports_verification_codes' => fake()->boolean(70),
        ]);
    }

    /**
     * 创建不支持的设备状态
     *
     * @return static
     */
    public function unsupported(): static
    {
        return $this->state(fn(array $attributes) => [
            'unsupported' => true,
            'supports_verification_codes' => false,
            'has_apple_pay_cards' => false,
            'current_device' => false,
        ]);
    }

    /**
     * 为指定账户创建设备
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
