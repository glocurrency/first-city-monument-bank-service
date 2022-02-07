<?php

namespace GloCurrency\FirstCityMonumentBank\Exceptions;

use GloCurrency\FirstCityMonumentBank\Enums\TransactionStateCodeEnum;
use BrokeYourBike\HasSourceModel\SourceModelInterface;
use BrokeYourBike\FirstCityMonumentBank\Models\CancelTransactionResponse;
use BrokeYourBike\FirstCityMonumentBank\Enums\ErrorCodeEnum;
use BrokeYourBike\FirstCityMonumentBank\Client;

final class CancelTransactionException extends \RuntimeException
{
    private TransactionStateCodeEnum $stateCode;
    private string $stateCodeReason;

    public function __construct(TransactionStateCodeEnum $stateCode, string $stateCodeReason, ?\Throwable $previous = null)
    {
        $this->stateCode = $stateCode;
        $this->stateCodeReason = $stateCodeReason;

        parent::__construct($stateCodeReason, 0, $previous);
    }

    public function getStateCode(): TransactionStateCodeEnum
    {
        return $this->stateCode;
    }

    public function getStateCodeReason(): string
    {
        return $this->stateCodeReason;
    }

    public static function apiRequestException(\Throwable $e): self
    {
        $className = Client::class;
        $message = "Exception during {$className} request with message: `{$e->getMessage()}`";
        return new static(TransactionStateCodeEnum::API_REQUEST_EXCEPTION, $message);
    }

    public static function unexpectedErrorCode(string $code): self
    {
        $className = ErrorCodeEnum::class;
        $message = "Unexpected {$className}: `{$code}`";
        return new static(TransactionStateCodeEnum::UNEXPECTED_ERROR_CODE, $message);
    }

    public static function cancelUnsuccess(SourceModelInterface $transaction, CancelTransactionResponse $response): self
    {
        $transactionClassName = $transaction::class;
        $requestClassName = $response::class;
        $message = "Cannot cancel {$transactionClassName} `{$transaction->getKey()}`";
        $message .= " {$requestClassName} body `{$response->getRawResponse()->getBody()}`";
        return new static(TransactionStateCodeEnum::FAILED, $message);
    }
}
