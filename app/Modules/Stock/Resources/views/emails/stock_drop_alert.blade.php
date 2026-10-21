<!DOCTYPE html>
<html>

<head>
    <title>Pertes de stock</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }

        .alert {
            color: #d9534f;
            font-weight: bold;
        }

        .data-box {
            background: #f8f9fa;
            padding: 15px;
            border-left: 4px solid #d9534f;
            margin: 20px 0;
        }

        table {
            border-collapse: collapse;
            width: 100%;
            margin: 20px 0;
        }

        th,
        td {
            border-bottom: 1px solid #dee2e6;
            padding: 6px 8px;
            text-align: left;
        }

        .num {
            text-align: right;
            white-space: nowrap;
        }

        .muted {
            color: #6c757d;
            font-size: 0.9em;
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
    <h2>Pertes de stock non expliquées</h2>
    <p>Bonjour,</p>
    <p>
        Sur les {{ $hours }} dernières heures, des sorties de stock <span class="alert">sans vente</span> ont été
        enregistrées : corrections en sortie et écarts d'inventaire négatifs.
    </p>

    <div class="data-box">
        <p>📉 <strong>Valeur perdue : {{ money($lossValue) }}</strong></p>
        <p>Soit {{ number_format($lossPercentage, 2) }} % de la valeur du stock.</p>
        <p class="muted">Valorisation au coût d'achat, ou au prix de vente quand le coût n'est pas renseigné.</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Produit</th>
                <th class="num">Quantité</th>
                <th>Motif</th>
                <th class="num">Valeur</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($lines as $line)
                <tr>
                    <td class="num">{{ $line['date']->format('d/m H:i') }}</td>
                    <td>
                        {{ $line['label'] }}
                        @if ($line['sku'])
                            <div class="muted">{{ $line['sku'] }}</div>
                        @endif
                    </td>
                    <td class="num">{{ $line['quantity'] }}</td>
                    <td>{{ $line['reason'] }}</td>
                    <td class="num">{{ money($line['value']) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p>Vérifiez ces mouvements : vol, casse, erreur de saisie ou de comptage.</p>
    <p><a href="{{ route('admin.movements.index') }}" class="btn">Voir les mouvements de stock</a></p>
</body>

</html>
