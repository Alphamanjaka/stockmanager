<!DOCTYPE html>
<html>

<head>
    <title>Stock Alert</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }

        .alert {
            color: #d9534f;
            font-weight: bold;
            font-size: 1.2em;
        }

        .data-box {
            background: #f8f9fa;
            padding: 15px;
            border-left: 4px solid #d9534f;
            margin: 20px 0;
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #0275d8;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
    </style>
</head>

<body>
    <h2>Stock Alert - Significant Change</h2>
    <p>Bonjour,</p>
    <p>Our monitoring system detected an <span class="alert">abnormal drop</span> in the overall stock valuation.</p>

    <div class="data-box">
        <p>📉 <strong>Variation détectée : {{ number_format($dropPercentage, 2) }}%</strong></p>
        <p>Valeur Hier : {{ number_format($previousValue, 2) }} €</p>
        <p>Valeur Actuelle : <strong>{{ number_format($currentValue, 2) }} €</strong></p>
    </div>

    <p>Please review recent movements (theft, losses, or data entry errors) immediately.</p>
    <p><a href="{{ route('admin.dashboard') }}" class="btn">Go to Dashboard</a></p>
</body>

</html>
