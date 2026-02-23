<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Facture {{ $sale->reference }}</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 14px;
        }

        .header {
            text-align: right;
            margin-bottom: 50px;
        }

        .company-info {
            float: left;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }

        th {
            background-color: #f4f4f4;
        }

        .total-section {
            margin-top: 30px;
            float: right;
            width: 300px;
        }

        .text-right {
            text-align: right;
        }

        .text-success {
            color: green;
            font-size: 18px;
        }
    </style>
</head>

<body>
    <div class="company-info">
        <h2>StockMaster Pro</h2>
        <p>Expert en Gestion de Stock</p>
    </div>

    <div class="header">
        <h1>FACTURE</h1>
        <p>Reference : {{ $sale->reference }}</p>
        <p>Date : {{ $sale->created_at->format('d/m/Y') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Designation</th>
                <th>Qté</th>
                <th>PU</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($sale->items as $item)
                <tr>
                    <td>{{ $item->product->name }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ number_format($item->unit_price, 2) }} €</td>
                    <td>{{ number_format($item->subtotal, 2) }} €</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="total-section">
        <table>
            <tr>
                <td>Total Gross</td>
                <td class="text-right">{{ number_format($sale->total_brut, 2) }} €</td>
            </tr>
            <tr>
                <td>Discount</td>
                <td class="text-right">-{{ number_format($sale->discount, 2) }} €</td>
            </tr>
            <tr>
                <td><strong>TOTAL NET</strong></td>
                <td class="text-right text-success"><strong>{{ number_format($sale->total_net, 2) }} €</strong></td>
            </tr>
        </table>
    </div>
</body>

</html>
