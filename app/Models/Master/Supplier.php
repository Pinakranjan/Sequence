<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    /**
     * Disable automatic management of the updated_at column while keeping created_at.
     */
    public const UPDATED_AT = null;

    protected $guarded = [];

    /**
     * The table associated with the model.
     */
    protected $table = 'master_suppliers';
}
