<?php

namespace Tests\Unit\Service;

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

    public function test_it_lists_backups_sorted_by_date_descending()
    {
        // Créer deux faux fichiers de sauvegarde avec des timestamps différents
        $this->disk->put($this->backupName . '/backup-old.zip', 'old-content');
        // simuler un fichier plus ancien
        touch($this->disk->path($this->backupName . '/backup-old.zip'), time() - 3600);

        $this->disk->put($this->backupName . '/backup-new.zip', 'new-content');
        touch($this->disk->path($this->backupName . '/backup-new.zip'), time());

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

    /** @test */
    public function it_returns_error_if_backup_file_not_found_for_verification()
    {
        $result = $this->backupService->verifyBackup('non-existent-file.zip');

        $this->assertFalse($result['valid']);
        $this->assertEquals('Le fichier de sauvegarde est introuvable.', $result['message']);
    }

    /** @test */
    public function it_detects_a_corrupted_zip_archive()
    {
        $filePath = $this->backupName . '/corrupted.zip';
        // On écrit du texte invalide au lieu d'une archive zip
        $this->disk->put($filePath, 'ceci n\'est pas un fichier zip');

        $result = $this->backupService->verifyBackup($filePath);

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('n\'est pas une archive ZIP valide', $result['message']);
    }

    /** @test */
    public function it_detects_a_missing_database_dump_in_a_valid_archive()
    {
        // Créer un fichier zip valide mais sans le dossier db-dumps
        $filePath = $this->backupName . '/no-dump.zip';
        $zip = new \ZipArchive();
        $zipPath = $this->disk->path($filePath);

        if ($zip->open($zipPath, \ZipArchive::CREATE) === TRUE) {
            $zip->addFromString('test.txt', 'file content');
            $zip->close();
        }

        // S'assurer qu'on s'attend à un dump de BDD
        config(['backup.backup.source.databases' => ['mysql']]);
        $this->backupService = new BackupService(); // Ré-instancier pour prendre en compte le changement de config

        $result = $this->backupService->verifyBackup($filePath);

        $this->assertFalse($result['valid']);
        $this->assertEquals('Archive valide, mais le dump de la base de données est manquant.', $result['message']);
    }

    /** @test */
    public function it_successfully_verifies_a_valid_backup()
    {
        // Créer un fichier zip valide avec un dump de BDD
        $filePath = $this->backupName . '/valid-backup.zip';
        $zip = new \ZipArchive();
        $zipPath = $this->disk->path($filePath);

        if ($zip->open($zipPath, \ZipArchive::CREATE) === TRUE) {
            $zip->addFromString('db-dumps/dump.sql', 'SQL DUMP CONTENT');
            $zip->close();
        }

        config(['backup.backup.source.databases' => ['mysql']]);
        $this->backupService = new BackupService();

        $result = $this->backupService->verifyBackup($filePath);

        $this->assertTrue($result['valid']);
        $this->assertEquals('L\'intégrité de l\'archive a été vérifiée avec succès.', $result['message']);
    }
}
