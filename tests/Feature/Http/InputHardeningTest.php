<?php

namespace Tests\Feature\Http;

use App\Core\Models\Setting;
use App\Models\User;
use App\Modules\Catalog\Models\Color;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Les paramètres venant de la requête (clés de réglages, fichiers, chemins, tri, pagination)
 * ne doivent jamais être utilisés tels quels.
 */
class InputHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->backOffice()->create());
    }

    /** @test */
    public function settings_only_store_known_keys()
    {
        $this->put(route('admin.settings.update', 1), ['company_name' => 'Ma Boutique', 'is_admin' => '1'])
            ->assertRedirect(route('admin.settings.index'));

        $this->assertDatabaseHas('settings', ['key' => 'company_name', 'value' => 'Ma Boutique']);
        $this->assertDatabaseMissing('settings', ['key' => 'is_admin']);
    }

    /** @test */
    public function settings_reject_a_logo_that_is_not_an_image()
    {
        Storage::fake('public');

        $this->put(route('admin.settings.update', 1), [
            'company_logo' => UploadedFile::fake()->createWithContent('logo.php', '<?php echo 1;'),
        ])->assertSessionHasErrors('company_logo');

        $this->assertSame([], Storage::disk('public')->allFiles());
        $this->assertNull(Setting::where('key', 'company_logo')->value('value'));
    }

    /** @test */
    public function unknown_sort_columns_and_directions_are_ignored()
    {
        Color::factory()->count(2)->create();

        $this->get('/admin/products?sort=password&order=sideways')->assertOk();
        $this->get('/admin/colors?sort=name;drop&order=desc')->assertOk();
        $this->get('/admin/suppliers?sort=secret')->assertOk();
    }

    /** @test */
    public function page_size_from_the_request_is_capped()
    {
        $this->get('/admin/colors?per_page=100000')
            ->assertOk()
            ->assertViewHas('items', fn ($items) => $items->perPage() === 100);
    }
}
