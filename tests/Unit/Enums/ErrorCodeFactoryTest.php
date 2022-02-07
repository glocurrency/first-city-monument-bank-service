<?php

namespace GloCurrency\FirstCityMonumentBank\Tests\Unit\Enums;

use GloCurrency\FirstCityMonumentBank\Tests\TestCase;
use GloCurrency\FirstCityMonumentBank\Enums\TransactionStateCodeEnum;
use GloCurrency\FirstCityMonumentBank\Enums\ErrorCodeFactory;
use BrokeYourBike\FirstCityMonumentBank\Enums\ErrorCodeEnum;

class ErrorCodeFactoryTest extends TestCase
{
    /** @test */
    public function it_can_return_transaction_state_code_from_all_values()
    {
        foreach (ErrorCodeEnum::cases() as $value) {
            $this->assertInstanceOf(TransactionStateCodeEnum::class, ErrorCodeFactory::getTransactionStateCode($value));
        }
    }
}
