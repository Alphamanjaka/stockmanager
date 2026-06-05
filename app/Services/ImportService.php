<?php

namespace App\Services;

use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class ImportService
{
    public function import(UploadedFile $file, $importClass)
    {
        // Instancier la classe d'importation avant la transaction
        $importInstance = is_string($importClass) ? new $importClass : $importClass;

        // Récupérer le chemin réel immédiatement
        $filePath = $file->getRealPath();

        if (!$filePath) {
            throw new \InvalidArgumentException("Le fichier téléchargé n'est pas valide ou a expiré.");
        }

        // On enveloppe l'import dans une transaction pour rollback en cas d'erreur
        DB::transaction(function () use ($filePath, $importInstance) {
            Excel::import($importInstance, $filePath);
        });

        return true;
    }
}