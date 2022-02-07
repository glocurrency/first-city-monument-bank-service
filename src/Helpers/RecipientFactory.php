<?php

namespace GloCurrency\FirstCityMonumentBank\Helpers;

use GloCurrency\MiddlewareBlocks\Enums\IdentificationTypeEnum as MIdentificationTypeEnum;
use GloCurrency\MiddlewareBlocks\Contracts\RecipientInterface as MRecipientInterface;
use GloCurrency\FirstCityMonumentBank\Models\Recipient;
use GloCurrency\FirstCityMonumentBank\Enums\IdentificationTypeFactory;

class RecipientFactory
{
    public static function makeFrom(MRecipientInterface $from): Recipient
    {
        $target = new Recipient([
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
