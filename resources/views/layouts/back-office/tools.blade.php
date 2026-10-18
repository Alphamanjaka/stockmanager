{{-- Outils : recherche globale, notifications, profil ($unreadCount et $latestNotifications : CoreServiceProvider).
     Langue et thème se règlent dans Administration › Paramètres › Interface & System. --}}
<div class="bo-tools">
    {{-- Recherche globale (global-search.js) --}}
    <div class="dropdown search-input-wrapper bo-search d-none d-sm-block">
        <div class="input-group input-group-sm">
            <span class="input-group-text"><i class="bi bi-search" aria-hidden="true"></i></span>
            <input type="search" id="global-search-input" class="form-control"
                placeholder="{{ __('backoffice.search_placeholder') }}" aria-label="{{ __('backoffice.search_placeholder') }}"
                autocomplete="off" data-bs-toggle="dropdown" aria-expanded="false" />
        </div>
        <div id="global-search-results" class="dropdown-menu"></div>
    </div>

    @auth
        {{-- Notifications --}}
        <div class="dropdown">
            <button class="bo-tool" type="button" data-bs-toggle="dropdown" aria-expanded="false"
                aria-label="{{ __('backoffice.notifications') }}" title="{{ __('backoffice.notifications') }}">
                <i class="bi bi-bell" aria-hidden="true"></i>
                @if ($unreadCount > 0)
                    <span class="bo-tool-badge">{{ $unreadCount > 99 ? '99+' : $unreadCount }}</span>
                @endif
            </button>
            <ul class="dropdown-menu dropdown-menu-end bo-notifications">
                <li class="dropdown-header">{{ __('backoffice.notifications') }}</li>
                @forelse ($latestNotifications as $notification)
                    <li>
                        <a class="dropdown-item align-items-start py-2"
                            href="{{ Route::has('admin.products.index') ? route('admin.products.index', ['search' => $notification->data['product_name'] ?? '']) : '#' }}">
                            <i class="bi bi-exclamation-triangle text-warning mt-1" aria-hidden="true"></i>
                            <span>
                                <strong class="d-block">{{ $notification->data['product_name'] ?? 'Product' }}</strong>
                                <small class="text-muted">{{ __('backoffice.stock_alert') }}:
                                    {{ $notification->data['current_stock'] ?? 0 }} {{ __('backoffice.stock_left') }}</small>
                            </span>
                        </a>
                    </li>
                @empty
                    <li><span class="dropdown-item-text text-muted small">{{ __('backoffice.no_notifications') }}</span></li>
                @endforelse
            </ul>
        </div>

        {{-- Profil --}}
        <div class="dropdown">
            <button class="bo-tool dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="ui-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                <span class="d-none d-lg-inline">{{ auth()->user()->name }}</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><span class="dropdown-item-text small text-muted">{{ auth()->user()->email }}</span></li>
                <li>
                    <a class="dropdown-item" href="{{ route('admin.users.index') }}">
                        <i class="bi bi-person" aria-hidden="true"></i> {{ __('backoffice.profile') }}
                    </a>
                </li>
                <li>
                    <hr class="dropdown-divider">
                </li>
                <li>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button class="dropdown-item text-danger" type="submit">
                            <i class="bi bi-box-arrow-right" aria-hidden="true"></i> {{ __('backoffice.logout') }}
                        </button>
                    </form>
                </li>
            </ul>
        </div>
    @endauth
</div>
