{{--
    Menu du module courant. Menu principal : les entrées de l'espace ; menu secondaire : la liste déroulante
    d'une entrée qui a des enfants. Masqué pour un espace d'une seule page (tableau de bord).
    Les listes déroulantes sont positionnées en "fixed" pour sortir de la barre, qui défile horizontalement sur mobile.
--}}
@php $current = $nav->current; @endphp
@if ($current && (count($current->items) > 1 || $current->items[0]->children))
    <nav class="bo-module-nav" aria-label="{{ __('backoffice.module_menu') }}">
        <ul class="bo-module-menu">
            @foreach ($current->items as $item)
                <li @class(['dropdown' => $item->children])>
                    @if ($item->children)
                        <button type="button" @class(['bo-module-link dropdown-toggle', 'is-active' => $item->active])
                            data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false"
                            @if ($item->shortcut) title="Alt + {{ strtoupper($item->shortcut) }}" @endif>
                            @if ($item->icon)<i class="bi {{ $item->icon }}" aria-hidden="true"></i>@endif
                            {{ $item->label }}
                        </button>
                        <ul class="dropdown-menu">
                            @foreach ($item->children as $child)
                                <li>
                                    <a @class(['dropdown-item', 'active' => $child->active]) href="{{ $child->url }}"
                                        @if ($child->current) aria-current="page" @endif>
                                        {{ $child->label }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <a @class(['bo-module-link', 'is-active' => $item->active]) href="{{ $item->url }}"
                            @if ($item->current) aria-current="page" @endif
                            @if ($item->shortcut) title="Alt + {{ strtoupper($item->shortcut) }}" @endif>
                            @if ($item->icon)<i class="bi {{ $item->icon }}" aria-hidden="true"></i>@endif
                            {{ $item->label }}
                        </a>
                    @endif
                </li>
            @endforeach
        </ul>
    </nav>
@endif
