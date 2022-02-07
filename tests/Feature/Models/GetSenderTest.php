<?php

namespace GloCurrency\FirstCityMonumentBank\Tests\Feature\Models;

use Illuminate\Support\Facades\Event;
use GloCurrency\FirstCityMonumentBank\Tests\FeatureTestCase;
use GloCurrency\FirstCityMonumentBank\Models\Transaction;
use GloCurrency\FirstCityMonumentBank\Models\Sender;

class GetSenderTest extends FeatureTestCase
{
    /** @test */
    public function it_can_get_sender(): void
    {
        Event::fake([
            TransactionCreatedEvent::class,
        ]);

        $sender = Sender::factory()->create();

        $transaction = Transaction::factory()->create([
            'fcmb_sender_id' => $sender->id,
        ]);

        $this->assertSame($sender->id, $transaction->fresh()->sender->id);
    }
}
