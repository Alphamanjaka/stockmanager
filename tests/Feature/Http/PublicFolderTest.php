<?php

namespace Tests\Feature\Http;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Le dossier public est servi par le serveur web : tout fichier PHP qui s'y trouve serait exécutable par son URL.
 * Seul index.php, le point d'entrée de Laravel, doit y être (voir aussi .gitignore et la configuration nginx).
 */
class PublicFolderTest extends TestCase
{
    /** @test */
    public function index_php_is_the_only_php_file_of_the_public_folder()
    {
        $phpFiles = collect(File::allFiles(public_path(), true))
            ->filter(fn ($file) => strtolower($file->getExtension()) === 'php')
            ->map(fn ($file) => str_replace('\\', '/', $file->getRelativePathname()))
            ->values()
            ->all();

        $this->assertSame(['index.php'], $phpFiles);
    }
}
