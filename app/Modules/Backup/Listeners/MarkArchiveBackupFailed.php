<?php

namespace App\Modules\Backup\Listeners;

use App\Modules\Backup\Models\BackupRun;
use Spatie\Backup\Events\BackupHasFailed;

/**
 * Marque la sauvegarde d'archive en cours comme échouée.
 */
class MarkArchiveBackupFailed
{
    public function handle(BackupHasFailed $event): void
    {
        $run = BackupRun::where('type', BackupRun::TYPE_ARCHIVE)
            ->where('status', BackupRun::STATUS_RUNNING)
            ->latest()
            ->first();

        if (! $run) {
            return;
        }

        $run->update([
            'status' => BackupRun::STATUS_FAILED,
            'message' => $event->exception->getMessage(),
            'finished_at' => now(),
        ]);
    }
}
