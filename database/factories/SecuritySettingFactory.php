<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\SecuritySetting;
/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SecuritySetting>
 */
class SecuritySettingFactory extends Factory
{
    protected $model = SecuritySetting::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'authorized_ips' => [],
            'safe_entrance' => false,
            'configuration' => [],
        ];
    }
}
