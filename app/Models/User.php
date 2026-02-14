<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $guarded = [];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'pin',
        'remember_token',
    ];

    /**
     * Explicitly cast date columns so they hydrate as Carbon instances
     * even though UPDATED_AT is disabled. This ensures calls like
     * ->toDateTimeString() work on both created_at and updated_at.
     */
    // Cast rating to integer for safe serialization and comparisons
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'pin_enabled' => 'boolean',
        ];
    }

    /**
     * Computed URL for the user's profile photo.
     * - If `photo` is an absolute URL, return as-is
     * - If `photo` is set, build the S3 URL for key `upload/user_images/{photo}`
     * - Otherwise return the local fallback image
     */
    public function getPhotoUrlAttribute(): string
    {
        $val = (string) ($this->photo ?? '');
        if ($val && Str::startsWith($val, ['http://', 'https://'])) {
            return $val;
        }
        if ($val !== '') {
            $key = 'upload/user_images/' . ltrim($val, '/');
            // Optional signed URLs for private buckets (take precedence when enabled)
            $useSigned = (bool) env('AWS_SIGNED_URLS', false);
            if ($useSigned) {
                try {
                    $disk = Storage::disk('s3');
                    if (is_callable([$disk, 'temporaryUrl'])) {
                        return (string) call_user_func([$disk, 'temporaryUrl'], $key, now()->addMinutes((int) env('AWS_SIGNED_URL_TTL', 60)));
                    }
                } catch (\Throwable $e) {
                    // fall through to default construction
                }
            }
            $base = (string) (config('filesystems.disks.s3.url') ?? '');
            if ($base !== '') {
                return rtrim($base, '/') . '/' . ltrim($key, '/');
            }
            // Final fallback to public path (dev only)
            return (string) url($key);
        }
        return url('upload/no_image.jpg');
    }
}
