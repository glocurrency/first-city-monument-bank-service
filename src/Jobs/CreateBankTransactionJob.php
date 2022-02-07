<?php

namespace GloCurrency\FirstCityMonumentBank\Jobs;

use Money\Formatter\DecimalMoneyFormatter;
use Illuminate\Support\Facades\App;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Bus\Queueable;
use GloCurrency\MiddlewareBlocks\Enums\TransactionTypeEnum as MTransactionTypeEnum;
use GloCurrency\MiddlewareBlocks\Enums\TransactionStateCodeEnum as MTransactionStateCodeEnum;
use GloCurrency\MiddlewareBlocks\Enums\QueueTypeEnum as MQueueTypeEnum;
use GloCurrency\MiddlewareBlocks\Enums\ProcessingItemStateCodeEnum as MProcessingItemStateCodeEnum;
use GloCurrency\MiddlewareBlocks\Contracts\ProcessingItemInterface as MProcessingItemInterface;
use GloCurrency\FirstCityMonumentBank\Models\Transaction;
use GloCurrency\FirstCityMonumentBank\Models\BankCode;
use GloCurrency\FirstCityMonumentBank\Helpers\SenderFactory;
use GloCurrency\FirstCityMonumentBank\Helpers\RecipientFactory;
use GloCurrency\FirstCityMonumentBank\FirstCityMonumentBank;
use GloCurrency\FirstCityMonumentBank\Exceptions\CreateTransactionException;
use GloCurrency\FirstCityMonumentBank\Enums\TransactionStateCodeEnum;
use BrokeYourBike\FirstCityMonumentBank\Enums\TransactionTypeEnum;

class CreateBankTransactionJob implements ShouldQueue, ShouldBeUnique, ShouldBeEncrypted
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 1;

    private MProcessingItemInterface $processingItem;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(MProcessingItemInterface $processingItem)
    {
        $this->processingItem = $processingItem;
        $this->afterCommit();
        $this->onQueue(MQueueTypeEnum::SERVICES->value);
    }

    /**
     * The unique ID of the job.
     *
     * @return string
     * @todo test
     */
    public function uniqueId()
    {
        return $this->processingItem->getId();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $transaction = $this->processingItem->getTransaction();

        if (!$transaction) {
            throw CreateTransactionException::noTransaction($this->processingItem);
        }

        if (MTransactionTypeEnum::BANK !== $transaction->getType()) {
            throw CreateTransactionException::typeNotAllowed($transaction);
        }

        if (MTransactionStateCodeEnum::PROCESSING !== $transaction->getStateCode()) {
            throw CreateTransactionException::stateNotAllowed($transaction);
        }

        /** @var Transaction|null */
        $targetTransaction = Transaction::firstWhere('transaction_id', $transaction->getId());

        if ($targetTransaction) {
            throw CreateTransactionException::duplicateTargetTransaction($targetTransaction);
        }

        $transactionSender = $transaction->getSender();

        if (!$transactionSender) {
            throw CreateTransactionException::noTransactionSender($transaction);
        }

        $transactionRecipient = $transaction->getRecipient();

        if (!$transactionRecipient) {
            throw CreateTransactionException::noTransactionRecipient($transaction);
        }

        // TODO: test
        if (!$transactionRecipient->getBankCode()) {
            throw CreateTransactionException::noBankCode($transactionRecipient);
        }

        // TODO: test
        if (!$transactionRecipient->getBankAccount()) {
            throw CreateTransactionException::noBankAccount($transactionRecipient);
        }

        $bank = (FirstCityMonumentBank::$bankModel)::firstWhere([
            'country_code' => $transactionRecipient->getCountryCode(),
            'code' => $transactionRecipient->getBankCode(),
        ]);

        // TODO: test
        if (!$bank instanceof Model) {
            throw CreateTransactionException::noBank($transactionRecipient->getCountryCode(), $transactionRecipient->getBankCode());
        }

        /** @var BankCode|null */
        $targetBankCode = BankCode::firstWhere('bank_id', $bank->getKey());

        // TODO: test
        if (!$targetBankCode) {
            throw CreateTransactionException::noTargetBankCode($bank);
        }

        $targetSender = SenderFactory::makeFrom($transactionSender);
        $targetSender->save();

        // TODO: test
        $targetRecipient = RecipientFactory::makeFrom($transactionRecipient);
        $targetRecipient->bank_code = $targetBankCode->code;
        $targetRecipient->account_number = $transactionRecipient->getBankAccount();
        $targetRecipient->save();

        /** @var DecimalMoneyFormatter $moneyFormatter */
        $moneyFormatter = App::make(DecimalMoneyFormatter::class);

        Transaction::create([
            'transaction_id' => $transaction->getId(),
            'processing_item_id' => $this->processingItem->getId(),
            'fcmb_sender_id' => $targetSender->id,
            'fcmb_recipient_id' => $targetRecipient->id,
            'amount' => $moneyFormatter->format($transaction->getOutputAmount()),
            'currency_code' => $transaction->getOutputAmount()->getCurrency()->getCode(),
            'country_code' => $transactionRecipient->getCountryCode(),
            'state_code' => TransactionStateCodeEnum::LOCAL_UNPROCESSED,
            'operation' => TransactionTypeEnum::BANK,
            'reference' => $transaction->getReferenceForHumans(),
        ]);
    }

    /**
     * Handle a job failure.
     *
     * @param  \Throwable  $exception
     * @return void
     */
    public function failed(\Throwable $exception)
    {
        report($exception);

        if ($exception instanceof CreateTransactionException) {
            $this->processingItem->updateStateCode($exception->getStateCode(), $exception->getStateCodeReason());
            return;
        }

        $this->processingItem->updateStateCode(MProcessingItemStateCodeEnum::EXCEPTION, $exception->getMessage());
    }
}
