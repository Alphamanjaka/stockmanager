<?php

namespace Tests\Feature\Core;

use App\Core\Exports\Column;
use App\Models\User;
use App\Modules\Catalog\Exports\ProductsExport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Lang;
use Tests\TestCase;

/**
 * Interface en français. Les vues sont écrites en anglais : le texte anglais est la clé de traduction (__('New brand')),
 * traduite dans un fichier JSON : Resources/lang/fr.json du module qui l'utilise, ou lang/fr.json pour les textes
 * partagés (layouts, composants, mots communs à plusieurs modules). Une clé sans traduction s'affiche en anglais.
 */
class TranslationsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function every_text_of_the_interface_has_a_french_translation()
    {
        $json = app('translator')->getLoader()->load('fr', '*', '*');
        $keys = $this->keysInUse();
        $missing = [];

        // Garde-fou : la recherche trouve bien les appels (vue de module, composant, PHP, pagination de Laravel)
        foreach (['New brand', 'Clear selection', 'Week of :date', 'Showing', 'pos.title'] as $known) {
            $this->assertArrayHasKey($known, $keys);
        }

        foreach ($keys as $key => $files) {
            $translated = $this->isGroupKey($key) ? Lang::hasForLocale($key, 'fr') : array_key_exists($key, $json);

            if (! $translated) {
                $missing[] = "{$key} ({$files[0]})";
            }
        }

        $this->assertSame([], $missing, 'Textes sans traduction française');
    }

    /** @test */
    public function each_module_translates_its_own_texts()
    {
        $shared = $this->translations(lang_path('fr.json'));
        $misplaced = [];

        foreach ($this->keysInUse() as $key => $files) {
            if ($this->isGroupKey($key)) {
                continue;
            }

            foreach ($files as $file) {
                // Un module ne dépend pas des traductions d'un autre : texte partagé dans lang/fr.json
                $own = preg_match('#^app/(Modules/[^/]+|Core)/#', $file, $match)
                    ? $this->translations(app_path("{$match[1]}/Resources/lang/fr.json"))
                    : [];

                if (! array_key_exists($key, $shared) && ! array_key_exists($key, $own)) {
                    $misplaced[] = "{$key} ({$file})";
                }
            }
        }

        $this->assertSame([], $misplaced, 'Textes traduits dans un autre module que celui qui les affiche');
    }

    /** @test */
    public function a_text_has_the_same_translation_in_every_file()
    {
        $seen = [];
        $conflicts = [];

        $files = [lang_path('fr.json'), app_path('Core/Resources/lang/fr.json'), ...File::glob(app_path('Modules/*/Resources/lang/fr.json'))];

        foreach ($files as $path) {
            foreach ($this->translations($path) as $key => $value) {
                if (isset($seen[$key]) && $seen[$key] !== $value) {
                    $conflicts[] = "{$key} : « {$seen[$key]} » / « {$value} »";
                }
                $seen[$key] ??= $value;
            }
        }

        $this->assertSame([], $conflicts);
    }

    /** @test */
    public function pages_and_validation_messages_are_displayed_in_french()
    {
        app()->setLocale('fr');
        $this->actingAs(User::factory()->backOffice()->create());

        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('<html lang="fr">', false)
            ->assertSee('Ce qui demande votre attention aujourd\'hui')
            ->assertDontSee('What needs your attention');

        $this->get(route('admin.products.index'))->assertOk()->assertSee('Nouveau produit')->assertSee('Prix d\'achat');

        $this->post(route('admin.categories.store'), ['name' => ''])
            ->assertSessionHasErrors(['name' => 'Le champ nom est obligatoire.']);
    }

    /** @test */
    public function list_exports_are_titled_and_labelled_in_french()
    {
        app()->setLocale('fr');
        $export = app(ProductsExport::class);

        $this->assertSame('Produits', $export->title());
        $labels = array_map(fn (Column $column) => $column->label, $export->columns());
        $this->assertSame(['Produit', 'Catégorie', 'Prix d\'achat', 'Valeur du stock'], array_values(array_intersect($labels, ['Produit', 'Catégorie', 'Prix d\'achat', 'Valeur du stock'])));
        $this->assertSame(['Recherche' => '« perruque »', 'Statut' => 'Archivés'], $export->describeFilters(['search' => 'perruque', 'status' => 'archived']));
    }

    /**
     * Clés littérales des appels __(), trans(), trans_choice() et @lang : vues et code de l'application,
     * et la pagination de Laravel (Bootstrap 5). Les clés construites ('pos.methods.' . $method) sont ignorées.
     *
     * @return array<string, list<string>> clé => fichiers qui l'utilisent (relatifs au projet)
     */
    private function keysInUse(): array
    {
        $files = collect([app_path(), resource_path('views'), base_path('routes')])
            ->flatMap(fn (string $directory) => File::allFiles($directory))
            ->map(fn ($file) => $file->getPathname())
            ->filter(fn (string $path) => str_ends_with($path, '.php'))
            ->push(base_path('vendor/laravel/framework/src/Illuminate/Pagination/resources/views/bootstrap-5.blade.php'));

        $pattern = '/(?:\b__|\btrans_choice|@lang|\btrans)\(\s*(?:\'((?:[^\'\\\\]|\\\\.)*)\'|"((?:[^"\\\\]|\\\\.)*)")/';
        $keys = [];

        foreach ($files as $path) {
            preg_match_all($pattern, file_get_contents($path), $matches, PREG_SET_ORDER | PREG_UNMATCHED_AS_NULL);
            $relative = str_replace('\\', '/', substr($path, strlen(base_path()) + 1));

            foreach ($matches as $match) {
                $key = $match[1] !== null
                    ? str_replace(["\\'", '\\\\'], ["'", '\\'], $match[1])
                    : str_replace(['\\"', '\\\\'], ['"', '\\'], $match[2]);

                if (str_contains($key, '$') || str_ends_with($key, '.')) {
                    continue;
                }

                $keys[$key][] = $relative;
            }
        }

        return $keys;
    }

    /** Clé d'un fichier PHP de traductions (« pos.title » : lang/fr/pos.php), par opposition à un texte anglais. */
    private function isGroupKey(string $key): bool
    {
        return preg_match('/^([a-z0-9_-]+)(\.[a-z0-9_-]+)+$/', $key, $match) === 1 && is_file(lang_path("fr/{$match[1]}.php"));
    }

    /** @return array<string, string> */
    private function translations(string $path): array
    {
        return is_file($path) ? json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR) : [];
    }
}
