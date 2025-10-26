<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Message;
use App\Models\MessageRecipient;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\LazyCollection;
use Throwable;

class ImportLegacyMessagesCommand extends Command
{
    protected $signature = 'travian:import-messages
        {--chunk=500 : Number of legacy messages to process per batch}
        {--dry-run : Simulate the import without writing to the database}';

    protected $description = 'Import legacy Travian messages with per-recipient state reconciliation.';

    public function handle(): int
    {
        $chunkSize = max(1, (int) $this->option('chunk'));
        $dryRun = (bool) $this->option('dry-run');

        $this->components->info(sprintf(
            'Starting legacy message import (chunk size: %d, dry-run: %s)',
            $chunkSize,
            $dryRun ? 'yes' : 'no',
        ));

        $query = DB::connection('legacy')
            ->table('mdata')
            ->orderBy('id');

        $processed = 0;
        $created = 0;
        $updated = 0;

        try {
            $query->lazy()->chunk($chunkSize)->each(function (LazyCollection $chunk) use ($dryRun, &$processed, &$created, &$updated): void {
                $chunk->each(function (object $row) use ($dryRun, &$processed, &$created, &$updated): void {
                    $processed++;

                    $senderId = $this->resolveUserId($row->uid ?? null);
                    $recipientId = $this->resolveUserId($row->to_uid ?? null);
                    $sentAt = $this->resolveTimestamp($row->time ?? null);

                    $deliveryScope = ((int) ($row->isAlliance ?? 0)) === 1 ? 'alliance' : 'individual';
                    $isBroadcast = $deliveryScope === 'alliance' || (int) ($row->to_uid ?? 0) === 0;
                    $autoType = (int) ($row->autoType ?? 0);
                    $isSystemGenerated = $autoType > 0 || (int) ($row->uid ?? 0) === 0;

                    $attributes = [
                        'sender_id' => $senderId,
                        'alliance_id' => property_exists($row, 'aid') ? $row->aid : null,
                        'subject' => $this->truncate((string) ($row->topic ?? $row->subj ?? $row->subject ?? '')),
                        'body' => (string) ($row->text ?? $row->message ?? ''),
                        'message_type' => $this->resolveMessageType($autoType, $isSystemGenerated),
                        'delivery_scope' => $deliveryScope,
                        'is_system_generated' => $isSystemGenerated,
                        'is_broadcast' => $isBroadcast,
                        'checksum' => $row->md5_checksum ?? null,
                        'sent_at' => $sentAt,
                        'delivered_at' => $sentAt,
                        'metadata' => [
                            'auto_type' => $autoType,
                            'legacy_flags' => [
                                'delete_sender' => (bool) ($row->delete_sender ?? false),
                                'delete_receiver' => (bool) ($row->delete_receiver ?? false),
                                'archived' => (bool) ($row->archived ?? false),
                                'reported' => (bool) ($row->reported ?? false),
                            ],
                        ],
                    ];

                    if ($dryRun) {
                        return;
                    }

                    /** @var Message $message */
                    $message = Message::query()->updateOrCreate(
                        ['legacy_message_id' => (int) $row->id],
                        $attributes,
                    );

                    if ($recipientId !== null) {
                        [$status, $deletedAt, $archivedAt, $readAt, $reportedAt] = $this->deriveRecipientState($row);

                        MessageRecipient::query()->updateOrCreate(
                            [
                                'message_id' => $message->getKey(),
                                'recipient_id' => $recipientId,
                            ],
                            [
                                'recipient_alliance_id' => property_exists($row, 'to_aid') ? $row->to_aid : null,
                                'status' => $status,
                                'is_archived' => $archivedAt !== null,
                                'is_muted' => false,
                                'is_reported' => $reportedAt !== null,
                                'read_at' => $readAt,
                                'archived_at' => $archivedAt,
                                'deleted_at' => $deletedAt,
                                'reported_at' => $reportedAt,
                                'flags' => [
                                    'auto_type' => $autoType,
                                    'delete_sender' => (bool) ($row->delete_sender ?? false),
                                    'delete_receiver' => (bool) ($row->delete_receiver ?? false),
                                ],
                            ],
                        );
                    }

                    $message->update([
                        'delivered_at' => $message->delivered_at ?? $message->sent_at,
                    ]);

                    $created += (int) $message->wasRecentlyCreated;
                    $updated += $message->wasRecentlyCreated ? 0 : 1;
                });

                $this->components->twoColumnDetail(
                    sprintf('Processed %d legacy messages', $processed),
                    sprintf('created %d · updated %d', $created, $updated),
                );
            });
        } catch (Throwable $exception) {
            Log::error('legacy.import.messages.failed', [
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            $this->components->error('Message import halted: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->components->success(sprintf(
            'Message import complete (%d processed, %d created, %d updated).',
            $processed,
            $created,
            $updated,
        ));

        $this->summariseInboxParity();

        return self::SUCCESS;
    }

    private function resolveUserId(mixed $legacyId): ?int
    {
        if ($legacyId === null) {
            return null;
        }

        return User::query()
            ->where('legacy_uid', $legacyId)
            ->value('id');
    }

    private function resolveTimestamp(mixed $value): ?Carbon
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof Carbon) {
            return $value;
        }

        if (is_numeric($value)) {
            return Carbon::createFromTimestamp((int) $value);
        }

        if (is_string($value)) {
            try {
                return Carbon::parse($value);
            } catch (Throwable) {
                return null;
            }
        }

        return null;
    }

    private function resolveMessageType(int $autoType, bool $isSystemGenerated): string
    {
        return match (true) {
            $isSystemGenerated && $autoType === 11 => 'support-ticket',
            $isSystemGenerated && $autoType === 5 => 'raid-report',
            $isSystemGenerated => 'system',
            default => 'player',
        };
    }

    /**
     * @return array{0: string, 1: \Illuminate\Support\Carbon|null, 2: \Illuminate\Support\Carbon|null, 3: \Illuminate\Support\Carbon|null, 4: \Illuminate\Support\Carbon|null}
     */
    private function deriveRecipientState(object $row): array
    {
        $readAt = ((int) ($row->viewed ?? 0)) === 1 ? $this->resolveTimestamp($row->time ?? null) : null;
        $archivedAt = ((int) ($row->archived ?? 0)) === 1 ? Carbon::now() : null;
        $deletedAt = ((int) ($row->delete_receiver ?? 0)) === 1 ? Carbon::now() : null;
        $reportedAt = ((int) ($row->reported ?? 0)) === 1 ? Carbon::now() : null;

        $status = 'unread';

        if ($readAt !== null) {
            $status = 'read';
        }

        if ($archivedAt !== null) {
            $status = 'archived';
        }

        if ($deletedAt !== null) {
            $status = 'deleted';
        }

        return [$status, $deletedAt, $archivedAt, $readAt, $reportedAt];
    }

    private function summariseInboxParity(): void
    {
        $mismatchCount = MessageRecipient::query()
            ->where('status', 'deleted')
            ->whereNull('deleted_at')
            ->count();

        if ($mismatchCount > 0) {
            $this->components->warn(sprintf(
                'Detected %d recipient records flagged as deleted without timestamps. Consider rerunning reconciliation.',
                $mismatchCount,
            ));
        } else {
            $this->components->info('Recipient state reconciliation completed without anomalies.');
        }
    }

    private function truncate(string $subject, int $limit = 120): string
    {
        return mb_strlen($subject) > $limit
            ? mb_substr($subject, 0, $limit - 3).'...'
            : $subject;
    }
}
