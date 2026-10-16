{{--
    Carte uniforme : en-tête (titre, icône, actions), barre d'outils facultative, contenu, pied de page.

        <x-ui.card title="Products" icon="bi-box" flush :pagination="$products">
            <x-slot:actions>…boutons de l'en-tête…</x-slot:actions>
            <x-slot:toolbar>…recherche et filtres (<x-ui.filters>)…</x-slot:toolbar>
            …contenu…
            <x-slot:footer>…</x-slot:footer>
        </x-ui.card>

    flush      : contenu bord à bord (tableau, liste) au lieu d'un corps avec marges
    count      : nombre affiché à côté du titre
    pagination : paginateur de la liste, affiché dans le pied de carte s'il y a plusieurs pages
    tone       : "danger" pour une zone sensible (suppression définitive)
--}}
@props([
    'title' => null,
    'icon' => null,
    'subtitle' => null,
    'count' => null,
    'flush' => false,
    'pagination' => null,
    'bodyClass' => null,
    'tone' => null,
])

<section {{ $attributes->class(['card ui-card', 'ui-card-danger' => $tone === 'danger']) }}>
    @if ($title || isset($actions))
        <header class="card-header">
            <div>
                @if ($title)
                    <h2 class="card-title">
                        @if ($icon)
                            <i class="bi {{ $icon }}" aria-hidden="true"></i>
                        @endif
                        {{ $title }}
                        @if ($count !== null)
                            <span class="ui-tag">{{ $count }}</span>
                        @endif
                    </h2>
                @endif
                @if ($subtitle)
                    <p class="card-subtitle">{{ $subtitle }}</p>
                @endif
            </div>
            @isset($actions)
                <div class="ui-card-actions">{{ $actions }}</div>
            @endisset
        </header>
    @endif

    @isset($toolbar)
        <div class="ui-card-toolbar">{{ $toolbar }}</div>
    @endisset

    @if ($flush)
        {{ $slot }}
    @else
        <div @class(['card-body', $bodyClass])>{{ $slot }}</div>
    @endif

    @if (isset($footer) || $pagination?->hasPages())
        <footer class="card-footer">
            {{ $footer ?? '' }}
            @if ($pagination?->hasPages())
                {{ $pagination->links() }}
            @endif
        </footer>
    @endif
</section>
