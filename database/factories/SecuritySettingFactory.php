<?php

namespace Database\Factories;

use App\Models\SecuritySetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SecuritySetting>
 */
class SecuritySettingFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = SecuritySetting::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'authorized_ips' => null,
            'safe_entrance' => null,
            'configuration' => [
                'language' => 'zh',
                'country_code' => '+86',
                'blacklist_ips' => [],
            ],
        ];
    }

    /**
     * 创建带有黑名单IP的设置
     */
    public function withBlacklistIps(array $ips): static
    {
        return $this->state(function (array $attributes) use ($ips) {
            $attributes['configuration']['blacklist_ips'] = $ips;
            return $attributes;
        });
    }

    /**
     * 创建带有授权IP的设置
     */
    public function withAuthorizedIps(array $ips): static
    {
        return $this->state(function (array $attributes) use ($ips) {
            $attributes['authorized_ips'] = $ips;
            return $attributes;
        });
    }

    /**
     * 创建带有特定语言的设置
     */
    public function withLanguage(string $language): static
    {
        return $this->state(function (array $attributes) use ($language) {
            $attributes['configuration']['language'] = $language;
            return $attributes;
        });
    }
}
