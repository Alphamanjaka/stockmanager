{{--
    Reçu de caisse au format ticket (imprimante thermique 80 mm ; lisible aussi sur A4).
    Imprimé depuis la caisse (cadre caché) ou ouvert seul : ?print=1 lance l'impression à l'ouverture.
--}}
@php
    // Séparateurs de la langue courante ; décimales affichées seulement quand il y en a (ex. Ariary : 13 500 Ar)
    [$decimalPoint, $thousands] = ['en' => ['.', ','], 'es' => [',', '.']][app()->getLocale()] ?? [',', ' '];
    $money = function ($amount) use ($sale, $decimalPoint, $thousands) {
        $amount = (float) $amount;
        $number = number_format($amount, floor($amount) == $amount ? 0 : 2, $decimalPoint, $thousands);

        return settings('currency_position', 'after') === 'before'
            ? "{$sale->currency_symbol} {$number}"
            : "{$number} {$sale->currency_symbol}";
    };
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('pos.receipt') }} {{ $sale->reference }}</title>
    <style>
        @page {
            size: 80mm auto;
            margin: 0;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #e9ecef;
            color: #000;
            font: 12px/1.35 "Courier New", Courier, monospace;
        }

        .ticket {
            width: 80mm;
            margin: 16px auto;
            padding: 4mm 4mm 6mm;
            background: #fff;
        }

        .center {
            text-align: center;
        }

        .company {
            font-size: 15px;
            font-weight: bold;
            text-transform: uppercase;
        }

        hr {
            border: 0;
            border-top: 1px dashed #000;
            margin: 6px 0;
        }

        .row {
            display: flex;
            justify-content: space-between;
            gap: 8px;
        }

        .row span:last-child {
            text-align: right;
            white-space: nowrap;
        }

        .item {
            margin-bottom: 4px;
        }

        .item-name {
            font-weight: bold;
        }

        .muted {
            color: #333;
        }

        .total {
            font-size: 16px;
            font-weight: bold;
        }

        .actions {
            display: flex;
            gap: 8px;
            justify-content: center;
            margin: 16px auto;
        }

        .actions button {
            font: 14px system-ui, sans-serif;
            padding: 8px 16px;
            cursor: pointer;
        }

        @media print {
            body {
                background: #fff;
            }

            .ticket {
                margin: 0;
                width: 100%;
            }

            .actions {
                display: none;
            }
        }
    </style>
</head>

<body>
    <div class="ticket">
        <div class="center">
            <div class="company">{{ $sale->company_name }}</div>
            @if ($sale->company_address)
                <div>{{ $sale->company_address }}</div>
            @endif
            @if ($sale->company_phone)
                <div>Tél. {{ $sale->company_phone }}</div>
            @endif
            @if ($sale->company_email)
                <div>{{ $sale->company_email }}</div>
            @endif
        </div>

        <hr>
        <div class="row"><span>{{ __('pos.receipt_number') }}</span><span>{{ $sale->reference }}</span></div>
        <div class="row">
            <span>{{ __('pos.date') }}</span>
            <span>{{ $sale->created_at->format(settings('date_format', 'd/m/Y') . ' H:i') }}</span>
        </div>
        @if ($sale->user)
            <div class="row"><span>{{ __('pos.cashier') }}</span><span>{{ $sale->user->name }}</span></div>
        @endif
        @if ($sale->customer)
            <div class="row"><span>{{ __('pos.customer') }}</span><span>{{ $sale->customer->name }}</span></div>
        @endif
        <hr>

        @foreach ($sale->items as $item)
            <div class="item">
                <div class="item-name">{{ $item->productColor->product->name ?? '' }}</div>
                @if ($item->productColor->variant_name)
                    <div class="muted">{{ $item->productColor->variant_name }}</div>
                @endif
                <div class="row">
                    <span>{{ $item->quantity }} × {{ $money($item->unit_price) }}</span>
                    <span>{{ $money($item->subtotal) }}</span>
                </div>
            </div>
        @endforeach

        <hr>
        @if ((float) $sale->discount > 0)
            <div class="row"><span>{{ __('pos.subtotal') }}</span><span>{{ $money($sale->total_brut) }}</span></div>
            <div class="row"><span>{{ __('pos.discount') }}</span><span>-{{ $money($sale->discount) }}</span></div>
        @endif
        <div class="row total"><span>{{ __('pos.total') }}</span><span>{{ $money($sale->total_net) }}</span></div>
        <hr>

        <div class="row">
            <span>{{ __('pos.paid_by') }}</span>
            <span>{{ __('pos.methods.' . $sale->payment_method) }}</span>
        </div>
        @if ($sale->amount_received !== null && $sale->payment_method === \App\Modules\Sale\Models\Sale::PAYMENT_CASH)
            <div class="row"><span>{{ __('pos.received') }}</span><span>{{ $money($sale->amount_received) }}</span></div>
            <div class="row"><span>{{ __('pos.change') }}</span><span>{{ $money($sale->change_due) }}</span></div>
        @endif

        <hr>
        <div class="center">{{ __('pos.thanks') }}</div>
    </div>

    <div class="actions">
        <button type="button" onclick="window.print()">{{ __('pos.print') }}</button>
        <button type="button" onclick="window.close()">{{ __('pos.close') }}</button>
    </div>

    @if (request()->boolean('print'))
        <script>
            window.addEventListener('load', () => window.print());
        </script>
    @endif
</body>

</html>
