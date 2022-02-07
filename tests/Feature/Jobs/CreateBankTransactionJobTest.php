<?php

namespace GloCurrency\FirstCityMonumentBank\Tests\Feature\Jobs;

use Money\Money;
use Money\Currency;
use Illuminate\Support\Facades\Event;
use GloCurrency\MiddlewareBlocks\Enums\TransactionTypeEnum as MTransactionTypeEnum;
use GloCurrency\MiddlewareBlocks\Enums\TransactionStateCodeEnum as MTransactionStateCodeEnum;
use GloCurrency\MiddlewareBlocks\Contracts\TransactionInterface as MTransactionInterface;
use GloCurrency\MiddlewareBlocks\Contracts\SenderInterface as MSenderInterface;
use GloCurrency\MiddlewareBlocks\Contracts\RecipientInterface as MRecipientInterface;
use GloCurrency\MiddlewareBlocks\Contracts\ProcessingItemInterface as MProcessingItemInterface;
use GloCurrency\FirstCityMonumentBank\Tests\Fixtures\BankFixture;
use GloCurrency\FirstCityMonumentBank\Tests\FeatureTestCase;
use GloCurrency\FirstCityMonumentBank\Models\Transaction;
use GloCurrency\FirstCityMonumentBank\Models\Sender;
use GloCurrency\FirstCityMonumentBank\Models\Recipient;
use GloCurrency\FirstCityMonumentBank\Models\BankCode;
use GloCurrency\FirstCityMonumentBank\Jobs\CreateBankTransactionJob;
use GloCurrency\FirstCityMonumentBank\Exceptions\CreateTransactionException;
use GloCurrency\FirstCityMonumentBank\Events\TransactionCreatedEvent;
use GloCurrency\FirstCityMonumentBank\Enums\TransactionStateCodeEnum;
use BrokeYourBike\FirstCityMonumentBank\Enums\TransactionTypeEnum;

class CreateBankTransactionJobTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Event::fake([
            TransactionCreatedEvent::class,
        ]);
    }

    /** @test */
    public function it_will_throw_without_transaction(): void
    {
        $processingItem = $this->getMockBuilder(MProcessingItemInterface::class)->getMock();
        $processingItem->method('getTransaction')->willReturn(null);

        $this->expectExceptionMessage("transaction not found");
        $this->expectException(CreateTransactionException::class);

        CreateBankTransactionJob::dispatchSync($processingItem);
    }

    /** @test */
    public function it_will_throw_if_target_transaction_already_exist(): void
    {
        $transaction = $this->getMockBuilder(MTransactionInterface::class)->getMock();
        $transaction->method('getType')->willReturn(MTransactionTypeEnum::BANK);
        $transaction->method('getStateCode')->willReturn(MTransactionStateCodeEnum::PROCESSING);

        $processingItem = $this->getMockBuilder(MProcessingItemInterface::class)->getMock();
        $processingItem->method('getTransaction')->willReturn($transaction);

        /** @var MTransactionInterface $transaction */
        $targetTransaction = Transaction::factory()->create([
            'transaction_id' => $transaction->getId(),
        ]);

        $this->expectExceptionMessage("Transaction cannot be created twice, `{$targetTransaction->id}`");
        $this->expectException(CreateTransactionException::class);

        CreateBankTransactionJob::dispatchSync($processingItem);
    }

    /** @test */
    public function it_will_throw_without_transaction_sender(): void
    {
        $transaction = $this->getMockBuilder(MTransactionInterface::class)->getMock();
        $transaction->method('getType')->willReturn(MTransactionTypeEnum::BANK);
        $transaction->method('getStateCode')->willReturn(MTransactionStateCodeEnum::PROCESSING);
        $transaction->method('getSender')->willReturn(null);

        $processingItem = $this->getMockBuilder(MProcessingItemInterface::class)->getMock();
        $processingItem->method('getTransaction')->willReturn($transaction);

        $this->expectExceptionMessage('sender not found');
        $this->expectException(CreateTransactionException::class);

        CreateBankTransactionJob::dispatchSync($processingItem);
    }

    /** @test */
    public function it_will_throw_without_transaction_recipient(): void
    {
        $sender = $this->getMockBuilder(MSenderInterface::class)->getMock();

        $transaction = $this->getMockBuilder(MTransactionInterface::class)->getMock();
        $transaction->method('getType')->willReturn(MTransactionTypeEnum::BANK);
        $transaction->method('getStateCode')->willReturn(MTransactionStateCodeEnum::PROCESSING);
        $transaction->method('getSender')->willReturn($sender);
        $transaction->method('getRecipient')->willReturn(null);

        $processingItem = $this->getMockBuilder(MProcessingItemInterface::class)->getMock();
        $processingItem->method('getTransaction')->willReturn($transaction);

        $this->expectExceptionMessage('recipient not found');
        $this->expectException(CreateTransactionException::class);

        CreateBankTransactionJob::dispatchSync($processingItem);
    }

    /** @test */
    public function it_will_throw_without_bank_code_in_transaction_recipient(): void
    {
        $sender = $this->getMockBuilder(MSenderInterface::class)->getMock();
        $recipient = $this->getMockBuilder(MRecipientInterface::class)->getMock();
        $recipient->method('getBankCode')->willReturn(null);

        $transaction = $this->getMockBuilder(MTransactionInterface::class)->getMock();
        $transaction->method('getId')->willReturn('1234');
        $transaction->method('getType')->willReturn(MTransactionTypeEnum::BANK);
        $transaction->method('getStateCode')->willReturn(MTransactionStateCodeEnum::PROCESSING);
        $transaction->method('getSender')->willReturn($sender);
        $transaction->method('getRecipient')->willReturn($recipient);

        $processingItem = $this->getMockBuilder(MProcessingItemInterface::class)->getMock();
        $processingItem->method('getTransaction')->willReturn($transaction);

        /** @var MRecipientInterface $recipient */
        $this->expectExceptionMessage("`{$recipient->getId()}` has no `bank_code`");
        $this->expectException(CreateTransactionException::class);

        CreateBankTransactionJob::dispatchSync($processingItem);
    }

    /** @test */
    public function it_will_throw_without_bank_account_in_transaction_recipient(): void
    {
        $sender = $this->getMockBuilder(MSenderInterface::class)->getMock();
        $recipient = $this->getMockBuilder(MRecipientInterface::class)->getMock();
        $recipient->method('getBankCode')->willReturn($this->faker()->word());
        $recipient->method('getBankAccount')->willReturn(null);

        $transaction = $this->getMockBuilder(MTransactionInterface::class)->getMock();
        $transaction->method('getId')->willReturn('1234');
        $transaction->method('getType')->willReturn(MTransactionTypeEnum::BANK);
        $transaction->method('getStateCode')->willReturn(MTransactionStateCodeEnum::PROCESSING);
        $transaction->method('getSender')->willReturn($sender);
        $transaction->method('getRecipient')->willReturn($recipient);

        $processingItem = $this->getMockBuilder(MProcessingItemInterface::class)->getMock();
        $processingItem->method('getTransaction')->willReturn($transaction);

        /** @var MRecipientInterface $recipient */
        $this->expectExceptionMessage("`{$recipient->getId()}` has no `bank_account`");
        $this->expectException(CreateTransactionException::class);

        CreateBankTransactionJob::dispatchSync($processingItem);
    }

    /** @test */
    public function it_will_throw_if_bank_not_found_for_transaction_recipient(): void
    {
        $sender = $this->getMockBuilder(MSenderInterface::class)->getMock();
        $recipient = $this->getMockBuilder(MRecipientInterface::class)->getMock();
        $recipient->method('getBankCode')->willReturn($this->faker()->word());
        $recipient->method('getBankAccount')->willReturn($this->faker->numerify('##########'));

        /** @var MRecipientInterface $recipient */
        $bank = BankFixture::factory()->create([
            'country_code' => $recipient->getCountryCode(),
            'code' => $recipient->getBankCode(),
        ]);
        $bank->delete();

        $transaction = $this->getMockBuilder(MTransactionInterface::class)->getMock();
        $transaction->method('getId')->willReturn('1234');
        $transaction->method('getType')->willReturn(MTransactionTypeEnum::BANK);
        $transaction->method('getStateCode')->willReturn(MTransactionStateCodeEnum::PROCESSING);
        $transaction->method('getSender')->willReturn($sender);
        $transaction->method('getRecipient')->willReturn($recipient);

        $processingItem = $this->getMockBuilder(MProcessingItemInterface::class)->getMock();
        $processingItem->method('getTransaction')->willReturn($transaction);

        $this->expectExceptionMessage("for {$bank->country_code}/{$bank->code} not found");
        $this->expectException(CreateTransactionException::class);

        CreateBankTransactionJob::dispatchSync($processingItem);
    }

    /** @test */
    public function it_will_throw_if_target_bank_not_found(): void
    {
        $sender = $this->getMockBuilder(MSenderInterface::class)->getMock();
        $recipient = $this->getMockBuilder(MRecipientInterface::class)->getMock();
        $recipient->method('getBankCode')->willReturn($this->faker()->word());
        $recipient->method('getBankAccount')->willReturn($this->faker->numerify('##########'));

        $transaction = $this->getMockBuilder(MTransactionInterface::class)->getMock();
        $transaction->method('getId')->willReturn('1234');
        $transaction->method('getType')->willReturn(MTransactionTypeEnum::BANK);
        $transaction->method('getStateCode')->willReturn(MTransactionStateCodeEnum::PROCESSING);
        $transaction->method('getSender')->willReturn($sender);
        $transaction->method('getRecipient')->willReturn($recipient);

        $processingItem = $this->getMockBuilder(MProcessingItemInterface::class)->getMock();
        $processingItem->method('getTransaction')->willReturn($transaction);

        /** @var MRecipientInterface $recipient */
        $bank = BankFixture::factory()->create([
            'country_code' => $recipient->getCountryCode(),
            'code' => $recipient->getBankCode(),
        ]);

        /** @var BankCode */
        $targetBank = BankCode::factory()->create([
            'bank_id' => $bank->id,
        ]);
        $targetBank->delete();

        $this->expectExceptionMessage("for " . $bank::class . " `{$bank->id}` not found");
        $this->expectException(CreateTransactionException::class);

        CreateBankTransactionJob::dispatchSync($processingItem);
    }

    /** @test */
    public function it_can_create_transaction(): void
    {
        $sender = $this->getMockBuilder(MSenderInterface::class)->getMock();

        $recipient = $this->getMockBuilder(MRecipientInterface::class)->getMock();
        $recipient->method('getBankCode')->willReturn($this->faker()->word());
        $recipient->method('getBankAccount')->willReturn($this->faker->numerify('##########'));

        $transaction = $this->getMockBuilder(MTransactionInterface::class)->getMock();
        $transaction->method('getId')->willReturn('1234');
        $transaction->method('getType')->willReturn(MTransactionTypeEnum::BANK);
        $transaction->method('getStateCode')->willReturn(MTransactionStateCodeEnum::PROCESSING);
        $transaction->method('getSender')->willReturn($sender);
        $transaction->method('getRecipient')->willReturn($recipient);
        $transaction->method('getOutputAmount')->willReturn(new Money('201', new Currency('NGN')));

        $processingItem = $this->getMockBuilder(MProcessingItemInterface::class)->getMock();
        $processingItem->method('getTransaction')->willReturn($transaction);

        /**
         * @var MSenderInterface $sender
         * @var MRecipientInterface $recipient
         * @var MTransactionInterface $transaction
         * @var MProcessingItemInterface $processingItem
        */

        $bank = BankFixture::factory()->create([
            'country_code' => $recipient->getCountryCode(),
            'code' => $recipient->getBankCode(),
        ]);

        $targetBank = BankCode::factory()->create([
            'bank_id' => $bank->id,
        ]);

        $this->assertNull(Transaction::first());

        CreateBankTransactionJob::dispatchSync($processingItem);

        $this->assertNotNull($targetTransaction = Transaction::first());
        $this->assertSame($transaction->getId(), $targetTransaction->transaction_id);
        $this->assertSame($processingItem->getId(), $targetTransaction->processing_item_id);
        $this->assertEquals(TransactionStateCodeEnum::LOCAL_UNPROCESSED, $targetTransaction->state_code);
        $this->assertSame(2.01, $targetTransaction->amount);
        $this->assertSame($transaction->getOutputAmount()->getCurrency()->getCode(), $targetTransaction->currency_code);
        $this->assertSame($transaction->getReferenceForHumans(), $targetTransaction->reference);
        // TODO: more accertions
    }
}
