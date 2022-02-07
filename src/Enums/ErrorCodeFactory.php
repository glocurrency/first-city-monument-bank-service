<?php

namespace GloCurrency\FirstCityMonumentBank\Enums;

use BrokeYourBike\FirstCityMonumentBank\Enums\ErrorCodeEnum;
use GloCurrency\FirstCityMonumentBank\Enums\TransactionStateCodeEnum;

class ErrorCodeFactory
{
    public static function getTransactionStateCode(ErrorCodeEnum $errorCode): TransactionStateCodeEnum
    {
        return match ($errorCode) {
            ErrorCodeEnum::SUCCESS => TransactionStateCodeEnum::PAID,
            ErrorCodeEnum::DOUBLE_SUCCESS => TransactionStateCodeEnum::PAID,
            ErrorCodeEnum::CREDENTIALS_ERROR => TransactionStateCodeEnum::API_ERROR,
            ErrorCodeEnum::AMOUNT_ERROR => TransactionStateCodeEnum::TRANSACTION_AMOUNT_INVALID,
            ErrorCodeEnum::NOT_PERMITTED_ERROR => TransactionStateCodeEnum::API_ERROR,
            ErrorCodeEnum::CURRENCY_ERROR => TransactionStateCodeEnum::API_ERROR,
            ErrorCodeEnum::INVALID_PARAMS_ERROR => TransactionStateCodeEnum::API_ERROR,
            ErrorCodeEnum::COUNTRY_ERROR => TransactionStateCodeEnum::API_ERROR,
            ErrorCodeEnum::GENERIC_ERROR => TransactionStateCodeEnum::API_ERROR,
            ErrorCodeEnum::PUBLIC_KEY_ERROR => TransactionStateCodeEnum::API_ERROR,
            ErrorCodeEnum::TOKEN_ERROR => TransactionStateCodeEnum::API_ERROR,
            ErrorCodeEnum::DESTINATION_ERROR => TransactionStateCodeEnum::API_ERROR,
            ErrorCodeEnum::TRANSACTION_ERROR => TransactionStateCodeEnum::API_ERROR,
            ErrorCodeEnum::NAME_ENQUIRY_ERROR => TransactionStateCodeEnum::RECIPIENT_NAME_VALIDATION_FAILED,
            ErrorCodeEnum::PAYOUT_TYPE_ERROR => TransactionStateCodeEnum::API_ERROR,
            ErrorCodeEnum::REFERENCE_ERROR => TransactionStateCodeEnum::DUPLICATE_TRANSACTION,
            ErrorCodeEnum::NOT_FOUND => TransactionStateCodeEnum::API_ERROR,
            ErrorCodeEnum::PENDING => TransactionStateCodeEnum::PROCESSING,
            ErrorCodeEnum::INITIATED => TransactionStateCodeEnum::PROCESSING,
            ErrorCodeEnum::IN_PROGRESS => TransactionStateCodeEnum::PROCESSING,
            ErrorCodeEnum::LOCKED => TransactionStateCodeEnum::PROCESSING,
            ErrorCodeEnum::CANCELED => TransactionStateCodeEnum::CANCELED,
        };
    }
}
