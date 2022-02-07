<?php

namespace GloCurrency\FirstCityMonumentBank\Tests\Unit\Models;

use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;
use GloCurrency\FirstCityMonumentBank\Tests\TestCase;
use GloCurrency\FirstCityMonumentBank\Models\Sender;
use BrokeYourBike\FirstCityMonumentBank\Enums\IdentificationTypeEnum;
use BrokeYourBike\BaseModels\BaseUuid;

class SenderTest extends TestCase
{
    /** @test */
    public function it_extends_base_model(): void
    {
        $parent = get_parent_class(Sender::class);

        $this->assertSame(BaseUuid::class, $parent);
    }

    /** @test */
    public function it_uses_soft_deletes(): void
    {
        $usedTraits = class_uses(Sender::class);

        $this->assertArrayHasKey(SoftDeletes::class, $usedTraits);
    }

    /** @test */
    public function it_return_id_expiry_as_datetime(): void
    {
        $sender = new Sender();
        $sender->id_expiry = '2020-01-01';

        $this->assertInstanceOf(Carbon::class, $sender->id_expiry);
    }

    /** @test */
    public function it_returns_id_type_as_enum(): void
    {
        $sender = new Sender();
        $sender->setRawAttributes([
            'id_type' => IdentificationTypeEnum::PASSPORT->value,
        ]);

        $this->assertEquals(IdentificationTypeEnum::PASSPORT, $sender->id_type);
    }

    /** @test */
    public function it_can_return_country_code_alpha2()
    {
        $sender = new Sender();
        $sender->country_code = 'USA';

        $this->assertSame('US', $sender->country_code_alpha2);
    }
}
