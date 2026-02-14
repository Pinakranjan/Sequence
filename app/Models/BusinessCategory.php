<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessCategory extends Model
{
    use HasFactory;

    protected $table = 'utility_business_categories';

    /**
     * Disable automatic management of the updated_at column while keeping created_at.
     */
    public const UPDATED_AT = null;

    protected $guarded = [];

    /**
     * Explicitly cast date columns so they hydrate as Carbon instances
     * even though UPDATED_AT is disabled.
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'status' => 'integer',
    ];
}
