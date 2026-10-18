{{--
    Écran de caisse, commun au front office et au back office.
    Variables : $pos (PosCatalog::build), $customers, $storeUrl.
    Comportement : resources/js/pos.js — mise en page : resources/css/pos.css.
--}}
@php
    $posConfig = [
        'storeUrl' => $storeUrl,
        'currency' => settings('currency_symbol', 'MGA'),
        'currencyPosition' => settings('currency_position', 'after'),
        'locale' => str_replace('_', '-', app()->getLocale()),
        // Panier en cours et réglages mémorisés par poste et par utilisateur
        'storageKey' => 'pos.' . auth()->id(),
        'methods' => __('pos.methods'),
        'i18n' => __('pos.js') + [
            'all' => __('pos.all'),
            'all_brands' => __('pos.all_brands'),
            'brands' => __('pos.brands'),
            'top_sellers' => __('pos.top_sellers'),
            'exact_amount' => __('pos.exact_amount'),
        ],
    ];
    $methodIcons = ['cash' => 'cash-stack', 'mobile_money' => 'phone', 'card' => 'credit-card'];
@endphp

@push('css')
    @vite('resources/js/pos.js')
@endpush

<div class="pos" id="pos">
    <section class="pos-catalog">
        <div class="pos-search">
            <label for="pos-search" class="visually-hidden">{{ __('pos.search_label') }}</label>
            <i class="bi bi-upc-scan pos-search-icon" aria-hidden="true"></i>
            <span class="pos-multiplier" id="pos-multiplier" title="{{ __('pos.quantity_multiplier') }}" hidden></span>
            <input type="search" id="pos-search" class="form-control" placeholder="{{ __('pos.search_placeholder') }}"
                autocomplete="off" spellcheck="false" autofocus>
            <kbd class="pos-search-kbd" aria-hidden="true">F2</kbd>
        </div>

        <div class="pos-filters">
            <div class="pos-chips" id="pos-categories" role="group" aria-label="{{ __('pos.categories') }}"></div>
            <div class="pos-chips pos-chips-sub" id="pos-subcategories" role="group" hidden></div>
            <div class="pos-chips" id="pos-brands" role="group" aria-label="{{ __('pos.brands') }}" hidden></div>
        </div>

        <div class="pos-grid" id="pos-grid"></div>
        <p class="pos-grid-note" id="pos-grid-note" hidden></p>
    </section>

    <aside class="pos-cart" aria-labelledby="pos-cart-title">
        <header class="pos-cart-header">
            <h2 id="pos-cart-title">
                <i class="bi bi-cart3" aria-hidden="true"></i> {{ __('pos.cart') }}
                <span class="badge rounded-pill text-bg-primary" id="pos-count">0</span>
            </h2>
            <div class="dropdown">
                <button type="button" class="btn btn-sm pos-icon-btn" data-bs-toggle="dropdown" data-bs-auto-close="outside"
                    aria-expanded="false" aria-label="{{ __('pos.settings') }}" title="{{ __('pos.settings') }}">
                    <i class="bi bi-gear" aria-hidden="true"></i>
                </button>
                <div class="dropdown-menu dropdown-menu-end p-3 pos-settings">
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" role="switch" id="pos-pref-sound">
                        <label class="form-check-label" for="pos-pref-sound">{{ __('pos.sound') }}</label>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="pos-pref-print">
                        <label class="form-check-label" for="pos-pref-print">{{ __('pos.auto_print') }}</label>
                    </div>
                </div>
            </div>
        </header>

        <ol class="pos-lines" id="pos-lines" aria-live="polite"></ol>
        <div class="pos-cart-empty" id="pos-cart-empty">
            <i class="bi bi-upc-scan" aria-hidden="true"></i>
            <strong>{{ __('pos.cart_empty_title') }}</strong>
            <span>{{ __('pos.cart_empty_hint') }}</span>
        </div>

        <footer class="pos-cart-footer">
            <div class="pos-total">
                <span>{{ __('pos.total') }}</span>
                <strong id="pos-total">0</strong>
            </div>
            <button type="button" class="btn btn-success btn-lg w-100 pos-pay" id="pos-pay" disabled>
                <i class="bi bi-cash-coin" aria-hidden="true"></i> {{ __('pos.pay') }} <kbd>F9</kbd>
            </button>
            <button type="button" class="btn btn-outline-secondary w-100 mt-2" id="pos-cancel" disabled>
                {{ __('pos.cancel_sale') }} <kbd>F4</kbd>
            </button>
        </footer>
    </aside>

    <ul class="pos-help" aria-label="{{ __('pos.shortcuts_title') }}">
        <li><kbd>F2</kbd> {{ __('pos.shortcuts.search') }}</li>
        <li><kbd>3</kbd><kbd>*</kbd> {{ __('pos.shortcuts.multiply') }}</li>
        <li><kbd>+</kbd><kbd>−</kbd> {{ __('pos.shortcuts.qty') }}</li>
        <li><kbd>↑</kbd><kbd>↓</kbd> {{ __('pos.shortcuts.lines') }}</li>
        <li><kbd>{{ __('pos.keys.del') }}</kbd> {{ __('pos.shortcuts.remove') }}</li>
        <li><kbd>{{ __('pos.keys.esc') }}</kbd> {{ __('pos.shortcuts.clear') }}</li>
        <li><kbd>F4</kbd> {{ __('pos.shortcuts.cancel') }}</li>
        <li><kbd>F9</kbd> {{ __('pos.shortcuts.pay') }}</li>
    </ul>
