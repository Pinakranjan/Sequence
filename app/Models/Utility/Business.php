<?php

namespace App\Models\Utility;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class Business extends Model
{
    /**
     * Disable automatic management of the updated_at column while keeping created_at.
     */
    public const UPDATED_AT = null;

    protected $guarded = [];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The table associated with the model.
     */
    protected $table = 'utility_company';

    /**
     * Computed URL for the business's image/logo.
     * Returns null when no image set.
     */
    public function getImageUrlAttribute(): ?string
    {
        $val = (string) ($this->image ?? '');
        if ($val === '') {
            return null;
        }
        if (Str::startsWith($val, ['http://', 'https://'])) {
            return $val;
        }
        $key = ltrim($val, '/');
        if ((bool) env('AWS_SIGNED_URLS', false)) {
            try {
                $disk = Storage::disk('s3');
                if (is_callable([$disk, 'temporaryUrl'])) {
                    return (string) call_user_func([$disk, 'temporaryUrl'], $key, now()->addMinutes((int) env('AWS_SIGNED_URL_TTL', 60)));
                }
            } catch (\Throwable $e) {
                // fall through
            }
        }
        $base = (string) (config('filesystems.disks.s3.url') ?? '');
        if ($base !== '') {
            return rtrim($base, '/') . '/' . $key;
        }
        return (string) url($key);
    }
}
