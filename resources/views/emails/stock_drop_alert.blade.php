<!DOCTYPE html>
<html>

<head>
    <title>Alerte Stock</title>
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
    <h2>Alerte de sécurité - Variation de Stock</h2>
    <p>Bonjour,</p>
    <p>Notre système de surveillance a détecté une <span class="alert">chute anormale</span> de la valorisation globale
        du stock.</p>

    <div class="data-box">
        <p>📉 <strong>Variation détectée : {{ number_format($dropPercentage, 2) }}%</strong></p>
        <p>Valeur Hier : {{ number_format($previousValue, 2) }} €</p>
        <p>Valeur Actuelle : <strong>{{ number_format($currentValue, 2) }} €</strong></p>
    </div>

    <p>Veuillez vérifier les mouvements récents (vols, pertes, ou erreurs de saisie) immédiatement.</p>
    <p><a href="{{ route('admin.dashboard') }}" class="btn">Accéder au Dashboard</a></p>
</body>

</html>
