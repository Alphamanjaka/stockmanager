<?php

namespace Tests\Feature\Core;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Plus de choix FIFO / CUMP : le coût moyen pondéré est la seule méthode réellement calculée.
 */
class ValuationMethodSettingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->backOffice()->create());
    }

    /** @test */
    public function the_settings_page_states_the_method_instead_of_offering_fifo()
    {
        $this->get(route('admin.settings.index'))
            ->assertOk()
            ->assertSee('Weighted average cost')
            ->assertDontSee('FIFO')
            ->assertDontSee('name="stock_valuation_method"', false);
    }

    /** @test */
    public function the_setting_is_removed_and_cannot_be_saved_again()
    {
        $this->assertDatabaseMissing('settings', ['key' => 'stock_valuation_method']);

        $this->put(route('admin.settings.update', 1), ['stock_valuation_method' => 'FIFO'])
            ->assertRedirect(route('admin.settings.index'));

        $this->assertDatabaseMissing('settings', ['key' => 'stock_valuation_method']);
    }

    /** @test */
    public function the_migration_can_be_rolled_back()
    {
        $migration = require base_path('database/migrations/2026_10_22_110000_remove_stock_valuation_method_setting.php');

        $migration->down();
        $this->assertSame('FIFO', DB::table('settings')->where('key', 'stock_valuation_method')->value('value'));

        $migration->up();
        $this->assertDatabaseMissing('settings', ['key' => 'stock_valuation_method']);
    }
}
