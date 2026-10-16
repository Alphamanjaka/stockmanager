{{-- Lanceur : liste des modules et de leurs pages, accessible de partout (bouton ou Alt + L) --}}
<div class="dropdown bo-launcher">
    <button id="bo-launcher-toggle" class="bo-launcher-toggle" type="button" data-bs-toggle="dropdown"
        data-bs-display="static" aria-expanded="false" aria-label="{{ __('backoffice.open_launcher') }}"
        title="{{ __('backoffice.open_launcher') }} (Alt + {{ strtoupper(\App\Core\Navigation\BackOfficeMenu::LAUNCHER_SHORTCUT) }})"
        data-shortcut="{{ \App\Core\Navigation\BackOfficeMenu::LAUNCHER_SHORTCUT }}">
        <i class="bi bi-grid-3x3-gap-fill" aria-hidden="true"></i>
    </button>

    <div class="dropdown-menu bo-launcher-panel">
        <div class="bo-launcher-head">
            <span><i class="bi bi-box-seam-fill me-1" aria-hidden="true"></i> StockMaster ·
                {{ __('backoffice.launcher') }}</span>
            <kbd class="bo-kbd">Alt+{{ strtoupper(\App\Core\Navigation\BackOfficeMenu::LAUNCHER_SHORTCUT) }}</kbd>
        </div>

        <div class="bo-launcher-grid">
            @foreach ($nav->spaces as $space)
                @php $single = count($space->items) === 1 && ! $space->items[0]->children; @endphp
                <section class="bo-launcher-space {{ $space->active ? 'is-active' : '' }}">
                    <a class="dropdown-item bo-launcher-space-link" href="{{ $space->url }}"
                        @if ($space->active) aria-current="true" @endif>
                        <span class="bo-space-icon"><i class="bi {{ $space->icon }}" aria-hidden="true"></i></span>
                        <span class="flex-grow-1">{{ $space->label }}</span>
                        @if ($single && $space->items[0]->shortcut)
                            <kbd class="bo-kbd">Alt+{{ strtoupper($space->items[0]->shortcut) }}</kbd>
                        @endif
                    </a>

                    @unless ($single)
                        <ul class="bo-launcher-items list-unstyled">
                            @foreach ($space->items as $item)
                                <li>
                                    <a class="dropdown-item {{ $item->active ? 'is-active' : '' }}" href="{{ $item->url }}">
                                        <span>{{ $item->label }}</span>
                                        @if ($item->shortcut)
                                            <kbd class="bo-kbd">Alt+{{ strtoupper($item->shortcut) }}</kbd>
                                        @endif
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endunless
                </section>
            @endforeach
        </div>
    </div>
</div>
