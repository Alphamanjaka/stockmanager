<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('group')->default('general'); // general, regional, stock, interface
            $table->string('type')->default('string'); // string, boolean, integer, file
            $table->timestamps();
        });

        // Seed default values
        $defaults = [
            // General
            ['key' => 'company_name', 'value' => 'Ma Boutique', 'group' => 'general', 'type' => 'string'],
            ['key' => 'company_phone', 'value' => '', 'group' => 'general', 'type' => 'string'],
            ['key' => 'company_email', 'value' => '', 'group' => 'general', 'type' => 'string'],
            ['key' => 'company_address', 'value' => '', 'group' => 'general', 'type' => 'string'],
            ['key' => 'company_siret', 'value' => '', 'group' => 'general', 'type' => 'string'],
            ['key' => 'company_vat', 'value' => '', 'group' => 'general', 'type' => 'string'],
            ['key' => 'company_logo', 'value' => null, 'group' => 'general', 'type' => 'file'],

            // Regional
            ['key' => 'currency_symbol', 'value' => '€', 'group' => 'regional', 'type' => 'string'],
            ['key' => 'currency_position', 'value' => 'after', 'group' => 'regional', 'type' => 'string'], // before, after
            ['key' => 'timezone', 'value' => 'Europe/Paris', 'group' => 'regional', 'type' => 'string'],
            ['key' => 'date_format', 'value' => 'd/m/Y', 'group' => 'regional', 'type' => 'string'],

            // Stock
            ['key' => 'global_alert_threshold', 'value' => '5', 'group' => 'stock', 'type' => 'integer'],
            ['key' => 'default_tax_rate', 'value' => '20', 'group' => 'stock', 'type' => 'integer'],
            ['key' => 'stock_valuation_method', 'value' => 'FIFO', 'group' => 'stock', 'type' => 'string'], // FIFO, CUMP

            // Interface
            ['key' => 'theme_mode', 'value' => 'light', 'group' => 'interface', 'type' => 'string'], // light, dark, auto
            ['key' => 'pagination_per_page', 'value' => '15', 'group' => 'interface', 'type' => 'integer'],
        ];

        DB::table('settings')->insert($defaults);
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
