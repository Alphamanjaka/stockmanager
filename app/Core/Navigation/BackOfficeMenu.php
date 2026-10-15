<?php

namespace App\Core\Navigation;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/**
 * Menu du back office, alimenté par les modules (propriétés $menuSpaces et $menu de ModuleServiceProvider).
 *
 * Un espace est une tuile du lanceur (Catalogue, Stock, Achats...). Chaque module y range ses pages :
 *
 *     'catalog' => [
 *         ['label' => 'backoffice.products', 'icon' => 'fa-box', 'route' => 'admin.products.index', 'shortcut' => 'p',
 *          'children' => [ // menu secondaire (liste déroulante)
 *              ['label' => 'backoffice.menu.all_products', 'route' => 'admin.products.index'],
 *              ['label' => 'backoffice.menu.new_product', 'route' => 'admin.products.create'],
 *          ]],
 *     ],
 *
 * Clés d'une entrée :
 * - label    clé de traduction (obligatoire)
 * - route    nom de route sans paramètre ; sans route, l'entrée mène à son premier enfant
 * - icon     classe Font Awesome (ex. 'fa-box')
 * - shortcut touche du raccourci Alt + touche, unique dans tout le menu
 * - children menu secondaire
 * - order    rang dans la liste (0 par défaut) : permet à un module de se placer après les pages d'un autre
 * - can      capacité (Gate) requise pour voir l'entrée
 * - active   motifs de noms de routes qui rendent l'entrée active ; par défaut « admin.x.* » pour « admin.x.index »,
 *            sinon la route seule
 *
 * Une entrée dont la route n'existe pas, ou que l'utilisateur n'a pas le droit de voir, est masquée ;
 * un espace sans entrée visible disparaît du lanceur. Le layout ne peut donc pas planter à cause du menu.
 */
final class BackOfficeMenu
{
    /** Touche du lanceur (Alt + L) : aucune entrée ne peut la prendre. */
    public const LAUNCHER_SHORTCUT = 'l';

    /** Actions dont le nom s'ajoute au fil d'Ariane quand la page n'a pas sa propre entrée de menu. */
    private const BREADCRUMB_ACTIONS = ['create', 'show', 'edit'];

    /** @var array<string, array{label: string, icon: string}> */
    private array $spaces = [];

    /** @var array<string, list<array>> entrées déclarées, par espace */
    private array $entries = [];

    /** @param  array<string, array{label: string, icon: string}>  $spaces */
    public function addSpaces(array $spaces): void
    {
        $this->spaces = array_merge($this->spaces, $spaces);
    }

    public function addEntries(string $space, array $entries): void
    {
        $this->entries[$space] = [...$this->entries[$space] ?? [], ...$entries];
    }

    /** Menu visible pour la page demandée. */
    public function resolve(Request $request): Navigation
    {
        $spaces = [];

        foreach ($this->spaces as $key => $space) {
            $items = $this->resolveList($this->entries[$key] ?? [], $request);

            if ($items !== []) {
                $spaces[] = new MenuSpace(
                    key: $key,
                    label: __($space['label']),
                    icon: $space['icon'],
                    url: $items[0]->url,
                    active: array_any($items, fn (MenuItem $item) => $item->active),
                    items: $items,
                );
            }
        }

        $current = array_find($spaces, fn (MenuSpace $space) => $space->active);

        return new Navigation($spaces, $current, $this->breadcrumb($current, $request), $this->shortcuts($spaces));
    }

    /**
     * Raccourcis déclarés plusieurs fois (entrées visibles ou non), ou qui prennent la touche du lanceur.
     *
     * @return array<string, list<string>> touche → libellés des entrées en conflit
     */
    public function shortcutConflicts(): array
    {
        $labelsByKey = [self::LAUNCHER_SHORTCUT => ['launcher']];

        foreach ($this->declaredEntries() as $entry) {
            if (isset($entry['shortcut'])) {
                $labelsByKey[strtolower($entry['shortcut'])][] = $entry['label'];
            }
        }

        return array_filter($labelsByKey, fn (array $labels) => count($labels) > 1);
    }

    /** @return list<string> clés de traduction des espaces et des entrées déclarés */
    public function translationKeys(): array
    {
        $keys = array_column($this->spaces, 'label');

        foreach ($this->declaredEntries() as $entry) {
            $keys[] = $entry['label'];
        }

        return array_values(array_unique($keys));
    }

