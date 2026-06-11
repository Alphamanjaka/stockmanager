<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>list Catalogues export pdf</title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
        }

        th {
            background-color: #f2f2f2;
        }
    </style>
</head>

<body>
    <h1>Liste des catalogues</h1>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>category</th>
                <th>Price</th>
                <th>Stock Quantity</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($products as $item)
                <tr>
                    <td>{{ $item->id }}</td>
                    <td>{{ $item->product ? $item->product->xname : 'N/A' }}</td>
                    <td>{{ $item->getCategoryNameAttribute()}}</td>
                    <td>{{ number_format($item->price, 2) }} MGA</td>
                    <td>{{ $item->stock }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

</body>

</html>
