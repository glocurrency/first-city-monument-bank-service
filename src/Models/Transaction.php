<?php

namespace GloCurrency\FirstCityMonumentBank\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use GloCurrency\MiddlewareBlocks\Contracts\ModelWithStateCodeInterface as MModelWithStateCodeInterface;
use GloCurrency\FirstCityMonumentBank\Models\Sender;
use GloCurrency\FirstCityMonumentBank\Models\Recipient;
use GloCurrency\FirstCityMonumentBank\FirstCityMonumentBank;
use GloCurrency\FirstCityMonumentBank\Events\TransactionUpdatedEvent;
use GloCurrency\FirstCityMonumentBank\Events\TransactionCreatedEvent;
use GloCurrency\FirstCityMonumentBank\Enums\TransactionStateCodeEnum;
use GloCurrency\FirstCityMonumentBank\Database\Factories\TransactionFactory;
use BrokeYourBike\HasSourceModel\SourceModelInterface;
use BrokeYourBike\FirstCityMonumentBank\Interfaces\TransactionInterface;
use BrokeYourBike\FirstCityMonumentBank\Interfaces\SenderInterface;
use BrokeYourBike\FirstCityMonumentBank\Interfaces\RecipientInterface;
use BrokeYourBike\FirstCityMonumentBank\Enums\TransactionTypeEnum;
use BrokeYourBike\FirstCityMonumentBank\Enums\ErrorCodeEnum;
use BrokeYourBike\CountryCasts\Alpha2Cast;
use BrokeYourBike\BaseModels\BaseUuid;

/**
 * GloCurrency\FirstCityMonumentBank\Transaction
 *
 * @property string $id
 * @property string $transaction_id
 * @property string $processing_item_id
 * @property string $fcmb_sender_id
 * @property string $fcmb_recipient_id
 * @property \GloCurrency\FirstCityMonumentBank\Enums\TransactionStateCodeEnum $state_code
 * @property string|null $state_code_reason
 * @property \BrokeYourBike\FirstCityMonumentBank\Enums\ErrorCodeEnum|null $error_code
 * @property string|null $error_code_description
 * @property \BrokeYourBike\FirstCityMonumentBank\Enums\TransactionTypeEnum $operation
 * @property string|null $linking_reference
 * @property string $reference
 * @property float $amount
 * @property string $currency_code
 * @property string $country_code
 * @property string $country_code_alpha2
 * @property string|null $reason
 * @property string|null $description
 * @property string|null $secret_question
 * @property string|null $secret_answer
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class Transaction extends BaseUuid implements MModelWithStateCodeInterface, SourceModelInterface, TransactionInterface
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'fcmb_transactions';

    /**
     * @var array<mixed>
     */
    protected $casts = [
        'state_code' => TransactionStateCodeEnum::class,
        'error_code' => ErrorCodeEnum::class,
        'operation' => TransactionTypeEnum::class,
        'country_code_alpha2' => Alpha2Cast::class . ':country_code',
        'amount' => 'double',
    ];

    /**
     * @var array<mixed>
     */
    protected $dispatchesEvents = [
        'created' => TransactionCreatedEvent::class,
        'updated' => TransactionUpdatedEvent::class,
    ];

    public function getStateCode(): TransactionStateCodeEnum
    {
        return $this->state_code;
    }

    public function getStateCodeReason(): ?string
    {
        return $this->state_code_reason;
    }

    public function getSender(): ?SenderInterface
    {
        return $this->sender;
    }

    public function getRecipient(): ?RecipientInterface
    {
        return $this->recipient;
    }

    public function getTransactionType(): TransactionTypeEnum
    {
        return $this->operation;
    }

    public function getReference(): string
    {
        return $this->reference;
    }

    public function getCountryCode(): string
    {
        return $this->country_code_alpha2;
    }

    public function getCurrencyCode(): string
    {
        return $this->currency_code;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getSecretQuestion(): ?string
    {
        return $this->secret_question;
    }

    public function getSecretAnswer(): ?string
    {
        return $this->secret_answer;
    }

    /**
     * Get Sender that the Transaction has.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function sender()
    {
        return $this->hasOne(
            Sender::class,
            'id',
            'fcmb_sender_id',
        );
    }

    /**
     * Get Recipient that the Transaction has.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function recipient()
    {
        return $this->hasOne(
            Recipient::class,
            'id',
            'fcmb_recipient_id',
        );
    }

    /**
     * The ProcessingItem that Transaction belong to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function processingItem()
    {
        return $this->belongsTo(FirstCityMonumentBank::$processingItemModel, 'processing_item_id', 'id');
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return TransactionFactory::new();
    }
}