    /**
     * Résout des entrées sœurs. Le lien le plus précis gagne : si l'une d'elles mène exactement à la page courante,
     * les motifs larges de ses sœurs sont ignorés (« Nouveau produit » est actif, pas « Tous les produits »).
     *
     * @return list<MenuItem>
     */
    private function resolveList(array $entries, Request $request): array
    {
        $entries = array_filter($entries, fn (array $entry) => ! isset($entry['can']) || Gate::allows($entry['can']));

        // usort est stable : à rang égal, l'ordre de déclaration est conservé
        usort($entries, fn (array $a, array $b) => ($a['order'] ?? 0) <=> ($b['order'] ?? 0));

        $hasExactMatch = array_any($entries, fn (array $entry) => $this->routeOf($entry) !== null
            && $request->routeIs($this->routeOf($entry)));

        $items = array_map(fn (array $entry) => $this->resolveItem($entry, $request, ! $hasExactMatch), $entries);

        return array_values(array_filter($items));
    }

    private function resolveItem(array $entry, Request $request, bool $matchPatterns): ?MenuItem
    {
        $children = $this->resolveList($entry['children'] ?? [], $request);
        $route = $this->routeOf($entry);

        if ($route === null && $children === []) {
            return null;
        }

        $current = $route !== null && $request->routeIs($route);
        $patterns = $this->activePatterns($entry, $route);
        $matches = $matchPatterns && $patterns !== [] && $request->routeIs(...$patterns);

        return new MenuItem(
            label: __($entry['label']),
            url: $route !== null ? route($route) : $children[0]->url,
            icon: $entry['icon'] ?? null,
            shortcut: isset($entry['shortcut']) ? strtolower($entry['shortcut']) : null,
            active: $current || $matches || array_any($children, fn (MenuItem $child) => $child->active),
            current: $current,
            children: $children,
        );
    }

    /** Route de l'entrée si elle existe (une route retirée d'un module masque l'entrée au lieu de faire planter). */
    private function routeOf(array $entry): ?string
    {
        return isset($entry['route']) && Route::has($entry['route']) ? $entry['route'] : null;
    }

    /** @return list<string> */
    private function activePatterns(array $entry, ?string $route): array
    {
        if (isset($entry['active'])) {
            return (array) $entry['active'];
        }

        if ($route === null) {
            return [];
        }

        return [str_ends_with($route, '.index') ? Str::beforeLast($route, '.index').'.*' : $route];
    }

    /**
     * Espace › entrée › sous-entrée › action (Nouveau, Détail, Modifier).
     * Un niveau qui porte le même nom que le précédent (« Ventes › Ventes ») ou qui mène à la même page
     * (« Produits › Tous les produits ») n'est pas répété.
     *
     * @return list<array{label: string, url: ?string}>
     */
    private function breadcrumb(?MenuSpace $space, Request $request): array
    {
        if ($space === null) {
            return [];
        }

        $crumbs = [['label' => $space->label, 'url' => $space->url]];
        $items = $space->items;
        $deepest = null;

        while ($item = array_find($items, fn (MenuItem $item) => $item->active)) {
            $last = array_key_last($crumbs);

            if ($item->label === $crumbs[$last]['label']) {
                $crumbs[$last]['url'] = $item->url;
            } elseif ($deepest === null || $item->url !== $crumbs[$last]['url']) {
                $crumbs[] = ['label' => $item->label, 'url' => $item->url];
            }

            $deepest = $item;
            $items = $item->children;
        }

        $action = Str::afterLast((string) $request->route()?->getName(), '.');

        if ($deepest !== null && ! $deepest->current && in_array($action, self::BREADCRUMB_ACTIONS, true)) {
            $crumbs[] = ['label' => __("backoffice.crumbs.{$action}"), 'url' => null];
        }

        $crumbs[array_key_last($crumbs)]['url'] = null;

        return $crumbs;
    }

    /**
     * @param  list<MenuSpace>  $spaces
     * @return array<string, string> touche → URL (à touche égale, la première entrée déclarée l'emporte)
     */
    private function shortcuts(array $spaces): array
    {
        $shortcuts = [];
        $pending = array_merge(...array_map(fn (MenuSpace $space) => $space->items, $spaces));

        while ($item = array_shift($pending)) {
            if ($item->shortcut !== null) {
                $shortcuts[$item->shortcut] ??= $item->url;
            }

            array_push($pending, ...$item->children);
        }

        return $shortcuts;
    }

    /** @return iterable<array> toutes les entrées déclarées, sous-menus compris */
    private function declaredEntries(): iterable
    {
        $pending = array_merge([], ...array_values($this->entries));

        while ($entry = array_shift($pending)) {
            yield $entry;
            array_push($pending, ...$entry['children'] ?? []);
        }
    }
}
