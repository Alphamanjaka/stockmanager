<?php

use App\Modules\Catalog\Support\Barcode;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_colors', function (Blueprint $table) {
            // Code scanné en caisse et imprimé sur les étiquettes : celui du fabricant, ou un EAN-13 interne
            $table->string('barcode', 32)->nullable()->unique()->after('sku');
        });

        // Les variantes existantes reçoivent leur code interne (aucun code fabricant n'est encore connu)
        DB::table('product_colors')->orderBy('id')->select('id')->chunkById(500, function ($variants) {
            foreach ($variants as $variant) {
                DB::table('product_colors')->where('id', $variant->id)->update(['barcode' => Barcode::internal($variant->id)]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('product_colors', function (Blueprint $table) {
            $table->dropUnique(['barcode']);
            $table->dropColumn('barcode');
        });
    }
};
