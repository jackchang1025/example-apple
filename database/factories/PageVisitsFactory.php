<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PageVisits>
 */
class PageVisitsFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uri'         => $this->faker->url,
            'name'        => $this->faker->word,
            'user_agent'  => $this->faker->userAgent,
            'ip_address'  => $this->faker->ipv4,
            'visited_at'  => $this->faker->dateTimeThisMonth,
            'country'     => $this->faker->country,
            'city'        => $this->faker->city,
            'latitude'    => $this->faker->latitude,
            'longitude'   => $this->faker->longitude,
            'device_type' => $this->faker->randomElement(['desktop', 'mobile', 'tablet']),
            'browser'     => $this->faker->randomElement(['Chrome', 'Firefox', 'Safari', 'Edge']),
            'platform'    => $this->faker->randomElement(['Windows', 'MacOS', 'Linux', 'iOS', 'Android']),
            'created_at'  => $this->faker->dateTimeThisMonth,
            'updated_at'  => $this->faker->dateTimeThisMonth,
        ];
    }

    /**
     * 指定中国城市的 IP 和地理信息
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function chineseCity(): Factory
    {
        return $this->state(function (array $attributes) {
            $chineseCities = [
                '北京市'   => ['ip' => '123.121.0.0', 'lat' => 39.9042, 'lon' => 116.4074],
                '包头市'    => ['ip' => '123.121.0.0', 'lat' => 39.9042, 'lon' => 116.4074],
                '上海市'  => ['ip' => '101.80.0.0', 'lat' => 31.2304, 'lon' => 121.4737],
                '广州市' => ['ip' => '14.18.0.0', 'lat' => 23.1291, 'lon' => 113.2644],
                '深圳市'  => ['ip' => '14.153.0.0', 'lat' => 22.5431, 'lon' => 114.0579],
                '杭州市'  => ['ip' => '115.236.0.0', 'lat' => 30.2741, 'lon' => 120.1551],
                '成都市'   => ['ip' => '61.139.0.0', 'lat' => 30.5728, 'lon' => 104.0668],
                '南京市'   => ['ip' => '180.94.0.0', 'lat' => 32.0603, 'lon' => 118.7969],
                '武汉市'     => ['ip' => '58.48.0.0', 'lat' => 30.5928, 'lon' => 114.3055],
                '西安市'    => ['ip' => '61.134.0.0', 'lat' => 34.3416, 'lon' => 108.9398],
                '重庆市' => ['ip' => '61.128.0.0', 'lat' => 29.4316, 'lon' => 106.9123],
            ];

            $city     = $this->faker->randomElement(array_keys($chineseCities));
            $cityData = $chineseCities[$city];

            return [
                'country'    => 'China',
                'city'       => $city,
                'ip_address' => $cityData['ip'],
                'latitude'   => $cityData['lat'],
                'longitude'  => $cityData['lon'],
            ];
        });
    }
}
