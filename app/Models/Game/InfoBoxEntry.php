<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InfoBoxEntry extends Model
{
    use HasFactory;

    protected $table = 'infobox';

    public $timestamps = false;

    protected $fillable = [
        'uid',
        'forAll',
        'type',
        'params',
        'showFrom',
        'showTo',
        'readStatus',
        'del',
    ];

    protected $casts = [
        'uid' => 'integer',
        'forAll' => 'boolean',
        'type' => 'integer',
        'showFrom' => 'integer',
        'showTo' => 'integer',
        'readStatus' => 'boolean',
        'del' => 'boolean',
    ];
}
