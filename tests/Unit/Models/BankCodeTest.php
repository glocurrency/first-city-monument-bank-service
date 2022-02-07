<?php

namespace GloCurrency\FirstCityMonumentBank\Tests\Unit\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use GloCurrency\FirstCityMonumentBank\Tests\TestCase;
use GloCurrency\FirstCityMonumentBank\Models\BankCode;
use BrokeYourBike\BaseModels\BaseUuid;

class BankCodeTest extends TestCase
{
    /** @test */
    public function it_extends_base_model(): void
    {
        $parent = get_parent_class(BankCode::class);

        $this->assertSame(BaseUuid::class, $parent);
    }

    /** @test */
    public function it_uses_soft_deletes(): void
    {
        $usedTraits = class_uses(BankCode::class);

        $this->assertArrayHasKey(SoftDeletes::class, $usedTraits);
    }
}
