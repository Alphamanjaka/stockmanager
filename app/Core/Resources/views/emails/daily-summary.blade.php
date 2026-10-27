{{--
    Résumé quotidien (DailySummaryMail). Mise en page en tableaux et styles simples : lisible dans les
    messageries (Gmail, Outlook, téléphone). Le même rendu sert d'aperçu dans le back office.
--}}
@php
    $s = $summary;
    $amount = fn ($value) => money($value, fmod((float) $value, 1) == 0 ? 0 : 2);
    $change = $s['previous']['change'];
    $company = settings('company_name', config('app.name'));
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Daily summary') }}</title>
    <style>
        body { margin: 0; padding: 0; background: #f1f3f5; font-family: Arial, Helvetica, sans-serif; color: #212529; }
        .wrap { max-width: 620px; margin: 0 auto; padding: 16px; }
        .card { background: #ffffff; border-radius: 8px; padding: 20px; margin-bottom: 12px; }
        h1 { font-size: 20px; margin: 0 0 4px; }
        h2 { font-size: 15px; margin: 0 0 12px; color: #495057; text-transform: uppercase; letter-spacing: 0.5px; }
        .muted { color: #6c757d; font-size: 13px; }
        .big { font-size: 28px; font-weight: bold; margin: 4px 0; }
        .up { color: #198754; }
        .down { color: #dc3545; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        td, th { padding: 6px 4px; border-bottom: 1px solid #e9ecef; text-align: left; vertical-align: top; }
        th { color: #6c757d; font-weight: normal; font-size: 12px; }
        .num { text-align: right; white-space: nowrap; }
        .kpi td { border: 0; padding: 4px 8px 4px 0; width: 33%; }
        .kpi .label { color: #6c757d; font-size: 12px; }
        .kpi .value { font-size: 17px; font-weight: bold; }
        .alert { color: #dc3545; font-weight: bold; }
        .ok { color: #198754; }
        .footer { text-align: center; color: #868e96; font-size: 12px; padding: 8px; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="card">
            <div class="muted">{{ $company }}</div>
            <h1>{{ __('Daily summary of :date', ['date' => $s['date']->translatedFormat('l j F Y')]) }}</h1>

            <div class="big">{{ $amount($s['revenue']) }}</div>
            <div class="muted">
                {{ __('Revenue, credit notes deducted') }}
                @if ($change !== null)
                    · <span class="{{ $change >= 0 ? 'up' : 'down' }}">{{ $change >= 0 ? '+' : '' }}{{ $change }} %</span>
                    {{ __('compared with :day (:amount)', ['day' => $s['previous']['date']->translatedFormat('l j F'), 'amount' => $amount($s['previous']['revenue'])]) }}
                @endif
            </div>

            <table class="kpi" style="margin-top: 12px;">
                <tr>
                    <td>
                        <div class="label">{{ __('Sales') }}</div>
                        <div class="value">{{ $s['sales_count'] }}</div>
                    </td>
                    <td>
                        <div class="label">{{ __('Average basket') }}</div>
                        <div class="value">{{ $amount($s['average']) }}</div>
                    </td>
                    <td>
                        <div class="label">{{ __('Gross margin') }}</div>
                        <div class="value">{{ $amount($s['margin']['margin']) }}</div>
                        @if ($s['margin']['rate'] !== null)
                            <div class="label">{{ $s['margin']['rate'] }} %</div>
                        @endif
                    </td>
                </tr>
            </table>
            @if ($s['refunds'] > 0)
                <div class="muted">{{ __(':amount refunded by credit notes', ['amount' => $amount($s['refunds'])]) }}</div>
            @endif
            @if ($s['margin']['excluded'] > 0)
                <div class="muted">{{ __(':count sale(s) without a known cost not counted in the margin', ['count' => $s['margin']['excluded']]) }}</div>
            @endif
        </div>

        @if ($s['payments'])
            <div class="card">
                <h2>{{ __('Payments') }}</h2>
                <table>
                    @foreach ($s['payments'] as $payment)
                        <tr>
                            <td>
                                {{ __('pos.methods.' . $payment['method']) }}@if ($payment['operator']) · {{ __('pos.operators.' . $payment['operator']) }}@endif
                                <span class="muted">({{ $payment['count'] }})</span>
                            </td>
                            <td class="num">{{ $amount($payment['total']) }}</td>
                        </tr>
                    @endforeach
                </table>
            </div>
        @endif

        @if ($s['top_products'])
            <div class="card">
                <h2>{{ __('Best sellers of the day') }}</h2>
                <table>
                    <tr><th>{{ __('Product') }}</th><th class="num">{{ __('Qty') }}</th><th class="num">{{ __('Revenue') }}</th></tr>
                    @foreach ($s['top_products'] as $product)
                        <tr>
                            <td>{{ $product['label'] }}</td>
                            <td class="num">{{ $product['quantity'] }}</td>
                            <td class="num">{{ $amount($product['revenue']) }}</td>
                        </tr>
                    @endforeach
                </table>
            </div>
        @endif

        @if (count($s['sellers']) > 1)
            <div class="card">
                <h2>{{ __('Sellers') }}</h2>
                <table>
                    @foreach ($s['sellers'] as $seller)
                        <tr>
                            <td>{{ $seller['name'] }} <span class="muted">({{ __(':count sale(s)', ['count' => $seller['count']]) }})</span></td>
                            <td class="num">{{ $amount($seller['total']) }}</td>
                        </tr>
                    @endforeach
                </table>
            </div>
        @endif

        <div class="card">
            <h2>{{ __('To do') }}</h2>
            <table>
                <tr>
                    <td>{{ __('Products to restock') }}</td>
                    <td class="num {{ $s['shortages']['count'] > 0 ? 'alert' : 'ok' }}">{{ $s['shortages']['count'] }}</td>
                </tr>
                @foreach ($s['shortages']['items'] as $item)
                    <tr>
                        <td class="muted">— {{ $item['label'] }}</td>
                        <td class="num muted">
                            {{ __(':stock in stock, alert at :alert', ['stock' => $item['stock'], 'alert' => $item['alert']]) }}@if ($item['ordered'] > 0), {{ __(':count ordered', ['count' => $item['ordered']]) }}@endif
                        </td>
                    </tr>
                @endforeach
                <tr>
                    <td>{{ __('Orders awaiting delivery') }}</td>
                    <td class="num">{{ $s['awaiting']['count'] }}@if ($s['awaiting']['count'] > 0) · {{ $amount($s['awaiting']['value']) }}@endif</td>
                </tr>
                <tr>
                    <td>{{ __('Unexplained losses of the day') }}</td>
                    <td class="num {{ $s['losses']['count'] > 0 ? 'alert' : 'ok' }}">
                        {{ $s['losses']['count'] > 0 ? __(':count movement(s) · :amount', ['count' => $s['losses']['count'], 'amount' => $amount($s['losses']['value'])]) : __('None') }}
                    </td>
                </tr>
            </table>
            <div class="muted" style="margin-top: 8px;">{{ __('Stock figures show the current situation.') }}</div>
        </div>

        <div class="footer">
            <a href="{{ route('admin.dashboard') }}">{{ __('Open the dashboard') }}</a>
            · {{ __('Sent by :app', ['app' => config('app.name')]) }}
        </div>
    </div>
</body>
</html>
