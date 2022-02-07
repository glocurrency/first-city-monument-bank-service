<?php

namespace GloCurrency\FirstCityMonumentBank\Tests\Feature\Jobs;

use Illuminate\Support\Facades\Event;
use GloCurrency\FirstCityMonumentBank\Tests\FeatureTestCase;
use GloCurrency\FirstCityMonumentBank\Models\Transaction;
use GloCurrency\FirstCityMonumentBank\Jobs\FetchTransactionUpdateJob;
use GloCurrency\FirstCityMonumentBank\Exceptions\FetchTransactionUpdateException;
use GloCurrency\FirstCityMonumentBank\Events\TransactionCreatedEvent;
use GloCurrency\FirstCityMonumentBank\Enums\TransactionStateCodeEnum;
use BrokeYourBike\FirstCityMonumentBank\Enums\ErrorCodeEnum;
use BrokeYourBike\FirstCityMonumentBank\Client;

class FetchTransactionUpdateJobTest extends FeatureTestCase
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

    /**
     * @test
     * @dataProvider transactionStatesProvider
     */
    public function it_will_throw_if_state_not_PROCESSING(TransactionStateCodeEnum $stateCode, bool $shouldFail): void
    {
        $targetTransaction = Transaction::factory()->create([
            'state_code' => $stateCode,
        ]);

        [$httpMock, $stack] = $this->mockApiFor(Client::class);
        $httpMock->append($this->makeAuthResponse());
        $httpMock->append(new \GuzzleHttp\Psr7\Response(200, [], '{
            "code": "' . ErrorCodeEnum::IN_PROGRESS->value . '",
            "message": "",
            "transaction": {
                "reference": "' . $targetTransaction->reference . '"
            }
        }'));

        try {
            FetchTransactionUpdateJob::dispatchSync($targetTransaction);
        } catch (\Throwable $th) {
            $this->assertInstanceOf(FetchTransactionUpdateException::class, $th);
            $this->assertStringContainsString("Transaction state_code `{$targetTransaction->state_code->value}` not allowed", $th->getMessage());
            return;
        }

        if ($shouldFail) {
            $this->fail('Exception was not thrown');
        }

        $this->assertSame(0, $httpMock->count());
    }

    public function transactionStatesProvider(): array
    {
        $states = collect(TransactionStateCodeEnum::cases())
            ->filter(fn($c) => !in_array($c, [
                TransactionStateCodeEnum::PROCESSING,
            ]))
            ->flatten()
            ->map(fn($c) => [$c, true])
            ->toArray();

        $states[] = [TransactionStateCodeEnum::PROCESSING, false];

        return $states;
    }

    /** @test */
    public function it_can_update_state_code()
    {
        $targetTransaction = Transaction::factory()->create([
            'state_code' => TransactionStateCodeEnum::PROCESSING,
            'error_code' => ErrorCodeEnum::SUCCESS,
        ]);

        [$httpMock, $stack] = $this->mockApiFor(Client::class);
        $httpMock->append($this->makeAuthResponse());
        $httpMock->append(new \GuzzleHttp\Psr7\Response(200, [], '{
            "code": "' . ErrorCodeEnum::CANCELED->value . '",
            "message": "",
            "transaction": {
                "reference": "' . $targetTransaction->reference . '"
            }
        }'));

        FetchTransactionUpdateJob::dispatchSync($targetTransaction);

        $targetTransaction = $targetTransaction->fresh();
        $this->assertInstanceOf(Transaction::class, $targetTransaction);

        $this->assertEquals(TransactionStateCodeEnum::CANCELED, $targetTransaction->state_code);
        $this->assertEquals(ErrorCodeEnum::SUCCESS, $targetTransaction->error_code);
    }
}
