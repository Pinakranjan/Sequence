<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    /**
     * Disable automatic management of the updated_at column while keeping created_at.
     */
    public const UPDATED_AT = null;

    protected $guarded = [];

    /**
     * The table associated with the model.
     */
    protected $table = 'master_products';

    /**
     * Product type constants.
     * 0 = OUTBOUND, 1 = INBOUND, 2 = INTERNAL
     */
    public const PRODUCT_TYPE_OUTBOUND = 0;
    public const PRODUCT_TYPE_INBOUND = 1;
    public const PRODUCT_TYPE_INTERNAL = 2;

    /**
     * Get the human-readable label for the product type.
     */
    public static function productTypeLabel(int $type): string
    {
        return match ($type) {
            self::PRODUCT_TYPE_OUTBOUND => 'OUTBOUND',
            self::PRODUCT_TYPE_INBOUND => 'INBOUND',
            self::PRODUCT_TYPE_INTERNAL => 'INTERNAL',
            default => 'UNKNOWN',
        };
    }
}
