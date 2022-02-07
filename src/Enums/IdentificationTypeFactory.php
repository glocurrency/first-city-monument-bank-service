<?php

namespace GloCurrency\FirstCityMonumentBank\Enums;

use GloCurrency\MiddlewareBlocks\Enums\IdentificationTypeEnum as MIdentificationTypeEnum;
use BrokeYourBike\FirstCityMonumentBank\Enums\IdentificationTypeEnum;

class IdentificationTypeFactory
{
    public static function makeFrom(MIdentificationTypeEnum $identificationType): ?IdentificationTypeEnum
    {
        return match ($identificationType) {
            MIdentificationTypeEnum::PASSPORT => IdentificationTypeEnum::PASSPORT,
            default => null,
        };
    }
}
