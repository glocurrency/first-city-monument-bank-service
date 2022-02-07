<?php

namespace GloCurrency\FirstCityMonumentBank\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use GloCurrency\FirstCityMonumentBank\Database\Factories\BankCodeFactory;
use BrokeYourBike\BaseModels\BaseUuid;

/**
 * GloCurrency\FirstCityMonumentBank\Models\BankCode
 *
 * @property string $id
 * @property string $bank_id
 * @property string $code
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class BankCode extends BaseUuid
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'fcmb_bank_codes';

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return BankCodeFactory::new();
    }
}
