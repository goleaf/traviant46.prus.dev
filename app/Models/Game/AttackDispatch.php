<?php

declare(strict_types=1);

namespace App\Models\Game;

use App\Enums\AttackMissionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttackDispatch extends Model
{
    protected $table = 'attack_dispatches';

    protected $fillable = [
        'target_village_id',
        'arrives_at',
        'arrival_checksum',
        'unit_slot_one_count',
        'unit_slot_two_count',
        'unit_slot_three_count',
        'unit_slot_four_count',
        'unit_slot_five_count',
        'unit_slot_six_count',
        'unit_slot_seven_count',
        'unit_slot_eight_count',
        'unit_slot_nine_count',
        'unit_slot_ten_count',
        'includes_hero',
        'attack_type',
        'redeploy_hero',
    ];

    protected $casts = [
        'target_village_id' => 'int',
        'arrives_at' => 'datetime',
        'unit_slot_one_count' => 'int',
        'unit_slot_two_count' => 'int',
        'unit_slot_three_count' => 'int',
        'unit_slot_four_count' => 'int',
        'unit_slot_five_count' => 'int',
        'unit_slot_six_count' => 'int',
        'unit_slot_seven_count' => 'int',
        'unit_slot_eight_count' => 'int',
        'unit_slot_nine_count' => 'int',
        'unit_slot_ten_count' => 'int',
        'includes_hero' => 'bool',
        'attack_type' => AttackMissionType::class,
        'redeploy_hero' => 'bool',
    ];

    public function targetVillage(): BelongsTo
    {
        return $this->belongsTo(Village::class, 'target_village_id');
    }
}
