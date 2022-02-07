<?php

namespace GloCurrency\FirstCityMonumentBank\Tests\Unit\Models;

use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;
use GloCurrency\FirstCityMonumentBank\Tests\TestCase;
use GloCurrency\FirstCityMonumentBank\Models\Recipient;
use BrokeYourBike\HasSourceModel\SourceModelInterface;
use BrokeYourBike\FirstCityMonumentBank\Enums\IdentificationTypeEnum;
use BrokeYourBike\BaseModels\BaseUuid;

class RecipientTest extends TestCase
{
    /** @test */
    public function it_extends_base_model(): void
    {
        $parent = get_parent_class(Recipient::class);

        $this->assertSame(BaseUuid::class, $parent);
    }

    /** @test */
    public function it_uses_soft_deletes(): void
    {
        $usedTraits = class_uses(Recipient::class);

        $this->assertArrayHasKey(SoftDeletes::class, $usedTraits);
    }

    /** @test */
    public function it_implemets_source_model_interface(): void
    {
        $this->assertInstanceOf(SourceModelInterface::class, new Recipient());
    }

    /** @test */
    public function it_return_id_expiry_as_datetime(): void
    {
        $recipient = new Recipient();
        $recipient->id_expiry = '2020-01-01';

        $this->assertInstanceOf(Carbon::class, $recipient->id_expiry);
    }

    /** @test */
    public function it_returns_id_type_as_enum(): void
    {
        $recipient = new Recipient();
        $recipient->setRawAttributes([
            'id_type' => IdentificationTypeEnum::PASSPORT->value,
        ]);

        $this->assertEquals(IdentificationTypeEnum::PASSPORT, $recipient->id_type);
    }

    /** @test */
    public function it_can_return_country_code_alpha2()
    {
        $recipient = new Recipient();
        $recipient->country_code = 'USA';

        $this->assertSame('US', $recipient->country_code_alpha2);
    }
}
