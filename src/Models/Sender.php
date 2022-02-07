<?php

namespace GloCurrency\FirstCityMonumentBank\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use GloCurrency\FirstCityMonumentBank\Database\Factories\SenderFactory;
use BrokeYourBike\FirstCityMonumentBank\Interfaces\SenderInterface;
use BrokeYourBike\FirstCityMonumentBank\Enums\IdentificationTypeEnum;
use BrokeYourBike\CountryCasts\Alpha2Cast;
use BrokeYourBike\BaseModels\BaseUuid;

/**
 * GloCurrency\FirstCityMonumentBank\Sender
 *
 * @property string $id
 * @property string $name
 * @property string|null $address
 * @property string|null $mobile
 * @property string|null $country_code
 * @property string|null $country_code_alpha2
 * @property \BrokeYourBike\FirstCityMonumentBank\Enums\IdentificationTypeEnum|null $id_type
 * @property string|null $id_number
 * @property \Illuminate\Support\Carbon|null $id_expiry
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class Sender extends BaseUuid implements SenderInterface
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'fcmb_senders';

    /**
     * @var array<mixed>
     */
    protected $casts = [
        'id_type' => IdentificationTypeEnum::class,
        'id_expiry' => 'datetime',
        'country_code_alpha2' => Alpha2Cast::class . ':country_code',
    ];

    public function getName(): string
    {
        return $this->name;
    }

    public function getCountryCode(): ?string
    {
        return $this->country_code_alpha2;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->mobile;
    }

    public function getIdentificationType(): ?IdentificationTypeEnum
    {
        return $this->id_type;
    }

    public function getIdentificationNumber(): ?string
    {
        return $this->id_number;
    }

    public function getIdentificationExpiry(): ?\DateTime
    {
        return $this->id_expiry;
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return SenderFactory::new();
    }
}
