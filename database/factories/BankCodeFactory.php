<?php

namespace GloCurrency\FirstCityMonumentBank\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use GloCurrency\FirstCityMonumentBank\Models\BankCode;
use GloCurrency\FirstCityMonumentBank\FirstCityMonumentBank;

class BankCodeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = BankCode::class;

    /**
     * Define the model's default state.
     *
     * @return array<string,mixed>
     */
    public function definition()
    {
        return [
            'id' => $this->faker->uuid(),
            'bank_id' => (FirstCityMonumentBank::$bankModel)::factory(),
            'code' => $this->faker->unique()->word(),
        ];
    }
}
