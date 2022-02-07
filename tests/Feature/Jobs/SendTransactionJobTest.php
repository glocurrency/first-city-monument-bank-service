<?php

namespace GloCurrency\FirstCityMonumentBank\Tests\Feature\Jobs;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Bus;
use GloCurrency\FirstCityMonumentBank\Tests\FeatureTestCase;
use GloCurrency\FirstCityMonumentBank\Models\Transaction;
use GloCurrency\FirstCityMonumentBank\Models\Recipient;
use GloCurrency\FirstCityMonumentBank\Jobs\SendTransactionJob;
use GloCurrency\FirstCityMonumentBank\Jobs\FetchTransactionUpdateJob;
use GloCurrency\FirstCityMonumentBank\Exceptions\SendTransactionException;
use GloCurrency\FirstCityMonumentBank\Events\TransactionCreatedEvent;
use GloCurrency\FirstCityMonumentBank\Enums\TransactionStateCodeEnum;
use BrokeYourBike\FirstCityMonumentBank\Enums\TransactionTypeEnum;
use BrokeYourBike\FirstCityMonumentBank\Enums\ErrorCodeEnum;
use BrokeYourBike\FirstCityMonumentBank\Client;

class SendTransactionJobTest extends FeatureTestCase
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

    /** @test @todo add all states */
    public function it_will_throw_if_state_not_LOCAL_UNPROCESSED(): void
    {
        $targetTransaction = Transaction::factory()->create([
            'state_code' => TransactionStateCodeEnum::PAID,
        ]);

        $this->expectExceptionMessage("Transaction state_code `{$targetTransaction->state_code->value}` not allowed");
        $this->expectException(SendTransactionException::class);

        SendTransactionJob::dispatchSync($targetTransaction);
    }

    /** @test */
    public function it_will_throw_if_no_recipient_found(): void
    {
        $targetTransaction = Transaction::factory()->create([
            'state_code' => TransactionStateCodeEnum::LOCAL_UNPROCESSED,
        ]);
        $targetTransaction->recipient->delete();

        $this->expectExceptionMessage("not found for `{$targetTransaction->id}`");
        $this->expectException(SendTransactionException::class);

        SendTransactionJob::dispatchSync($targetTransaction);
    }

    /** @test */
    public function it_will_throw_if_recipient_invalid_when_transaction_type_bank(): void
    {
        $targetTransaction = Transaction::factory()->create([
            'state_code' => TransactionStateCodeEnum::LOCAL_UNPROCESSED,
        ]);

        [$httpMock] = $this->mockApiFor(Client::class);

        $httpMock->append($this->makeAuthResponse());
        $httpMock->append(new \GuzzleHttp\Psr7\Response(200, [], '{
            "code": "S12",
            "message": "",
            "cutomername": ""
        }'));

        $this->expectExceptionMessage("`{$targetTransaction->recipient->id}` not valid");
        $this->expectException(SendTransactionException::class);

        SendTransactionJob::dispatchSync($targetTransaction);
    }

    /** @test */
    public function it_will_throw_if_recipient_cannot_be_validated_when_transaction_type_bank(): void
    {
        $targetTransaction = Transaction::factory()->create([
            'state_code' => TransactionStateCodeEnum::LOCAL_UNPROCESSED,
        ]);

        [$httpMock] = $this->mockApiFor(Client::class);

        $httpMock->append($this->makeAuthResponse());
        $httpMock->append(new \GuzzleHttp\Psr7\Response(200, [], '{}'));

        $this->expectExceptionMessage("Cannot validate " . Recipient::class . " `{$targetTransaction->recipient->id}`");
        $this->expectException(SendTransactionException::class);

        SendTransactionJob::dispatchSync($targetTransaction);
    }

    /** @test */
    public function it_will_throw_if_cannot_payout_transaction(): void
    {
        $targetTransaction = Transaction::factory()->create([
            'state_code' => TransactionStateCodeEnum::LOCAL_UNPROCESSED,
            'operation' => TransactionTypeEnum::CASH,
        ]);
        // API will throw if sende is not present
        $targetTransaction->sender->delete();

        [$httpMock] = $this->mockApiFor(Client::class);

        $httpMock->append($this->makeAuthResponse());
        $httpMock->append(new \GuzzleHttp\Psr7\Response(200, [], '{}'));

        $this->expectExceptionMessage('SenderInterface is required');
        $this->expectException(SendTransactionException::class);

        SendTransactionJob::dispatchSync($targetTransaction);
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
        $this->expectException(SendTransactionException::class);

        SendTransactionJob::dispatchSync($targetTransaction);
    }

    /** @test */
    public function it_will_update_state_if_exception_occured()
    {
        $targetTransaction = Transaction::factory()->create([
            'operation' => TransactionTypeEnum::CASH,
            'state_code' => TransactionStateCodeEnum::LOCAL_UNPROCESSED,
            'state_code_reason' => 'random-reason',
        ]);

        [$httpMock] = $this->mockApiFor(Client::class);

        $httpMock->append($this->makeAuthResponse());
        $httpMock->append(new \GuzzleHttp\Psr7\Response(200, [], '{}'));

        try {
            SendTransactionJob::dispatchSync($targetTransaction);
        } catch (\Throwable $th) {
            $this->assertEquals(TransactionStateCodeEnum::API_REQUEST_EXCEPTION, $targetTransaction->fresh()->state_code);
            $this->assertStringContainsString('Cannot assign null to property', $targetTransaction->fresh()->state_code_reason);
            $this->expectExceptionMessage('Cannot assign null to property');
            $this->expectException(SendTransactionException::class);
            throw $th;
        }

        $this->fail('Exception was not thrown');
    }

    /** @test */
    public function it_can_send_cash_transaction(): void
    {
        Bus::fake([
            FetchTransactionUpdateJob::class,
        ]);

        /** @var Transaction */
        $targetTransaction = Transaction::factory()->create([
            'state_code' => TransactionStateCodeEnum::LOCAL_UNPROCESSED,
            'state_code_reason' => null,
            'operation' => TransactionTypeEnum::CASH,
        ]);

        [$httpMock] = $this->mockApiFor(Client::class);

        $httpMock->append($this->makeAuthResponse());
        $httpMock->append(new \GuzzleHttp\Psr7\Response(200, [], '{
            "code": "' . ErrorCodeEnum::SUCCESS->value . '",
            "message": "Successful",
            "transaction": {
                "reference": "' . $targetTransaction->reference . '",
                "linkingreference": "F1234"
            }
        }'));

        SendTransactionJob::dispatchSync($targetTransaction);

        /** @var Transaction */
        $targetTransaction = $targetTransaction->fresh();

        $this->assertEquals(TransactionStateCodeEnum::PROCESSING, $targetTransaction->state_code);
        $this->assertEquals(ErrorCodeEnum::INITIATED, $targetTransaction->error_code);
        $this->assertSame('Successful', $targetTransaction->error_code_description);
        $this->assertSame('F1234', $targetTransaction->linking_reference);

        Bus::assertDispatchedTimes(FetchTransactionUpdateJob::class, 1);
    }

    /** @test */
    public function it_can_send_bank_transaction(): void
    {
        $targetTransaction = Transaction::factory()->create([
            'state_code' => TransactionStateCodeEnum::LOCAL_UNPROCESSED,
            'state_code_reason' => null,
            'operation' => TransactionTypeEnum::BANK,
        ]);

        [$httpMock] = $this->mockApiFor(Client::class);

        // recipient validation
        $httpMock->append($this->makeAuthResponse());
        $httpMock->append(new \GuzzleHttp\Psr7\Response(200, [], '{
            "code": "' . ErrorCodeEnum::SUCCESS->value . '",
            "message": "Successful",
            "cutomername": "John Doe"
        }'));

        // transaction
        $httpMock->append(new \GuzzleHttp\Psr7\Response(200, [], '{
            "code": "' . ErrorCodeEnum::SUCCESS->value . '",
            "message": "Successful",
            "transaction": {
                "reference": "' . $targetTransaction->reference . '",
                "linkingreference": "F1234"
            }
        }'));

        SendTransactionJob::dispatchSync($targetTransaction);

        $targetTransaction = $targetTransaction->fresh();

        $this->assertEquals(TransactionStateCodeEnum::PAID, $targetTransaction->state_code);
        $this->assertEquals(ErrorCodeEnum::SUCCESS, $targetTransaction->error_code);
        $this->assertSame('Successful', $targetTransaction->error_code_description);
        $this->assertSame('F1234', $targetTransaction->linking_reference);
    }

    /**
     * @test
     * @dataProvider errorCodesProvider
     * */
    public function it_will_dispatch_fetch_job_when_error_code(ErrorCodeEnum $errorCode, int $shouldBeDispatchedTimes)
    {
        Bus::fake([
            FetchTransactionUpdateJob::class,
        ]);

        $targetTransaction = Transaction::factory()->create([
            'state_code' => TransactionStateCodeEnum::LOCAL_UNPROCESSED,
            'state_code_reason' => null,
            'operation' => TransactionTypeEnum::BANK,
        ]);

        [$httpMock] = $this->mockApiFor(Client::class);

        // recipient validation
        $httpMock->append($this->makeAuthResponse());
        $httpMock->append(new \GuzzleHttp\Psr7\Response(200, [], '{
            "code": "' . ErrorCodeEnum::SUCCESS->value . '",
            "message": "Successful",
            "cutomername": "John Doe"
        }'));

        // transaction
        $httpMock->append(new \GuzzleHttp\Psr7\Response(200, [], '{
            "code": "' . $errorCode->value . '",
            "message": "Successful",
            "transaction": {
                "reference": "' . $targetTransaction->reference . '",
                "linkingreference": "F1234"
            }
        }'));

        SendTransactionJob::dispatchSync($targetTransaction);

        Bus::assertDispatchedTimes(FetchTransactionUpdateJob::class, $shouldBeDispatchedTimes);
    }

    public function errorCodesProvider(): array
    {
        $states = collect(ErrorCodeEnum::cases())
            ->filter(fn($c) => !in_array($c, [
                ErrorCodeEnum::PENDING,
                ErrorCodeEnum::INITIATED,
                ErrorCodeEnum::IN_PROGRESS,
                ErrorCodeEnum::LOCKED,
            ]))
            ->flatten()
            ->map(fn($c) => [$c, 0])
            ->toArray();

            $states[] = [ErrorCodeEnum::PENDING, 1];
            $states[] = [ErrorCodeEnum::INITIATED, 1];
            $states[] = [ErrorCodeEnum::IN_PROGRESS, 1];
            $states[] = [ErrorCodeEnum::LOCKED, 1];

        return $states;
    }
}
