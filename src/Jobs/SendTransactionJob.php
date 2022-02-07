<?php

namespace GloCurrency\FirstCityMonumentBank\Jobs;

use Illuminate\Support\Facades\App;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Bus\Queueable;
use GloCurrency\MiddlewareBlocks\Enums\QueueTypeEnum as MQueueTypeEnum;
use GloCurrency\FirstCityMonumentBank\Models\Transaction;
use GloCurrency\FirstCityMonumentBank\Models\Recipient;
use GloCurrency\FirstCityMonumentBank\Jobs\FetchTransactionUpdateJob;
use GloCurrency\FirstCityMonumentBank\Exceptions\SendTransactionException;
use GloCurrency\FirstCityMonumentBank\Enums\TransactionStateCodeEnum;
use GloCurrency\FirstCityMonumentBank\Enums\ErrorCodeFactory;
use BrokeYourBike\FirstCityMonumentBank\Enums\TransactionTypeEnum;
use BrokeYourBike\FirstCityMonumentBank\Enums\ErrorCodeEnum;
use BrokeYourBike\FirstCityMonumentBank\Client;

class SendTransactionJob implements ShouldQueue, ShouldBeUnique, ShouldBeEncrypted
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

    private Transaction $targetTransaction;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Transaction $targetTransaction)
    {
        $this->targetTransaction = $targetTransaction;
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
        return $this->targetTransaction->id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if (TransactionStateCodeEnum::LOCAL_UNPROCESSED !== $this->targetTransaction->state_code) {
            throw SendTransactionException::stateNotAllowed($this->targetTransaction);
        }

        /** @var Recipient|null */
        $fcmbRecipient = $this->targetTransaction->recipient;

        if (!$fcmbRecipient) {
            throw SendTransactionException::noRecipient($this->targetTransaction);
        }

        if (TransactionTypeEnum::BANK === $this->targetTransaction->operation) {
            if (!$this->isRecipientValid($fcmbRecipient)) {
                throw SendTransactionException::recipientInvalid($fcmbRecipient);
            }
        }

        try {
            /** @var Client */
            $api = App::make(Client::class);
            $response = $api->payoutTransaction($this->targetTransaction);
        } catch (\Throwable $e) {
            report($e);
            throw SendTransactionException::apiRequestException($e);
        }

        $errorCode = ErrorCodeEnum::tryFrom($response->code);

        // TODO: test
        if (!$errorCode) {
            throw SendTransactionException::unexpectedErrorCode($response->code);
        }

        if (ErrorCodeEnum::SUCCESS === $errorCode) {
            if (TransactionTypeEnum::CASH === $this->targetTransaction->operation) {
                $errorCode = ErrorCodeEnum::INITIATED;
            }
        }

        $this->targetTransaction->error_code = $errorCode;
        $this->targetTransaction->state_code = ErrorCodeFactory::getTransactionStateCode($errorCode);

        if (is_string($response->transaction->linkingReference)) {
            $this->targetTransaction->linking_reference = $response->transaction->linkingReference;
        }

        $this->targetTransaction->error_code_description = $response->message;
        $this->targetTransaction->save();

        if (TransactionStateCodeEnum::PROCESSING === $this->targetTransaction->state_code) {
            FetchTransactionUpdateJob::dispatch($this->targetTransaction);
        }
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

        if ($exception instanceof SendTransactionException) {
            $this->targetTransaction->update([
                'state_code' => $exception->getStateCode(),
                'state_code_reason' => $exception->getStateCodeReason(),
            ]);
            return;
        }

        $this->targetTransaction->update([
            'state_code' => TransactionStateCodeEnum::LOCAL_EXCEPTION,
            'state_code_reason' => $exception->getMessage(),
        ]);
    }

    /**
     * @return SendTransactionException|bool
     */
    public static function isRecipientValid(Recipient $fcmbRecipient)
    {
        try {
            /** @var Client */
            $api = App::make(Client::class);
            $response = $api->validateRecipient($fcmbRecipient);
        } catch (\Throwable $e) {
            report($e);
            throw SendTransactionException::recipientUndetected($fcmbRecipient);
        }

        if (ErrorCodeEnum::SUCCESS->value === $response->code) {
            return true;
        }

        return false;
    }
}
