<?php

namespace GloCurrency\FirstCityMonumentBank\Tests\Unit\Enums;

use GloCurrency\FirstCityMonumentBank\Tests\TestCase;
use GloCurrency\MiddlewareBlocks\Enums\IdentificationTypeEnum as MIdentificationTypeEnum;
use GloCurrency\FirstCityMonumentBank\Enums\IdentificationTypeFactory;
use BrokeYourBike\FirstCityMonumentBank\Enums\IdentificationTypeEnum;

class IdentificationTypeFactoryTest extends TestCase
{
    /**
     * @test
     * @dataProvider idTypeProvider
     * */
    public function it_can_return_enum_from(MIdentificationTypeEnum $sourceIdType, bool $shouldReturn)
    {
        $idType = IdentificationTypeFactory::makeFrom($sourceIdType);

        if ($shouldReturn) {
            $this->assertInstanceOf(IdentificationTypeEnum::class, $idType);
        } else {
            $this->assertNull($idType);
        }
    }

    public function idTypeProvider(): array
    {
        $data = collect(MIdentificationTypeEnum::cases())
            ->filter(fn($c) => !in_array($c, [
                MIdentificationTypeEnum::PASSPORT,
            ]))
            ->flatten()
            ->map(fn($c) => [$c, false])
            ->toArray();

        $data[] = [MIdentificationTypeEnum::PASSPORT, true];

        return $data;
    }
}
