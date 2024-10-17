<?php

namespace Database\Factories;

use App\Models\ProxyConfiguration;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class ProxyConfigurationFactory extends Factory
{
    protected $model = ProxyConfiguration::class;

    public function definition(): array
    {
        return [
            'name'              => $this->faker->name(),
            'configuration'     => $this->faker->words(),
            'is_active'         => $this->faker->boolean(),
            'created_at'        => Carbon::now(),
            'updated_at'        => Carbon::now(),
            'ipaddress_enabled' => $this->faker->randomElements([0, 1]),
            'proxy_enabled'     => $this->faker->randomElements([0, 1]),
        ];
    }
}
