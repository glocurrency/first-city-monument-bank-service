<?php

namespace GloCurrency\FirstCityMonumentBank\Tests\Unit;

use Illuminate\Foundation\Testing\WithFaker;
use GloCurrency\FirstCityMonumentBank\Tests\TestCase;
use GloCurrency\FirstCityMonumentBank\Config;
use BrokeYourBike\FirstCityMonumentBank\Interfaces\ConfigInterface;

class ConfigTest extends TestCase
{
    use WithFaker;

    /** @test */
    public function it_implemets_config_interface(): void
    {
        $this->assertInstanceOf(ConfigInterface::class, new Config());
    }

    /** @test */
    public function it_will_return_empty_string_if_value_not_found()
    {
        $configPrefix = 'services.first_city_monument_bank.api';

        // config is empty
        config([$configPrefix => []]);

        $config = new Config();

        $this->assertSame('', $config->getUrl());
        $this->assertSame('', $config->getClientId());
        $this->assertSame('', $config->getClientSecret());
    }

    /** @test */
    public function it_can_return_values()
    {
        $url = $this->faker->url();
        $clientId = $this->faker->uuid();
        $clientSecret = $this->faker->password();

        $configPrefix = 'services.first_city_monument_bank.api';

        config(["{$configPrefix}.url" => $url]);
        config(["{$configPrefix}.client_id" => $clientId]);
        config(["{$configPrefix}.client_secret" => $clientSecret]);

        $config = new Config();

        $this->assertSame($url, $config->getUrl());
        $this->assertSame($clientId, $config->getClientId());
        $this->assertSame($clientSecret, $config->getClientSecret());
    }
}
