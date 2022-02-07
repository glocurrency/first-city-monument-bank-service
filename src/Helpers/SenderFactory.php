<?php

namespace GloCurrency\FirstCityMonumentBank\Helpers;

use GloCurrency\MiddlewareBlocks\Enums\IdentificationTypeEnum as MIdentificationTypeEnum;
use GloCurrency\MiddlewareBlocks\Contracts\SenderInterface as MSenderInterface;
use GloCurrency\FirstCityMonumentBank\Models\Sender;
use GloCurrency\FirstCityMonumentBank\Enums\IdentificationTypeFactory;

class SenderFactory
{
    public static function makeFrom(MSenderInterface $from): Sender
    {
        $target = new Sender([
            'name' => $from->getName(),
            'mobile' => $from->getPhoneNumber(),
            'address' => $from->getCountryCode(),
            'country_code' => $from->getCountryCode(),
        ]);

        if ($from->getIdentificationType() instanceof MIdentificationTypeEnum) {
            // TODO: rename to common names `identification_*`
            $target->id_type = IdentificationTypeFactory::makeFrom($from->getIdentificationType());
            $target->id_number = $from->getIdentificationNumber() ?? null;
            $target->id_expiry = $from->getIdentificationExpiry() ?? null;
        }

        return $target;
    }
}
