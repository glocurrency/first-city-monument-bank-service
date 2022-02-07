<?php

namespace GloCurrency\FirstCityMonumentBank\Tests\Unit\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use GloCurrency\FirstCityMonumentBank\Tests\TestCase;
use GloCurrency\FirstCityMonumentBank\Models\Transaction;
use GloCurrency\FirstCityMonumentBank\Enums\TransactionStateCodeEnum;
use BrokeYourBike\HasSourceModel\SourceModelInterface;
use BrokeYourBike\FirstCityMonumentBank\Enums\TransactionTypeEnum;
use BrokeYourBike\BaseModels\BaseUuid;

class TransactionTest extends TestCase
{
    /** @test */
    public function it_extends_base_model(): void
    {
        $parent = get_parent_class(Transaction::class);

        $this->assertSame(BaseUuid::class, $parent);
    }

    /** @test */
    public function it_uses_soft_deletes(): void
    {
        $usedTraits = class_uses(Transaction::class);

        $this->assertArrayHasKey(SoftDeletes::class, $usedTraits);
    }

    /** @test */
    public function it_implemets_source_model_interface(): void
    {
        $this->assertInstanceOf(SourceModelInterface::class, new Transaction());
    }

    /** @test */
    public function it_returns_amount_as_float(): void
    {
        $transaction = new Transaction();
        $transaction->amount = '10';

        $this->assertIsFloat($transaction->amount);
    }

    /** @test */
    public function it_returns_state_as_enum(): void
    {
        $transaction = new Transaction();
        $transaction->setRawAttributes([
            'state_code' => TransactionStateCodeEnum::PAID->value,
        ]);

        $this->assertEquals(TransactionStateCodeEnum::PAID, $transaction->state_code);
    }

    /** @test */
    public function it_returns_operation_as_enum(): void
    {
        $transaction = new Transaction();
        $transaction->setRawAttributes([
            'operation' => TransactionTypeEnum::BANK->value,
        ]);

        $this->assertEquals(TransactionTypeEnum::BANK, $transaction->operation);
    }

    /** @test */
    public function it_can_return_country_code_alpha2()
    {
        $transaction = new Transaction();
        $transaction->country_code = 'USA';

        $this->assertSame('US', $transaction->country_code_alpha2);
    }
}
