<?php

namespace GloCurrency\FirstCityMonumentBank\Tests\Feature\Models;

use Illuminate\Support\Facades\Event;
use GloCurrency\FirstCityMonumentBank\Tests\FeatureTestCase;
use GloCurrency\FirstCityMonumentBank\Models\Transaction;
use GloCurrency\FirstCityMonumentBank\Models\Recipient;

class GetRecipientTest extends FeatureTestCase
{
    /** @test */
    public function it_can_get_recipient(): void
    {
        Event::fake([
            TransactionCreatedEvent::class,
        ]);

        $recipient = Recipient::factory()->create();

        $transaction = Transaction::factory()->create([
            'fcmb_recipient_id' => $recipient->id,
        ]);

        $this->assertSame($recipient->id, $transaction->fresh()->recipient->id);
    }
}
