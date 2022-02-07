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
use GloCurrency\FirstCityMonumentBank\Exceptions\FetchTransactionUpdateException;
use GloCurrency\FirstCityMonumentBank\Enums\TransactionStateCodeEnum;
use GloCurrency\FirstCityMonumentBank\Enums\ErrorCodeFactory;
use BrokeYourBike\FirstCityMonumentBank\Enums\ErrorCodeEnum;
use BrokeYourBike\FirstCityMonumentBank\Client;

class FetchTransactionUpdateJob implements ShouldQueue, ShouldBeUnique, ShouldBeEncrypted
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
        if (TransactionStateCodeEnum::PROCESSING !== $this->targetTransaction->state_code) {
            throw FetchTransactionUpdateException::stateNotAllowed($this->targetTransaction);
        }

        try {
            /** @var Client */
            $api = App::make(Client::class);
            $response = $api->fetchTransactionStatus($this->targetTransaction);
        } catch (\Throwable $e) {
            report($e);
            throw FetchTransactionUpdateException::apiRequestException($e);
        }

        // TODO: we should consider storing error_ and other codes in the separate table
        // to maintain history of statuses
        $errorCode = ErrorCodeEnum::tryFrom($response->code);

        if (!$errorCode) {
            throw FetchTransactionUpdateException::unexpectedErrorCode($response->code);
        }

        $this->targetTransaction->state_code = ErrorCodeFactory::getTransactionStateCode($errorCode);
        $this->targetTransaction->save();
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
    }
}
