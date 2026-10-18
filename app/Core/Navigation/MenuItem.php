<?php

namespace App\Core\Navigation;

/** Entrée du menu, prête à afficher (libellé traduit, URL calculée, état actif pour la page courante). */
final readonly class MenuItem
{
    /**
     * @param  bool  $active  la page courante appartient à cette entrée (ou à l'un de ses enfants)
     * @param  bool  $current  la page courante est exactement la route de cette entrée
     * @param  list<MenuItem>  $children  menu secondaire
     */
    public function __construct(
        public string $label,
        public string $url,
        public ?string $icon,
        public ?string $shortcut,
        public bool $active,
        public bool $current,
        public array $children = [],
    ) {}
}
