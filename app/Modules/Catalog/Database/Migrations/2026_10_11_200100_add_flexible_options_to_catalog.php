<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Variantes flexibles, étape 1 (ajout uniquement, sans risque pour la production) :
 * uniquement des colonnes facultatives et des index. L'ancien code les ignore ; color_id reste en place.
 *
 * - categories.option_template : options proposées à la création d'un produit de la catégorie
 *                                (ex. Postiches → [{"name":"Couleur","values":[]},{"name":"Taille","values":[]}])
 * - products.options           : axes de variation du produit ([{"name":"Contenance","values":["25 ml","30 ml"]}])
 * - products.specs             : informations libres du produit ({"Composition":"...","Origine":"Chine"})
 * - product_colors.option_values : combinaison de la variante ({"Contenance":"25 ml"})
 * - product_colors.signature   : forme canonique de la combinaison (+ marque), pour détecter les doublons
 * - product_colors.sku         : référence générée automatiquement, unique
 * - product_colors.brand_id    : marque de la variante (table brands)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->jsonb('option_template')->nullable();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->jsonb('options')->nullable();
            $table->jsonb('specs')->nullable();
        });

        Schema::table('product_colors', function (Blueprint $table) {
            $table->jsonb('option_values')->nullable();
            $table->string('signature')->nullable();
            // Unique dès maintenant : les valeurs NULL (variantes pas encore migrées) ne sont pas en conflit
            $table->string('sku', 64)->nullable()->unique();
            $table->foreignId('brand_id')->nullable()->constrained('brands')->nullOnDelete();

            // Pas encore unique : d'éventuels doublons existants doivent d'abord être fusionnés (étape 4)
            $table->index(['product_id', 'signature']);
            $table->index('brand_id');
        });

        // Index GIN pour filtrer par option (ex. option_values @> '{"Couleur":"Noir"}') : PostgreSQL uniquement
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('CREATE INDEX product_colors_option_values_gin ON product_colors USING GIN (option_values)');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS product_colors_option_values_gin');
        }

        Schema::table('product_colors', function (Blueprint $table) {
            $table->dropIndex(['product_id', 'signature']);
            $table->dropIndex(['brand_id']);
            $table->dropConstrainedForeignId('brand_id');
            $table->dropUnique(['sku']);
            $table->dropColumn(['option_values', 'signature', 'sku']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['options', 'specs']);
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('option_template');
        });
    }
};
