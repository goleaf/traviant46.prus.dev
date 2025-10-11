<?php

namespace App\Services\Maintenance;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class InactivePlayerCleanupService
{
    public function __construct(private readonly DatabaseManager $db)
    {
    }

    public function handle(): array
    {
        $now = now()->timestamp;
        $scheduled = 0;

        foreach ($this->thresholds() as $rule) {
            $scheduled += $this->scheduleInactivePlayers($rule, $now);
        }

        $purged = $this->purgeDuePlayers($now);

        return [
            'scheduled' => $scheduled,
            'purged' => $purged,
        ];
    }

    protected function scheduleInactivePlayers(array $rule, int $now): int
    {
        $inactiveSeconds = max(0, (int) Arr::get($rule, 'inactive_seconds', 0));
        if ($inactiveSeconds === 0) {
            return 0;
        }

        $batch = $this->batchSize();
        if ($batch <= 0) {
            return 0;
        }

        $cutoff = $now - $inactiveSeconds;
        $comparison = $this->vacationComparisonExpression();
        $query = $this->legacy()->table('users')
            ->select('id')
            ->where('id', '>', 2)
            ->where('last_login_time', '>', 0)
            ->whereRaw($comparison, [$cutoff, $cutoff, $cutoff])
            ->whereNotExists(function ($builder): void {
                $builder
                    ->selectRaw('1')
                    ->from('deleting')
                    ->whereColumn('deleting.uid', 'users.id');
            })
            ->orderBy('last_login_time')
            ->limit($batch);

        $maxPopulation = Arr::get($rule, 'max_population');
        if ($maxPopulation !== null) {
            $query->where('total_pop', '<=', (int) $maxPopulation);
        }

        /** @var Collection<int, object{ id:int }> $candidates */
        $candidates = $query->get();
        if ($candidates->isEmpty()) {
            return 0;
        }

        $deadline = $now + max(0, (int) Config::get('maintenance.inactive_players.deletion_grace_seconds', 0));
        $payload = $candidates->map(fn (object $row): array => [
            'uid' => (int) $row->id,
            'time' => $deadline,
        ])->all();

        $this->legacy()->table('deleting')->upsert($payload, ['uid'], ['time']);

        return count($payload);
    }

    protected function purgeDuePlayers(int $now): int
    {
        $batch = $this->batchSize();
        if ($batch <= 0) {
            return 0;
        }

        $uids = $this->legacy()->table('deleting')
            ->select('uid')
            ->where('time', '>', 0)
            ->where('time', '<=', $now)
            ->orderBy('time')
            ->limit($batch)
            ->pluck('uid')
            ->map(fn ($uid) => (int) $uid)
            ->values()
            ->all();

        if ($uids === []) {
            return 0;
        }

        $legacy = $this->legacy();
        $affected = 0;
        $legacy->transaction(function () use ($legacy, $uids, &$affected): void {
            $affected = $legacy->table('users')
                ->whereIn('id', $uids)
                ->update([
                    'hidden' => 1,
                    'total_pop' => 0,
                    'total_villages' => 0,
                    'vacationActiveTil' => 0,
                ]);

            if ($affected > 0) {
                $legacy->table('vdata')->whereIn('owner', $uids)->update(['owner' => 0]);
                $legacy->table('mdata')->whereIn('uid', $uids)->orWhereIn('to_uid', $uids)->delete();
                $legacy->table('ndata')->whereIn('uid', $uids)->delete();
                $legacy->table('hero')->whereIn('uid', $uids)->delete();
                $legacy->table('inventory')->whereIn('uid', $uids)->delete();
                $legacy->table('items')->whereIn('uid', $uids)->delete();
                $legacy->table('adventure')->whereIn('uid', $uids)->delete();
                $legacy->table('notes')->whereIn('uid', $uids)->orWhereIn('to_uid', $uids)->delete();
                $legacy->table('friendlist')->whereIn('uid', $uids)->orWhereIn('to_uid', $uids)->delete();
                $legacy->table('ignoreList')->whereIn('uid', $uids)->orWhereIn('ignore_id', $uids)->delete();
                $legacy->table('autoExtend')->whereIn('uid', $uids)->delete();
                $legacy->table('auction')->whereIn('uid', $uids)->delete();
                $legacy->table('bids')->whereIn('uid', $uids)->delete();
                $legacy->table('activation_progress')->whereIn('uid', $uids)->delete();
                $legacy->table('banQueue')->whereIn('uid', $uids)->delete();
                $legacy->table('banHistory')->whereIn('uid', $uids)->delete();
                $legacy->table('player_references')->whereIn('ref_uid', $uids)->orWhereIn('uid', $uids)->delete();
                $legacy->table('medal')->whereIn('uid', $uids)->delete();
                $legacy->table('newproc')->whereIn('uid', $uids)->delete();
                $legacy->table('mapflag')->whereIn('uid', $uids)->delete();
                $legacy->table('links')->whereIn('uid', $uids)->delete();
                $legacy->table('forum_open_players')->whereIn('uid', $uids)->delete();
                $legacy->table('accounting')->whereIn('uid', $uids)->delete();
                $legacy->table('ali_invite')->whereIn('uid', $uids)->delete();
                $legacy->table('alliance_bonus_upgrade_queue')->whereIn('uid', $uids)->delete();
                $legacy->table('farmlist')->whereIn('uid', $uids)->delete();
                $legacy->table('farmlist_last_reports')->whereIn('uid', $uids)->delete();

                $legacy->table('users')->whereIn('sit1Uid', $uids)->update(['sit1Uid' => 0]);
                $legacy->table('users')->whereIn('sit2Uid', $uids)->update(['sit2Uid' => 0]);
            }

            $legacy->table('deleting')->whereIn('uid', $uids)->delete();
        }, 5);

        if ($affected > 0) {
            Log::notice('maintenance.inactive_players.purged', ['count' => $affected, 'uids' => $uids]);
        }

        return $affected;
    }

    protected function thresholds(): array
    {
        return Config::get('maintenance.inactive_players.thresholds', []);
    }

    protected function batchSize(): int
    {
        return (int) Config::get('maintenance.inactive_players.batch', 25);
    }

    protected function legacy(): ConnectionInterface
    {
        $connection = Config::get('maintenance.connections.legacy', 'legacy');

        return $this->db->connection($connection);
    }

    protected function vacationComparisonExpression(): string
    {
        $driver = $this->legacy()->getDriverName();

        if ($driver === 'sqlite') {
            return 'CASE WHEN vacationActiveTil = 0 THEN last_login_time < ? ' .
                'WHEN last_login_time > vacationActiveTil THEN last_login_time < ? ' .
                'ELSE vacationActiveTil < ? END';
        }

        return 'IF(vacationActiveTil = 0, last_login_time < ?, IF(last_login_time > vacationActiveTil, last_login_time < ?, vacationActiveTil < ?))';
    }
}
