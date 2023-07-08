<?php

namespace GloCurrency\FirstCityMonumentBank\Enums;

use GloCurrency\MiddlewareBlocks\Enums\ProcessingItemStateCodeEnum as MProcessingItemStateCodeEnum;

enum TransactionStateCodeEnum: string
{
    case LOCAL_UNPROCESSED = 'local_unprocessed';
    case LOCAL_EXCEPTION = 'local_exception';
    case STATE_NOT_ALLOWED = 'state_not_allowed';
    case API_REQUEST_EXCEPTION = 'api_request_exception';
    case UNEXPECTED_ERROR_CODE = 'unexpected_error_code';
    case PROCESSING = 'processing';
    case PAID = 'paid';
    case FAILED = 'failed';
    case CANCELED = 'canceled';
    case API_ERROR = 'api_error';
    case TRANSACTION_ERROR = 'transaction_error';
    case DUPLICATE_TRANSACTION = 'duplicate_transaction';
    case TRANSACTION_AMOUNT_INVALID = 'transaction_amount_invalid';
    case RECIPIENT_NAME_VALIDATION_FAILED = 'recipient_name_validation_failed';
    case NO_RECIPIENT = 'no_recipient';
    case RECIPIENT_INVALID = 'recipient_invalid';
    case RECIPIENT_UNDETECTED = 'recipient_undetected';

    /**
     * Get the ProcessingItem state based on Transaction state.
     */
    public function getProcessingItemStateCode(): MProcessingItemStateCodeEnum
    {
        return match ($this) {
            self::LOCAL_UNPROCESSED => MProcessingItemStateCodeEnum::PENDING,
            self::LOCAL_EXCEPTION => MProcessingItemStateCodeEnum::MANUAL_RECONCILIATION_REQUIRED,
            self::STATE_NOT_ALLOWED => MProcessingItemStateCodeEnum::EXCEPTION,
            self::API_REQUEST_EXCEPTION => MProcessingItemStateCodeEnum::EXCEPTION,
            self::UNEXPECTED_ERROR_CODE => MProcessingItemStateCodeEnum::EXCEPTION,
            self::PROCESSING => MProcessingItemStateCodeEnum::PROVIDER_PENDING,
            self::PAID => MProcessingItemStateCodeEnum::PROCESSED,
            self::FAILED => MProcessingItemStateCodeEnum::MANUAL_RECONCILIATION_REQUIRED,
            self::CANCELED => MProcessingItemStateCodeEnum::MANUAL_RECONCILIATION_REQUIRED,
            self::API_ERROR => MProcessingItemStateCodeEnum::PROVIDER_NOT_ACCEPTING_TRANSACTIONS,
            self::TRANSACTION_ERROR => MProcessingItemStateCodeEnum::MANUAL_RECONCILIATION_REQUIRED,
            self::DUPLICATE_TRANSACTION => MProcessingItemStateCodeEnum::EXCEPTION,
            self::TRANSACTION_AMOUNT_INVALID => MProcessingItemStateCodeEnum::TRANSACTION_AMOUNT_INVALID,
            self::RECIPIENT_NAME_VALIDATION_FAILED => MProcessingItemStateCodeEnum::RECIPIENT_NAME_VALIDATION_FAILED,
            self::NO_RECIPIENT => MProcessingItemStateCodeEnum::EXCEPTION,
            self::RECIPIENT_INVALID => MProcessingItemStateCodeEnum::RECIPIENT_BANK_ACCOUNT_INVALID,
            self::RECIPIENT_UNDETECTED => MProcessingItemStateCodeEnum::RECIPIENT_DETAILS_INVALID,
        };
    }
}
