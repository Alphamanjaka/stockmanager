<?php

namespace App\Core\Navigation;

/** Espace du lanceur (Catalogue, Stock, Achats...) avec ses entrées visibles. */
final readonly class MenuSpace
{
    /**
     * @param  string  $url  page d'accueil de l'espace : sa première entrée visible
     * @param  list<MenuItem>  $items  menu principal de l'espace
     */
    public function __construct(
        public string $key,
        public string $label,
        public string $icon,
        public string $url,
        public bool $active,
        public array $items,
    ) {}
}
