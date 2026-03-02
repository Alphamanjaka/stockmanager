<?php

namespace Tests\Feature\Services;

use App\Services\BackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class BackupServiceTest extends TestCase
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

        $this->backupService = new BackupService();
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
        $this->disk->put($this->backupName . '/backup-test.zip', 'test-content');

        $backups = $this->backupService->getBackups();

        $this->assertCount(1, $backups);
        $this->assertEquals('backup-test.zip', $backups[0]['name']);
        $this->assertArrayHasKey('size', $backups[0]);
        $this->assertArrayHasKey('date', $backups[0]);
    }

    public function test_it_queues_a_backup_job()
    {
        // Simuler la façade Artisan
        Artisan::shouldReceive('queue')
            ->once()
            ->with('backup:run', [
                '--only-db' => true,
                '--disable-notifications' => true
            ]);

        $this->backupService->runBackup();
    }

    public function test_it_can_download_a_backup()
    {
        $filePath = $this->backupName . '/backup-to-download.zip';
        $this->disk->put($filePath, 'download-content');

        $request = new Request(['path' => $filePath]);
        $response = $this->backupService->downloadBackup($request);

        $this->assertInstanceOf(StreamedResponse::class, $response);
    }

    public function test_it_can_delete_a_backup()
    {
        $filePath = $this->backupName . '/backup-to-delete.zip';
        $this->disk->put($filePath, 'delete-content');

        // Vérifier que le fichier existe avant la suppression
        $this->disk->assertExists($filePath);

        $request = new Request(['path' => $filePath]);
        $wasDeleted = $this->backupService->deleteBackup($request);

        $this->assertTrue($wasDeleted);
        $this->disk->assertMissing($filePath);
    }
}
