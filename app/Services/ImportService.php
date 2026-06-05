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

        // On enveloppe l'import dans une transaction pour rollback en cas d'erreur
        DB::transaction(function () use ($file, $importInstance) {
            Excel::import($importInstance, $file);
        });

        return true;
    }
}