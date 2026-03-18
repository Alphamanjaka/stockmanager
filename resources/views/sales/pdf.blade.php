<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="utf-8">
    <title>Facture {{ $sale->reference }}</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 14px;
            color: #333;
            line-height: 1.4;
        }

        .header {
            width: 100%;
            margin-bottom: 40px;
            border-bottom: 1px solid #eee;
            padding-bottom: 20px;
        }

        .company-info {
            float: left;
            width: 50%;
        }

        .company-name {
            font-size: 20px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .invoice-details {
            float: right;
            width: 40%;
            text-align: right;
        }

        .invoice-title {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 10px;
            text-transform: uppercase;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            margin-bottom: 20px;
        }

        th {
            background-color: #2c3e50;
            color: white;
            padding: 10px;
            text-align: left;
            font-size: 12px;
        }

        td {
            padding: 10px;
            border-bottom: 1px solid #eee;
            font-size: 13px;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .totals {
            float: right;
            width: 40%;
            margin-top: 10px;
        }

        .total-row {
            padding: 5px 0;
            border-bottom: 1px solid #eee;
        }

        .total-row.final {
            font-size: 16px;
            font-weight: bold;
            color: #2c3e50;
            border-top: 2px solid #2c3e50;
            border-bottom: none;
            margin-top: 5px;
            padding-top: 10px;
        }

        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 10px;
            color: #95a5a6;
            border-top: 1px solid #eee;
            padding-top: 10px;
        }

        .clearfix:after {
            content: "";
            display: table;
            clear: both;
        }
    </style>
</head>

<body>
    @php
        // Récupération des réglages depuis la base de données
        $settings = \Illuminate\Support\Facades\DB::table('settings')->pluck('value', 'key');
        $currency = $settings['currency_symbol'] ?? 'Mga';
    @endphp

    <div class="header clearfix">
        <div class="company-info">
            @if(!empty($settings['company_logo']))
                <div style="margin-bottom: 10px;">
                    <img src="{{ public_path('storage/' . $settings['company_logo']) }}" alt="Logo" style="max-height: 60px;">
                </div>
            @endif
            <div class="company-name">{{ $settings['company_name'] ?? 'Nom Société' }}</div>
            <div>{{ $settings['company_address'] ?? '' }}</div>
            @if (!empty($settings['company_phone']))
                <div>Tél: {{ $settings['company_phone'] }}</div>
            @endif
            @if (!empty($settings['company_email']))
                <div>Email: {{ $settings['company_email'] }}</div>
            @endif
        </div>
        <div class="invoice-details">
            <div class="invoice-title">Facture</div>
            <div>Réf: <strong>{{ $sale->reference }}</strong></div>
            <div>Date: {{ $sale->created_at->format('d/m/Y') }}</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Produit</th>
                <th class="text-center" width="15%">Quantité</th>
                <th class="text-right" width="20%">Prix Unitaire</th>
                <th class="text-right" width="20%">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($sale->items as $item)
                <tr>
                    <td>{{ $item->product->name }}</td>
                    <td class="text-center">{{ $item->quantity }}</td>
                    <td class="text-right">{{ number_format($item->unit_price, 2, ',', ' ') }}</td>
                    <td class="text-right">{{ number_format($item->subtotal, 2, ',', ' ') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="clearfix">
        <div class="totals">
            <div class="total-row clearfix">
                <span style="float:left">Total Brut</span>
                <span style="float:right">{{ number_format($sale->total_brut, 2, ',', ' ') }}
                    {{ $currency }}</span>
            </div>
            @if ($sale->discount > 0)
                <div class="total-row clearfix" style="color: #e74c3c;">
                    <span style="float:left">Remise</span>
                    <span style="float:right">- {{ number_format($sale->discount, 2, ',', ' ') }}
                        {{ $currency }}</span>
                </div>
            @endif
            <div class="total-row final clearfix">
                <span style="float:left">Net à Payer</span>
                <span style="float:right">{{ number_format($sale->total_net, 2, ',', ' ') }}
                    {{ $currency }}</span>
            </div>
        </div>
    </div>

    <div class="footer">
        {{ $settings['company_name'] ?? '' }}
        @if (!empty($settings['company_siret']))
            - SIRET: {{ $settings['company_siret'] }}
        @endif
        @if (!empty($settings['company_vat']))
            - TVA: {{ $settings['company_vat'] }}
        @endif
        <br>
        Document généré automatiquement le {{ date('d/m/Y H:i') }}
    </div>
</body>

</html>