</div>

<div class="pos-toast" id="pos-toast" role="status" aria-live="polite"></div>

{{-- Encaissement, puis écran « vente terminée » (monnaie à rendre, reçu) --}}
<div class="modal fade pos-modal" id="pos-payment" tabindex="-1" aria-labelledby="pos-payment-title" aria-hidden="true"
    data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form class="modal-content" id="pos-form" novalidate>
            @csrf
            <div id="pos-step-pay">
                <div class="modal-header">
                    <h2 class="modal-title h4" id="pos-payment-title">{{ __('pos.payment_title') }}</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('pos.back') }}"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger d-none" id="pos-pay-error" role="alert"></div>
                    <div class="row g-4">
                        <div class="col-md-7">
                            <div class="pos-due">
                                <span>{{ __('pos.amount_due') }}</span>
                                <strong id="pos-due">0</strong>
                            </div>

                            <fieldset class="mb-3">
                                <legend class="form-label fs-6">{{ __('pos.method') }}</legend>
                                <div class="pos-methods">
                                    @foreach (\App\Modules\Sale\Models\Sale::PAYMENT_METHODS as $method)
                                        <input type="radio" class="btn-check" name="payment_method" value="{{ $method }}"
                                            id="pos-method-{{ $method }}" autocomplete="off" @checked($loop->first)>
                                        <label class="btn btn-outline-primary" for="pos-method-{{ $method }}">
                                            <i class="bi bi-{{ $methodIcons[$method] ?? 'wallet2' }}" aria-hidden="true"></i>
                                            {{ __("pos.methods.{$method}") }}
                                        </label>
                                    @endforeach
                                </div>
                            </fieldset>

                            <div id="pos-cash">
                                <label for="pos-received" class="form-label fs-6">{{ __('pos.amount_received') }}</label>
                                <input type="text" inputmode="decimal" name="amount_received" id="pos-received"
                                    class="form-control form-control-lg pos-received" autocomplete="off"
                                    aria-describedby="pos-received-hint">
                                <div class="form-text" id="pos-received-hint">{{ __('pos.amount_received_hint') }}</div>
                                <div class="pos-quick-cash" id="pos-quick-cash"></div>
                            </div>

                            <div class="pos-change" id="pos-change" aria-live="polite"></div>
                        </div>

                        <div class="col-md-5">
                            <dl class="pos-recap">
                                <dt>{{ __('pos.subtotal') }}</dt>
                                <dd id="pos-subtotal">0</dd>
                            </dl>
                            <label for="pos-discount" class="form-label">{{ __('pos.discount') }}
                                ({{ $posConfig['currency'] }})</label>
                            <input type="text" inputmode="decimal" name="discount" id="pos-discount"
                                class="form-control mb-3" value="0" autocomplete="off">

                            <h3 class="form-label fs-6 mb-2">{{ __('pos.customer_optional') }}</h3>
                            @include('sale::partials.customer-picker')
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-lg" data-bs-dismiss="modal">
                        {{ __('pos.back') }} <kbd>{{ __('pos.keys.esc') }}</kbd>
                    </button>
                    <button type="submit" class="btn btn-success btn-lg" id="pos-validate">
                        <span class="spinner-border spinner-border-sm d-none" aria-hidden="true"></span>
                        <i class="bi bi-check2-circle" aria-hidden="true"></i> {{ __('pos.validate') }}
                        <kbd>{{ __('pos.keys.enter') }}</kbd>
                    </button>
                </div>
            </div>

            <div id="pos-step-done" hidden>
                <div class="modal-body text-center py-5">
                    <i class="bi bi-check-circle-fill pos-done-icon" aria-hidden="true"></i>
                    <h2 class="h3 mt-2">{{ __('pos.success_title') }}</h2>
                    <p class="pos-muted" id="pos-done-ref"></p>
                    <div class="pos-done-change" id="pos-done-change" aria-live="assertive"></div>
                    <div class="d-flex flex-wrap gap-2 justify-content-center mt-4">
                        <button type="button" class="btn btn-outline-primary btn-lg" id="pos-print">
                            <i class="bi bi-printer" aria-hidden="true"></i> {{ __('pos.print_receipt') }} <kbd>P</kbd>
                        </button>
                        <button type="button" class="btn btn-primary btn-lg" id="pos-new-sale">
                            <i class="bi bi-plus-circle" aria-hidden="true"></i> {{ __('pos.new_sale') }}
                            <kbd>{{ __('pos.keys.enter') }}</kbd>
                        </button>
                    </div>
                    <a href="#" id="pos-done-link" class="d-inline-block mt-3 small">{{ __('pos.view_sale') }}</a>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Le reçu y est chargé puis imprimé, sans quitter la caisse --}}
<iframe id="pos-print-frame" class="pos-print-frame" title="{{ __('pos.receipt') }}" tabindex="-1"
    aria-hidden="true"></iframe>

<script type="application/json" id="pos-data">@json(['config' => $posConfig] + $pos)</script>
