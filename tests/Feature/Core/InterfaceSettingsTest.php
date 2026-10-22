<?php

namespace Tests\Feature\Core;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Langue et thème : réglages de l'application (Administration › Paramètres › Interface & System),
 * et non plus un choix dans la barre du haut.
 */
class InterfaceSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->backOffice()->create());
    }

    /** @test */
    public function the_saved_language_applies_to_the_whole_application()
    {
        // Les tests tournent en anglais (phpunit.xml) : le réglage passe l'application en français
        $this->put('/admin/settings/1', ['app_locale' => 'fr'])->assertRedirect('/admin/settings');

        $this->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('<html lang="fr">', false)
            ->assertSee(__('backoffice.launcher', [], 'fr'));
    }

    /** @test */
    public function a_language_that_is_not_offered_is_refused()
    {
        $this->put('/admin/settings/1', ['app_locale' => 'xx'])->assertSessionHasErrors('app_locale');
        // L'espagnol n'est plus proposé
        $this->put('/admin/settings/1', ['app_locale' => 'es'])->assertSessionHasErrors('app_locale');
    }

    /** @test */
    public function the_saved_theme_is_applied_before_the_page_is_displayed()
    {
        $this->put('/admin/settings/1', ['theme_mode' => 'dark']);

        $this->get('/admin/dashboard')->assertSee('root.dataset.themeMode = "dark"', false);
    }

    /** @test */
    public function the_top_bar_no_longer_offers_language_or_theme_switches()
    {
        $this->get('/admin/dashboard')
            ->assertDontSee('id="theme-toggle"', false)
            ->assertDontSee('/lang/', false);

        $this->get('/admin/settings')
            ->assertOk()
            ->assertSee('name="app_locale"', false)
            ->assertSee('name="theme_mode"', false);
    }
}
