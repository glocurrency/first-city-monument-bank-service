<?php

namespace GloCurrency\FirstCityMonumentBank\Tests\Unit\Enums;

use GloCurrency\FirstCityMonumentBank\Tests\TestCase;
use GloCurrency\MiddlewareBlocks\Enums\ProcessingItemStateCodeEnum as MProcessingItemStateCodeEnum;
use GloCurrency\FirstCityMonumentBank\Enums\TransactionStateCodeEnum;

class TransactionStateCodeTest extends TestCase
{
    /** @test */
    public function it_can_return_processing_item_state_code_from_all_values()
    {
        foreach (TransactionStateCodeEnum::cases() as $value) {
            $this->assertInstanceOf(MProcessingItemStateCodeEnum::class, $value->getProcessingItemStateCode());
        }
    }
}
