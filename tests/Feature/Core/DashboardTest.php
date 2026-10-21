<?php

namespace Tests\Feature\Core;

use App\Models\User;
use App\Modules\Sale\Models\Sale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Tableau de bord du back office : chiffre d'affaires de la semaine en cours (du lundi au dimanche).
 */
class DashboardTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function the_back_office_dashboard_shows_the_sales_of_the_current_week()
    {
        // Jeudi 15 octobre 2026 : la semaine va du lundi 12 au dimanche 18
        $this->travelTo(Carbon::parse('2026-10-15 10:00'));

        Sale::factory()->create(['total_net' => 100, 'created_at' => '2026-10-12 08:00']); // lundi
        Sale::factory()->create(['total_net' => 250, 'created_at' => '2026-10-15 09:30']); // aujourd'hui
        Sale::factory()->create(['total_net' => 999, 'created_at' => '2026-10-11 23:59']); // dimanche précédent

        $this->actingAs(User::factory()->backOffice()->create())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee("This week's sales")
            ->assertDontSee("Today's sales")
            ->assertViewHas('salesThisWeek', fn ($total) => (float) $total === 350.0);
    }
}
