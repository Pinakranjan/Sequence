<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;

    protected $table = 'utility_plans';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'status',
        'is_popular',
        'duration',
        'offerPrice',
        'subscriptionName',
        'subscriptionPrice',
        'features',
        'icon'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'duration' => 'integer',
        'offerPrice' => 'double',
        'subscriptionPrice' => 'double',
        'status' => 'integer',
        'is_popular' => 'integer',
        'features' => 'json',
    ];
}
