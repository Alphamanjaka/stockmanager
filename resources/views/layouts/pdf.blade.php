{{--
    Gabarit commun des documents PDF (dompdf) : en-tête de l'entreprise (Paramètres > Général),
    titre du document, pied de page (numéros de page : ListExport::numberPages($pdf), après le rendu).
    Utilisé par les exports de liste (core::exports.list).

        @extends('layouts.pdf')
        @section('title', 'Products')
        @section('meta') …bloc à droite de l'en-tête (référence, date)… @endsection
        @section('content') … @endsection
        @push('styles') …styles propres au document… @endpush

    Les couleurs sont écrites ici : dompdf ne lit pas les jetons CSS de l'application (tokens.css).
    Police DejaVu Sans (fournie avec dompdf) : accents, symboles et devises s'affichent correctement.
--}}
@php
    $company = [
        'name' => settings('company_name') ?: config('app.name'),
        'address' => settings('company_address'),
        'phone' => settings('company_phone'),
        'email' => settings('company_email'),
    ];
    // Logo : fichier local uniquement (dompdf ne télécharge pas), formats lus par dompdf
    $logo = settings('company_logo');
    $logoPath = $logo && preg_match('/\.(png|jpe?g|gif)$/i', $logo) ? storage_path('app/public/' . $logo) : null;
    $logoPath = $logoPath && is_file($logoPath) ? $logoPath : null;
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <title>@yield('title')</title>
    <style>
        @page {
            margin: 14mm 12mm 16mm;
        }

        body {
            margin: 0;
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 8.5pt;
            line-height: 1.35;
            color: #1d2733;
        }

        /* En-tête : entreprise à gauche, titre et informations du document à droite */
        .pdf-header {
            width: 100%;
            margin-bottom: 5mm;
            padding-bottom: 3mm;
            border-bottom: 2px solid #0f5ba7;
            border-collapse: collapse;
        }

        .pdf-header td {
            vertical-align: top;
            padding: 0;
        }

        .pdf-logo {
            max-height: 14mm;
            max-width: 40mm;
            margin-bottom: 2mm;
        }

        .pdf-company-name {
            font-size: 12pt;
            font-weight: bold;
            color: #0f2742;
        }

        .pdf-company-line {
            color: #5b6878;
        }

        .pdf-title {
            font-size: 15pt;
            font-weight: bold;
            color: #0f2742;
            text-align: right;
        }

        .pdf-meta {
            text-align: right;
            color: #5b6878;
        }

        /* Pied de page répété sur chaque page */
        .pdf-footer {
            position: fixed;
            bottom: -10mm;
            left: 0;
            right: 0;
            height: 6mm;
            padding-top: 1.5mm;
            border-top: 1px solid #d9dfe7;
            font-size: 7pt;
            color: #8592a3;
        }

        /* Tableaux */
        .pdf-table {
            width: 100%;
            border-collapse: collapse;
        }

        .pdf-table th {
            padding: 2mm 1.8mm;
            background: #0f2742;
            color: #ffffff;
            font-size: 7.5pt;
            font-weight: bold;
            text-align: left;
        }

        .pdf-table td {
            padding: 1.6mm 1.8mm;
            border-bottom: 1px solid #e6eaf0;
            vertical-align: top;
        }

        /* Lignes alternées par une classe (<tr class="is-even">) : dompdf évalue :nth-child très lentement */
        .pdf-table tr.is-even td {
            background: #f6f8fb;
        }

        .pdf-table tfoot td {
            border-top: 1.5px solid #0f2742;
            border-bottom: none;
            font-weight: bold;
        }

        .text-end {
            text-align: right;
            white-space: nowrap;
        }

        .text-muted {
            color: #8592a3;
        }

        /* Filtres appliqués et chiffres clés, sous l'en-tête */
        .pdf-chips {
            margin-bottom: 4mm;
        }

        .pdf-chip {
            display: inline-block;
            margin: 0 1.5mm 1.5mm 0;
            padding: 0.8mm 2mm;
            border: 1px solid #d9dfe7;
            border-radius: 2mm;
            background: #f6f8fb;
            font-size: 7.5pt;
        }

        .pdf-chip strong {
            color: #0f2742;
        }

        .pdf-note {
            margin-top: 4mm;
            padding: 2mm 3mm;
            border-left: 2px solid #c27c0e;
            background: #fdf6e7;
            font-size: 7.5pt;
        }
    </style>
    @stack('styles')
</head>

<body>
    {{-- « Page x / y » est ajouté à droite après le rendu (ListExport::numberPages) --}}
    <div class="pdf-footer">
        {{ $company['name'] }} · Generated on {{ now()->format('d/m/Y H:i') }}@auth by {{ auth()->user()->name }}@endauth
    </div>

    <table class="pdf-header">
        <tr>
            <td style="width: 55%;">
                @if ($logoPath)
                    <img src="{{ $logoPath }}" class="pdf-logo" alt="">
                @endif
                <div class="pdf-company-name">{{ $company['name'] }}</div>
                @foreach (['address', 'phone', 'email'] as $line)
                    @if ($company[$line])
                        <div class="pdf-company-line">{{ $company[$line] }}</div>
                    @endif
                @endforeach
            </td>
            <td>
                <div class="pdf-title">@yield('title')</div>
                <div class="pdf-meta">@yield('meta')</div>
            </td>
        </tr>
    </table>

    @yield('content')
</body>

</html>
