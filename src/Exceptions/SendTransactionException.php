<?php

namespace GloCurrency\FirstCityMonumentBank\Exceptions;

use GloCurrency\MiddlewareBlocks\Contracts\ModelWithStateCodeInterface as MModelWithStateCodeInterface;
use GloCurrency\FirstCityMonumentBank\Enums\TransactionStateCodeEnum;
use BrokeYourBike\HasSourceModel\SourceModelInterface;
use BrokeYourBike\FirstCityMonumentBank\Interfaces\RecipientInterface;
use BrokeYourBike\FirstCityMonumentBank\Enums\ErrorCodeEnum;

final class SendTransactionException extends \RuntimeException
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

    public static function stateNotAllowed(MModelWithStateCodeInterface $transaction): self
    {
        $className = $transaction::class;
        $message = "{$className} state_code `{$transaction->getStateCode()->value}` not allowed";
        return new static(TransactionStateCodeEnum::STATE_NOT_ALLOWED, $message);
    }

    public static function noRecipient(SourceModelInterface $transaction): self
    {
        $className = RecipientInterface::class;
        $message = "{$className} not found for `{$transaction->getKey()}`";
        return new static(TransactionStateCodeEnum::NO_RECIPIENT, $message);
    }

    public static function recipientInvalid(SourceModelInterface $recipient): self
    {
        $className = $recipient::class;
        $message = "{$className} `{$recipient->getKey()}` not valid";
        return new static(TransactionStateCodeEnum::RECIPIENT_INVALID, $message);
    }

    public static function recipientUndetected(SourceModelInterface $recipient): self
    {
        $className = $recipient::class;
        $message = "Cannot validate {$className} `{$recipient->getKey()}`";
        return new static(TransactionStateCodeEnum::RECIPIENT_UNDETECTED, $message);
    }

    public static function apiRequestException(\Throwable $e): self
    {
        $message = "Exception during request with message: `{$e->getMessage()}`";
        return new static(TransactionStateCodeEnum::API_REQUEST_EXCEPTION, $message);
    }

    public static function unexpectedErrorCode(string $code): self
    {
        $className = ErrorCodeEnum::class;
        $message = "Unexpected {$className}: `{$code}`";
        return new static(TransactionStateCodeEnum::UNEXPECTED_ERROR_CODE, $message);
    }
}
