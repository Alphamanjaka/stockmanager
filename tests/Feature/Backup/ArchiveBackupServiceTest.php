<?php

namespace Tests\Feature\Backup;

use App\Modules\Backup\Services\ArchiveBackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ArchiveBackupServiceTest extends TestCase
{
    use RefreshDatabase;

    private $backupService;

    private $disk;

    private $backupName;

    protected function setUp(): void
    {
        parent::setUp();

        // Utiliser un disque de stockage "fake" pour les tests
        Storage::fake('local');
        $this->disk = Storage::disk('local');

        // S'assurer que la configuration de test utilise ce disque
        config(['backup.backup.destination.disks' => ['local']]);
        $this->backupName = config('backup.backup.name');

        $this->backupService = new ArchiveBackupService;
    }

    public function test_it_returns_an_empty_array_when_no_backups_exist()
    {
        $backups = $this->backupService->getBackups();
        $this->assertIsArray($backups);
        $this->assertEmpty($backups);
    }

    public function test_it_can_list_existing_backups()
    {
        // Créer un faux fichier de sauvegarde
        $this->disk->put($this->backupName.'/backup-test.zip', 'test-content');

        $backups = $this->backupService->getBackups();

        $this->assertCount(1, $backups);
        $this->assertEquals('backup-test.zip', $backups[0]['name']);

        // Validation du format de taille (12 octets -> 12.00B)
        $this->assertEquals('12.00B', $backups[0]['size']);

        // Validation du format de date (d/m/Y H:i:s)
        $this->assertMatchesRegularExpression('/\d{2}\/\d{2}\/\d{4} \d{2}:\d{2}:\d{2}/', $backups[0]['date']);
    }

    public function test_it_lists_backups_sorted_by_date_descending()
    {
        // Créer deux faux fichiers de sauvegarde avec des timestamps différents
        $this->disk->put($this->backupName.'/backup-old.zip', 'old-content');
        // simuler un fichier plus ancien
        touch($this->disk->path($this->backupName.'/backup-old.zip'), time() - 3600);

        $this->disk->put($this->backupName.'/backup-new.zip', 'new-content');
        touch($this->disk->path($this->backupName.'/backup-new.zip'), time());

        $backups = $this->backupService->getBackups();

        $this->assertCount(2, $backups);
        // Le plus récent doit être le premier
        $this->assertEquals('backup-new.zip', $backups[0]['name']);
        $this->assertEquals('backup-old.zip', $backups[1]['name']);
    }

    public function test_it_queues_a_backup_job()
    {
        // Simuler la façade Artisan
        Artisan::shouldReceive('queue')
            ->once()
            ->with('backup:run');

        $this->backupService->runBackup();
    }
}
