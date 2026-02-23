<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Purchase {{ $purchase->reference }}</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #333;
            font-size: 12px;
        }

        .container {
            width: 100%;
            margin: 0 auto;
        }

        .header {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }

        .header-left,
        .header-right {
            display: table-cell;
            vertical-align: top;
        }

        .header-right {
            text-align: right;
        }

        h1 {
            color: #007bff;
            font-size: 24px;
            margin: 0;
        }

        h2 {
            font-size: 16px;
            color: #555;
            border-bottom: 1px solid #ccc;
            padding-bottom: 5px;
            margin-top: 30px;
            margin-bottom: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }

        .text-right {
            text-align: right;
        }

        .totals {
            width: 40%;
            margin-left: 60%;
        }

        .totals td {
            border: none;
        }

        .totals .total-net {
            font-weight: bold;
            background-color: #f2f2f2;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <div class="header-left">
                <h1>PURCHASE ORDER</h1>
                <strong>Reference :</strong> {{ $purchase->reference }}<br>
                <strong>Date :</strong> {{ $purchase->created_at->format('d/m/Y') }}
            </div>
            <div class="header-right">
                <strong>Supplier :</strong><br>
                {{ $purchase->supplier->name }}<br>
                {{ $purchase->supplier->email }}<br>
                {{ $purchase->supplier->phone }}
            </div>
        </div>

        <h2>Product Details</h2>
        <table>
            <thead>
                <tr>
                    <th>Product</th>
                    <th class="text-right">Quantity</th>
                    <th class="text-right">Unit Cost</th>
                    <th class="text-right">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($purchase->items as $item)
                    <tr>
                        <td>{{ $item->product->name }}</td>
                        <td class="text-right">{{ $item->quantity }}</td>
                        <td class="text-right">{{ number_format($item->unit_price, 2, ',', ' ') }} Mga</td>
                        <td class="text-right">{{ number_format($item->subtotal, 2, ',', ' ') }} Mga</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <table class="totals">
            <tr>
                <td>Total Brut</td>
                <td class="text-right">{{ number_format($purchase->total_amount, 2, ',', ' ') }} Mga</td>
            </tr>
            <tr>
                <td>Discount</td>
                <td class="text-right">-{{ number_format($purchase->discount, 2, ',', ' ') }} Mga</td>
            </tr>
            <tr class="total-net">
                <td>Total Net</td>
                <td class="text-right">{{ number_format($purchase->total_net, 2, ',', ' ') }} Mga</td>
            </tr>
        </table>
    </div>
</body>

</html>
