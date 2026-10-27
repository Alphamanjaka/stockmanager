<?php

namespace Tests\Feature\Core;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Fuseau horaire initial : Madagascar (Indian/Antananarivo) au lieu d'Europe/Paris. Un fuseau choisi par
 * l'administrateur est gardé.
 */
class TimezoneSettingTest extends TestCase
{
    use RefreshDatabase;

    private function migrate(): void
    {
        (require database_path('migrations/2026_10_27_100000_set_madagascar_timezone_setting.php'))->up();
    }

    /** @test */
    public function a_new_installation_is_on_madagascar_time()
    {
        $this->assertSame('Indian/Antananarivo', settings('timezone'));
    }

    /** @test */
    public function the_old_initial_value_is_replaced_but_a_chosen_time_zone_is_kept()
    {
        DB::table('settings')->where('key', 'timezone')->update(['value' => 'Europe/Paris']);
        $this->migrate();
        $this->assertSame('Indian/Antananarivo', DB::table('settings')->where('key', 'timezone')->value('value'));

        DB::table('settings')->where('key', 'timezone')->update(['value' => 'Africa/Nairobi']);
        $this->migrate();
        $this->assertSame('Africa/Nairobi', DB::table('settings')->where('key', 'timezone')->value('value'));
    }
}
