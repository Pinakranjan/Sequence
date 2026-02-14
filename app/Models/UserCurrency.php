<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Utility\Business;

class UserCurrency extends Model
{
    use HasFactory;

    protected $table = 'utility_user_currencies';

    protected $fillable = ['company_id', 'currency_id', 'name', 'country_name', 'code', 'rate', 'symbol', 'position'];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'company_id' => 'integer',
        'currency_id' => 'integer',
        'rate' => 'double',
    ];
}
