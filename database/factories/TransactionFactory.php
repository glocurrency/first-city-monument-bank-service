<?php

namespace GloCurrency\FirstCityMonumentBank\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use GloCurrency\FirstCityMonumentBank\Models\Transaction;
use GloCurrency\FirstCityMonumentBank\Models\Sender;
use GloCurrency\FirstCityMonumentBank\Models\Recipient;
use GloCurrency\FirstCityMonumentBank\FirstCityMonumentBank;
use GloCurrency\FirstCityMonumentBank\Enums\TransactionStateCodeEnum;
use BrokeYourBike\FirstCityMonumentBank\Enums\TransactionTypeEnum;

class TransactionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Transaction::class;

    /**
     * Define the model's default state.
     *
     * @return array<string,mixed>
     */
    public function definition()
    {
        return [
            'id' => $this->faker->uuid(),
            'transaction_id' => (FirstCityMonumentBank::$transactionModel)::factory(),
            'processing_item_id' => (FirstCityMonumentBank::$processingItemModel)::factory(),
            'fcmb_sender_id' => Sender::factory(),
            'fcmb_recipient_id' => Recipient::factory(),
            'state_code' => TransactionStateCodeEnum::LOCAL_UNPROCESSED,
            'operation' => TransactionTypeEnum::BANK,
            'reference' => $this->faker->uuid(),
            'amount' => $this->faker->randomFloat(2, 1),
            'currency_code' => $this->faker->currencyCode(),
            'country_code' => $this->faker->countryISOAlpha3(),
        ];
    }
}
