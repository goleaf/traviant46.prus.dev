<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VillageFieldLayout extends Model
{
    use HasFactory;

    protected $table = 'fdata';

    protected $primaryKey = 'kid';

    public $timestamps = false;

    protected $guarded = [];
}
