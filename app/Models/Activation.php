<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Activation extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'token',
        'password',
        'world_id',
        'used',
    ];

    protected $casts = [
        'used' => 'boolean',
    ];
}
