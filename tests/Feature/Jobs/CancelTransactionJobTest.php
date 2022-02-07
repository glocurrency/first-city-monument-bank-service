<?php

namespace GloCurrency\FirstCityMonumentBank\Tests\Feature\Jobs;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Bus;
use GloCurrency\FirstCityMonumentBank\Tests\FeatureTestCase;
use GloCurrency\FirstCityMonumentBank\Models\Transaction;
use GloCurrency\FirstCityMonumentBank\Jobs\FetchTransactionUpdateJob;
use GloCurrency\FirstCityMonumentBank\Jobs\CancelTransactionJob;
use GloCurrency\FirstCityMonumentBank\Exceptions\CancelTransactionException;
use GloCurrency\FirstCityMonumentBank\Events\TransactionCreatedEvent;
use GloCurrency\FirstCityMonumentBank\Enums\TransactionStateCodeEnum;
use BrokeYourBike\FirstCityMonumentBank\Enums\TransactionTypeEnum;
use BrokeYourBike\FirstCityMonumentBank\Enums\ErrorCodeEnum;
use BrokeYourBike\FirstCityMonumentBank\Client;

class CancelTransactionJobTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Event::fake([
            TransactionCreatedEvent::class,
        ]);
    }

    private function makeAuthResponse(): \GuzzleHttp\Psr7\Response
    {
        return new \GuzzleHttp\Psr7\Response(200, [], '{
            "access_token": "123456789",
            "expires_in": 86400
        }');
    }

    /** @test */
    public function it_will_throw_if_result_code_is_unexpected(): void
    {
        $targetTransaction = Transaction::factory()->create([
            'state_code' => TransactionStateCodeEnum::LOCAL_UNPROCESSED,
            'operation' => TransactionTypeEnum::CASH,
        ]);

        [$httpMock] = $this->mockApiFor(Client::class);

        $httpMock->append($this->makeAuthResponse());
        $httpMock->append(new \GuzzleHttp\Psr7\Response(200, [], '{
            "code": "lol-code",
            "message": "some message from api",
            "transaction": {
                "reference": "' . $targetTransaction->reference . '"
            }
        }'));

        $this->expectExceptionMessage('Unexpected ' . ErrorCodeEnum::class . ': `lol-code`');
        $this->expectException(CancelTransactionException::class);

        CancelTransactionJob::dispatchSync($targetTransaction);
    }

    /**
     * @test
     * @dataProvider errorCodesProvider
     * */
    public function it_will_throw_if_result_code(ErrorCodeEnum $errorCode, bool $shouldThrow): void
    {
        Bus::fake([FetchTransactionUpdateJob::class]);

        $targetTransaction = Transaction::factory()->create([
            'state_code' => TransactionStateCodeEnum::PROCESSING,
            'operation' => TransactionTypeEnum::CASH,
        ]);

        [$httpMock] = $this->mockApiFor(Client::class);

        $httpMock->append($this->makeAuthResponse());
        $httpMock->append(new \GuzzleHttp\Psr7\Response(200, [], '{
            "code": "' . $errorCode->value . '",
            "message": "some message from api",
            "transaction": {
                "reference": "' . $targetTransaction->reference . '"
            }
        }'));

        if ($shouldThrow) {
            $this->expectExceptionMessage('Cannot cancel ' . $targetTransaction::class . " `{$targetTransaction->id}`");
            $this->expectException(CancelTransactionException::class);
        }

        CancelTransactionJob::dispatchSync($targetTransaction);

        Bus::assertDispatched(FetchTransactionUpdateJob::class);
    }

    public function errorCodesProvider(): array
    {
        $states = collect(ErrorCodeEnum::cases())
            ->filter(fn($c) => !in_array($c, [
                ErrorCodeEnum::SUCCESS,
            ]))
            ->flatten()
            ->map(fn($c) => [$c, true])
            ->toArray();

        $states[] = [ErrorCodeEnum::SUCCESS, false];

        return $states;
    }

    /** @test */
    public function it_can_cancel_transaction(): void
    {
        Bus::fake([FetchTransactionUpdateJob::class]);

        $targetTransaction = Transaction::factory()->create([
            'state_code' => TransactionStateCodeEnum::PROCESSING,
            'operation' => TransactionTypeEnum::CASH,
        ]);

        [$httpMock] = $this->mockApiFor(Client::class);

        $httpMock->append($this->makeAuthResponse());
        $httpMock->append(new \GuzzleHttp\Psr7\Response(200, [], '{
            "code": "' . ErrorCodeEnum::SUCCESS->value . '",
            "message": "some message from api",
            "transaction": {
                "reference": "' . $targetTransaction->reference . '"
            }
        }'));

        CancelTransactionJob::dispatchSync($targetTransaction);

        Bus::assertDispatched(FetchTransactionUpdateJob::class);
    }
}
