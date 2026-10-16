<?php

namespace App\Core\Navigation;

/** Menu du back office résolu pour une page : ce que le layout affiche. */
final readonly class Navigation
{
    /**
     * @param  list<MenuSpace>  $spaces  tuiles du lanceur
     * @param  ?MenuSpace  $current  espace de la page courante (null : page hors menu)
     * @param  list<array{label: string, url: ?string}>  $breadcrumb  le dernier élément est la page courante (sans lien)
     * @param  array<string, string>  $shortcuts  touche → URL, pour Alt + touche
     */
    public function __construct(
        public array $spaces,
        public ?MenuSpace $current,
        public array $breadcrumb,
        public array $shortcuts,
    ) {}
}
