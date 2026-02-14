<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Option extends Model
{
    use HasFactory;

    protected $table = 'utility_options';

    protected $fillable = ['key','value','status'];

    protected $casts = [
        'status' => 'integer',
        'value' => 'json',
    ];
}
