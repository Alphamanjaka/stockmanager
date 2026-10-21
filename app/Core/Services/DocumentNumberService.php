<?php

namespace App\Core\Services;

use Illuminate\Support\Facades\DB;

/**
 * Numéros de documents à la suite, avec un compteur par préfixe et par année :
 * FAC-2026-000001, FAC-2026-000002... puis FAC-2027-000001.
 *
 * À appeler dans la transaction qui enregistre le document. Le compteur reste verrouillé jusqu'à la fin
 * de cette transaction (deux ventes simultanées ne reçoivent jamais le même numéro), et une transaction
 * annulée rend son numéro : la suite n'a pas de trou.
 */
class DocumentNumberService
{
    public function next(string $prefix, ?\DateTimeInterface $date = null): string
    {
        $year = (int) ($date ?? now())->format('Y');

        return DB::transaction(function () use ($prefix, $year) {
            // Premier document de l'année : le compteur est créé (sans effet s'il existe déjà)
            DB::table('document_sequences')->insertOrIgnore([
                'prefix' => $prefix, 'year' => $year, 'last_number' => 0, 'created_at' => now(), 'updated_at' => now(),
            ]);

            $sequence = DB::table('document_sequences')->where(['prefix' => $prefix, 'year' => $year])->lockForUpdate()->first();
            $number = $sequence->last_number + 1;

            DB::table('document_sequences')->where('id', $sequence->id)->update(['last_number' => $number, 'updated_at' => now()]);

            return sprintf('%s-%d-%06d', $prefix, $year, $number);
        });
    }
}
