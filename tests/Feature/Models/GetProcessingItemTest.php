<?php

namespace GloCurrency\FirstCityMonumentBank\Tests\Feature\Models;

use Illuminate\Support\Facades\Event;
use GloCurrency\FirstCityMonumentBank\Tests\Fixtures\ProcessingItemFixture;
use GloCurrency\FirstCityMonumentBank\Tests\FeatureTestCase;
use GloCurrency\FirstCityMonumentBank\Models\Transaction;
use GloCurrency\FirstCityMonumentBank\Events\TransactionCreatedEvent;

class GetProcessingItemTest extends FeatureTestCase
{
    /** @test */
    public function it_can_get_processing_item(): void
    {
        Event::fake([
            TransactionCreatedEvent::class,
        ]);

        $processingItem = ProcessingItemFixture::factory()->create();

        $fcmbTransaction = Transaction::factory()->create([
            'processing_item_id' => $processingItem->id,
        ]);

        $this->assertSame($processingItem->id, $fcmbTransaction->fresh()->processingItem->id);
    }
}
