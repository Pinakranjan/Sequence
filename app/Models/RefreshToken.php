<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RefreshToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'jti',
        'user_id',
        'user_device_id',
        'token_hash',
        'expires_at',
        'last_used_at',
        'revoked_at',
        'rotated_from_id',
        'last_ip',
        'last_user_agent',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'last_used_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(UserDevice::class, 'user_device_id');
    }

    public function rotatedFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'rotated_from_id');
    }
}
