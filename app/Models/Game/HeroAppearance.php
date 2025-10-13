<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HeroAppearance extends Model
{
    use HasFactory;

    protected $table = 'face';

    public $timestamps = false;

    protected $fillable = [
        'uid',
        'headProfile',
        'hairColor',
        'hairStyle',
        'ears',
        'eyebrow',
        'eyes',
        'nose',
        'mouth',
        'beard',
        'gender',
        'lastupdate',
    ];

    protected $casts = [
        'uid' => 'integer',
        'headProfile' => 'integer',
        'hairColor' => 'integer',
        'hairStyle' => 'integer',
        'ears' => 'integer',
        'eyebrow' => 'integer',
        'eyes' => 'integer',
        'nose' => 'integer',
        'mouth' => 'integer',
        'beard' => 'integer',
        'lastupdate' => 'integer',
    ];
}
