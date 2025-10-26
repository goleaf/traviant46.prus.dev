<?php

namespace Tests\Unit;

use App\Models\Game\Movement;
use App\Services\Game\MovementService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MovementServiceTest extends TestCase
{
    protected string $legacyDatabase = '';

    protected function setUp(): void
    {
        parent::setUp();

        $this->configureLegacyConnection();
        $this->migrateLegacyTables();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        if ($this->legacyDatabase !== '' && file_exists($this->legacyDatabase)) {
            unlink($this->legacyDatabase);
        }
    }

    public function testAddMovementPersistsPayload(): void
    {
        $service = new MovementService();

        $id = $service->addMovement(
            kid: 101,
            toKid: 202,
            race: 1,
            units: [1 => 10, 2 => 5, 11 => 1],
            ctar1: 3,
            ctar2: 0,
            spyType: 2,
            redeployHero: true,
            mode: MovementService::SORTTYPE_GOING,
            attackType: MovementService::ATTACKTYPE_NORMAL,
            startTime: 1_700_000_000,
            endTime: 1_700_000_900,
            data: 'payload'
        );

        $this->assertGreaterThan(0, $id);

        $movement = Movement::query()->find($id);
        $this->assertNotNull($movement);
        $this->assertSame(101, $movement->kid);
        $this->assertSame(202, $movement->to_kid);
        $this->assertSame(10, $movement->u1);
        $this->assertSame(5, $movement->u2);
        $this->assertSame(0, $movement->u3);
        $this->assertSame(1, $movement->u11);
        $this->assertSame(3, $movement->ctar1);
        $this->assertTrue((bool) $movement->redeployHero);
        $this->assertSame('payload', $movement->data);
    }

    public function testModifyMovementAcceptsLegacyAssignmentSyntax(): void
    {
        $movement = Movement::query()->create([
            'kid' => 300,
            'to_kid' => 400,
            'race' => 2,
            'ctar1' => 0,
            'ctar2' => 0,
            'spyType' => 0,
            'redeployHero' => false,
            'mode' => MovementService::SORTTYPE_GOING,
            'attack_type' => MovementService::ATTACKTYPE_RAID,
            'start_time' => 1_700_000_000,
            'end_time' => 1_700_000_500,
            'data' => '',
            'markState' => 0,
            'proc' => 0,
        ] + $this->emptyUnits());

        $service = new MovementService();

        $updated = $service->modifyMovement($movement->id, [
            'end_time=1700000900',
            "data='updated'",
            'markState=1',
        ]);

        $this->assertTrue($updated);

        $movement->refresh();
        $this->assertSame(1_700_000_900, $movement->end_time);
        $this->assertSame('updated', $movement->data);
        $this->assertSame(1, $movement->markState);

        $service->modifyMovement($movement->id, ['redeployHero' => true]);
        $movement->refresh();
        $this->assertTrue((bool) $movement->redeployHero);
    }

    public function testSetMovementMarkStateRequiresOutgoingAttack(): void
    {
        $eligible = Movement::query()->create([
            'kid' => 1,
            'to_kid' => 2,
            'race' => 1,
            'ctar1' => 0,
            'ctar2' => 0,
            'spyType' => 0,
            'redeployHero' => false,
            'mode' => MovementService::SORTTYPE_GOING,
            'attack_type' => MovementService::ATTACKTYPE_NORMAL,
            'start_time' => 1,
            'end_time' => 2,
            'data' => '',
            'markState' => 0,
            'proc' => 0,
        ] + $this->emptyUnits());

        $ineligible = Movement::query()->create([
            'kid' => 3,
            'to_kid' => 2,
            'race' => 1,
            'ctar1' => 0,
            'ctar2' => 0,
            'spyType' => 0,
            'redeployHero' => false,
            'mode' => MovementService::SORTTYPE_RETURN,
            'attack_type' => MovementService::ATTACKTYPE_RAID,
            'start_time' => 1,
            'end_time' => 2,
            'data' => '',
            'markState' => 0,
            'proc' => 0,
        ] + $this->emptyUnits());

        $service = new MovementService();

        $this->assertTrue($service->setMovementMarkState(2, $eligible->id, 1));
        $eligible->refresh();
        $this->assertSame(1, $eligible->markState);

        $this->assertFalse($service->setMovementMarkState(2, $ineligible->id, 1));
        $ineligible->refresh();
        $this->assertSame(0, $ineligible->markState);
    }

    public function testEnforcementAndTrappedHelpers(): void
    {
        $service = new MovementService();

        $enforceResult = $service->addEnforce(10, 11, 12, 1, [1 => 50, 4 => 10]);
        $this->assertTrue($enforceResult);

        $enforceId = $service->isSameVillageReinforcementExists(11, 12);
        $this->assertNotNull($enforceId);
        $this->assertGreaterThan(0, $enforceId);

        $service->addTrapped(20, 30, 2, [1 => 5, 2 => 3]);
        $trappedId = $service->isSameVillageTrappedExists(20, 30);
        $this->assertNotNull($trappedId);

        $this->assertTrue($service->deleteEnforce($enforceId));
        $this->assertTrue($service->deleteTrapped($trappedId));
    }

    public function testDeleteMovementRemovesRow(): void
    {
        $movement = Movement::query()->create([
            'kid' => 55,
            'to_kid' => 66,
            'race' => 1,
            'ctar1' => 0,
            'ctar2' => 0,
            'spyType' => 0,
            'redeployHero' => false,
            'mode' => MovementService::SORTTYPE_GOING,
            'attack_type' => MovementService::ATTACKTYPE_SPY,
            'start_time' => 10,
            'end_time' => 20,
            'data' => '',
            'markState' => 0,
            'proc' => 0,
        ] + $this->emptyUnits());

        $service = new MovementService();
        $this->assertTrue($service->deleteMovement($movement->id));
        $this->assertNull(Movement::query()->find($movement->id));
    }

    public function testAddMovementNormalizesDateTimeTimestamps(): void
    {
        $service = new MovementService();

        $start = Carbon::createFromTimestamp(1_700_100_000);
        $end = $start->copy()->addMinutes(15);

        $id = $service->addMovement(
            kid: 42,
            toKid: 77,
            race: 1,
            units: [1 => 5],
            ctar1: 0,
            ctar2: 0,
            spyType: 0,
            redeployHero: false,
            mode: MovementService::SORTTYPE_GOING,
            attackType: MovementService::ATTACKTYPE_RAID,
            startTime: $start,
            endTime: $end,
            data: null,
        );

        $movement = Movement::query()->find($id);

        $this->assertNotNull($movement);
        $this->assertSame($start->timestamp, $movement->start_time);
        $this->assertSame($end->timestamp, $movement->end_time);
    }

    /**
     * @return array<string, int>
     */
    protected function emptyUnits(): array
    {
        return [
            'u1' => 0,
            'u2' => 0,
            'u3' => 0,
            'u4' => 0,
            'u5' => 0,
            'u6' => 0,
            'u7' => 0,
            'u8' => 0,
            'u9' => 0,
            'u10' => 0,
            'u11' => 0,
        ];
    }

    protected function configureLegacyConnection(): void
    {
        $path = database_path('testing-legacy.sqlite');
        if (file_exists($path)) {
            unlink($path);
        }
        touch($path);

        $this->legacyDatabase = $path;

        Config::set('database.connections.legacy', [
            'driver' => 'sqlite',
            'database' => $path,
            'prefix' => '',
            'foreign_key_constraints' => false,
        ]);
    }

    protected function migrateLegacyTables(): void
    {
        Schema::connection('legacy')->create('movement', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('kid');
            $table->unsignedInteger('to_kid');
            $table->unsignedTinyInteger('race');
            for ($i = 1; $i <= 10; $i++) {
                $table->unsignedBigInteger('u' . $i)->default(0);
            }
            $table->unsignedInteger('u11')->default(0);
            $table->unsignedTinyInteger('ctar1')->default(0);
            $table->unsignedTinyInteger('ctar2')->default(0);
            $table->unsignedTinyInteger('spyType')->default(0);
            $table->unsignedTinyInteger('redeployHero')->default(0);
            $table->unsignedTinyInteger('mode');
            $table->unsignedTinyInteger('attack_type');
            $table->unsignedBigInteger('start_time');
            $table->unsignedBigInteger('end_time');
            $table->string('data')->default('');
            $table->unsignedTinyInteger('markState')->default(0);
            $table->unsignedTinyInteger('proc')->default(0);
        });

        Schema::connection('legacy')->create('enforcement', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('uid');
            $table->unsignedInteger('kid');
            $table->unsignedInteger('to_kid');
            $table->unsignedTinyInteger('race');
            for ($i = 1; $i <= 10; $i++) {
                $table->unsignedBigInteger('u' . $i)->default(0);
            }
            $table->unsignedInteger('u11')->default(0);
        });

        Schema::connection('legacy')->create('trapped', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('kid');
            $table->unsignedInteger('to_kid');
            $table->unsignedTinyInteger('race');
            for ($i = 1; $i <= 10; $i++) {
                $table->unsignedBigInteger('u' . $i)->default(0);
            }
            $table->unsignedInteger('u11')->default(0);
        });
    }
}
