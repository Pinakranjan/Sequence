<?php

namespace App\Models\Utility;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserLoginRegister extends Model
{
    use HasFactory;

    protected $table = 'utility_user_login_register';

    // Primary key is default 'id'
    public $timestamps = false; // Table manages its own time columns

    // protected $fillable = [
    //     'company_id',
    //     'last_connected_time',
    //     'login_time',
    //     'logout_time',
    //     'session_end_time',
    //     'session_end_type',
    //     'system_name',
    //     'user_id',
    //     'session_id',
    // ];

    protected $guarded = [];

    protected $casts = [
        'company_id' => 'integer',
        'user_id' => 'integer',
        'last_connected_time' => 'datetime',
        'login_time' => 'datetime',
        'session_end_time' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function business()
    {
        return $this->belongsTo(\App\Models\Utility\Business::class, 'company_id');
    }
}
