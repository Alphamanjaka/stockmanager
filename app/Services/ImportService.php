<?php

namespace App\Services;

use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class ImportService
{
    public function import(UploadedFile $file, $importClass)
    {
        // On enveloppe l'import dans une transaction pour rollback en cas d'erreur
        DB::transaction(function () use ($file, $importClass) {
            Excel::import($importClass, $file);
        });

        return true;
    }
}
