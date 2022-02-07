<?php

namespace GloCurrency\FirstCityMonumentBank\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use GloCurrency\FirstCityMonumentBank\Models\Recipient;

class RecipientFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Recipient::class;

    /**
     * Define the model's default state.
     *
     * @return array<string,mixed>
     */
    public function definition()
    {
        return [
            'id' => $this->faker->uuid(),
            'name' => $this->faker->name(),
            'country_code' => $this->faker->countryISOAlpha3(),
        ];
    }
}
