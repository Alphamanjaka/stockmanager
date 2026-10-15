<?php

namespace App\Modules\Backup\Listeners;

use App\Modules\Backup\Models\BackupRun;
use App\Modules\Backup\Services\ArchiveBackupService;
use Spatie\Backup\Events\BackupWasSuccessful;

/**
 * Marque la sauvegarde d'archive en cours comme réussie.
 */
class MarkArchiveBackupSuccessful
{
    public function __construct(private ArchiveBackupService $archive) {}

    public function handle(BackupWasSuccessful $event): void
    {
        $run = BackupRun::where('type', BackupRun::TYPE_ARCHIVE)
            ->where('status', BackupRun::STATUS_RUNNING)
            ->latest()
            ->first();

        if (! $run) {
            return;
        }

        $latest = $this->archive->getBackups()[0] ?? null;

        $run->update([
            'status' => BackupRun::STATUS_SUCCESS,
            'target' => $latest['name'] ?? null,
            'message' => 'Archive créée avec succès.',
            'finished_at' => now(),
        ]);
    }
}
