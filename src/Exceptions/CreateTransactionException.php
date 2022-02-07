<?php

namespace GloCurrency\FirstCityMonumentBank\Exceptions;

use Illuminate\Database\Eloquent\Model;
use GloCurrency\MiddlewareBlocks\Enums\ProcessingItemStateCodeEnum as MProcessingItemStateCodeEnum;
use GloCurrency\MiddlewareBlocks\Contracts\TransactionInterface as MTransactionInterface;
use GloCurrency\MiddlewareBlocks\Contracts\RecipientInterface as MRecipientInterface;
use GloCurrency\MiddlewareBlocks\Contracts\ProcessingItemInterface as MProcessingItemInterface;
use GloCurrency\FirstCityMonumentBank\Models\BankCode;
use GloCurrency\FirstCityMonumentBank\FirstCityMonumentBank;
use BrokeYourBike\HasSourceModel\SourceModelInterface;

final class CreateTransactionException extends \RuntimeException
{
    private MProcessingItemStateCodeEnum $stateCode;
    private string $stateCodeReason;

    public function __construct(MProcessingItemStateCodeEnum $stateCode, string $stateCodeReason, ?\Throwable $previous = null)
    {
        $this->stateCode = $stateCode;
        $this->stateCodeReason = $stateCodeReason;

        parent::__construct($stateCodeReason, 0, $previous);
    }

    public function getStateCode(): MProcessingItemStateCodeEnum
    {
        return $this->stateCode;
    }

    public function getStateCodeReason(): string
    {
        return $this->stateCodeReason;
    }

    public static function noTransaction(MProcessingItemInterface $processingItem): self
    {
        $className = $processingItem::class;
        $message = "{$className} `{$processingItem->getId()}` transaction not found";
        return new static(MProcessingItemStateCodeEnum::NO_TRANSACTION, $message);
    }

    public static function noTransactionSender(MTransactionInterface $transaction): self
    {
        $className = $transaction::class;
        $message = "{$className} `{$transaction->getId()}` sender not found";
        return new static(MProcessingItemStateCodeEnum::NO_TRANSACTION_SENDER, $message);
    }

    public static function noTransactionRecipient(MTransactionInterface $transaction): self
    {
        $className = $transaction::class;
        $message = "{$className} `{$transaction->getId()}` recipient not found";
        return new static(MProcessingItemStateCodeEnum::NO_TRANSACTION_RECIPIENT, $message);
    }

    public static function noBankCode(MRecipientInterface $transactionRecipient): self
    {
        $className = $transactionRecipient::class;
        $message = "{$className} `{$transactionRecipient->getId()}` has no `bank_code`";
        return new static(MProcessingItemStateCodeEnum::NO_TRANSACTION_RECIPIENT_BANK_CODE, $message);
    }

    public static function noBankAccount(MRecipientInterface $transactionRecipient): self
    {
        $className = $transactionRecipient::class;
        $message = "{$className} `{$transactionRecipient->getId()}` has no `bank_account`";
        return new static(MProcessingItemStateCodeEnum::NO_TRANSACTION_RECIPIENT_BANK_ACCOUNT, $message);
    }

    public static function noBank(string $countryCode, string $bankCode): self
    {
        $className = FirstCityMonumentBank::$bankModel;
        $message = "{$className} for {$countryCode}/{$bankCode} not found";
        return new static(MProcessingItemStateCodeEnum::NO_BANK, $message);
    }

    public static function noTargetBankCode(Model $bank): self
    {
        $sourceClassName = $bank::class;
        $targetClassName = BankCode::class;
        $message = "{$targetClassName} for {$sourceClassName} `{$bank->getKey()}` not found";
        return new static(MProcessingItemStateCodeEnum::NO_TARGET_BANK_CODE, $message);
    }

    public static function typeNotAllowed(MTransactionInterface $transaction): self
    {
        $className = $transaction::class;
        $message = "{$className} type `{$transaction->getType()->value}` not allowed";
        return new static(MProcessingItemStateCodeEnum::TRANSACTION_TYPE_NOT_ALLOWED, $message);
    }

    public static function stateNotAllowed(MTransactionInterface $transaction): self
    {
        $className = $transaction::class;
        $message = "{$className} state_code `{$transaction->getStateCode()->value}` not allowed";
        return new static(MProcessingItemStateCodeEnum::TRANSACTION_STATE_NOT_ALLOWED, $message);
    }

    public static function duplicateTargetTransaction(SourceModelInterface $transaction): self
    {
        $className = $transaction::class;
        $message = "{$className} cannot be created twice, `{$transaction->getKey()}`";
        return new static(MProcessingItemStateCodeEnum::DUPLICATE_TARGET_TRANSACTION, $message);
    }

    public static function noCollectionPassword(MTransactionInterface $transaction): self
    {
        $className = $transaction::class;
        $message = "{$className} `{$transaction->getId()}` collection_password required";
        return new static(MProcessingItemStateCodeEnum::NO_COLLECTION_PIN, $message);
    }

    public static function noPhoneNumber(MRecipientInterface $recipient): self
    {
        $className = $recipient::class;
        $message = "{$className} `{$recipient->getId()}` phone_number required";
        return new static(MProcessingItemStateCodeEnum::NO_TRANSACTION_RECIPIENT_PHONE_NUMBER, $message);
    }
}
