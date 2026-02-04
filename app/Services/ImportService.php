<?php

namespace App\Services;

use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\UploadedFile;

class ImportService
{
    public function import(UploadedFile $file, $importClass)
    {
        // On utilise Excel::import qui gère automatiquement
        // la lecture du CSV et l'insertion en base.
        Excel::import($importClass, $file);

        return true;
    }
}
