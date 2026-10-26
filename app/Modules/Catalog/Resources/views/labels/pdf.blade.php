{{--
    Planche d'étiquettes (LabelService). Chaque case est placée au millimètre près (LabelFormat::position) :
    les marges et écarts de la planche du commerce sont respectés, quelle que soit l'imprimante.
    $pages : cases de chaque page, null pour une case laissée vide (planche entamée).
--}}
@php
    [$width, $height] = $format['label'];
    $text = $format['text'];
    // Hauteurs de ligne en mm (1 pt = 0,3528 mm) : nom sur 2 lignes au plus
    $line = fn (float $size) => round($size * 0.3528 * 1.3, 2);
    $lineHeight = $line($text);
    // dompdf ignore box-sizing : la case est réduite de sa marge intérieure (1,5 mm en haut et en bas, 2 mm sur les côtés)
    $innerWidth = round($width - 4, 2);
    $innerHeight = round($height - 3, 2);
    // Marge blanche de 3 mm de chaque côté du code-barres : sans elle, le scanner ne le lit pas
    $barcodeWidth = round($innerWidth - 6, 2);
    // Nom coupé pour tenir sur 2 lignes (largeur moyenne d'un caractère : ~0,55 × la taille du texte)
    $nameLength = (int) floor($innerWidth / ($text * 0.3528 * 0.55)) * 2 - 4;
    // Le code-barres prend la place restante (au plus 40 % de l'étiquette)
    $used = ($company ? $line($text - 2) : 0) + $lineHeight * 2 + 1.2 + ($showPrice ? $line($text + 3) + 0.8 : 0) + $line($text - 1) + 0.5;
    $barcodeHeight = round(max(5, min($height * 0.4, $innerHeight - $used)), 2);
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('Labels') }}</title>
    <style>
        @page { margin: 0; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'DejaVu Sans', sans-serif; color: #000; }
        .page { position: relative; width: {{ $format['page'][0] }}mm; height: {{ $format['page'][1] }}mm; overflow: hidden; page-break-after: always; }
        .page:last-child { page-break-after: auto; }
        .label { position: absolute; width: {{ $innerWidth }}mm; height: {{ $innerHeight }}mm; padding: 1.5mm 2mm; overflow: hidden; text-align: center; }
        .company { font-size: {{ $text - 2 }}pt; line-height: {{ $line($text - 2) }}mm; height: {{ $line($text - 2) }}mm; color: #444; white-space: nowrap; overflow: hidden; }
        .name { font-size: {{ $text }}pt; line-height: {{ $lineHeight }}mm; height: {{ $lineHeight * 2 + 1.2 }}mm; overflow: hidden; }
                .price { font-size: {{ $text + 3 }}pt; line-height: {{ $line($text + 3) }}mm; font-weight: bold; margin: 0.4mm 0; white-space: nowrap; }
        .barcode { display: block; width: {{ $barcodeWidth }}mm; height: {{ $barcodeHeight }}mm; margin: 0 auto; }
        .code { font-size: {{ $text - 1 }}pt; line-height: {{ $line($text - 1) }}mm; letter-spacing: 0.5pt; }
    </style>
</head>
<body>
    @foreach ($pages as $cells)
        <div class="page">
            @foreach ($cells as $index => $label)
                @continue($label === null)
                @php $position = \App\Modules\Catalog\Support\LabelFormat::position($format, $index); @endphp
                <div class="label" style="top: {{ $position['top'] }}mm; left: {{ $position['left'] }}mm;">
                    @if ($company)
                        <div class="company">{{ $company }}</div>
                    @endif
                    <div class="name">{{ \Illuminate\Support\Str::limit($label->variant ? "{$label->name} · {$label->variant}" : $label->name, $nameLength) }}</div>
                    @if ($showPrice)
                        <div class="price">{{ money($label->price, fmod($label->price, 1) == 0 ? 0 : 2, $currency) }}</div>
                    @endif
                    <img class="barcode" src="{{ $label->image }}" alt="">
                    <div class="code">{{ $label->code }}</div>
                </div>
            @endforeach
        </div>
    @endforeach
</body>
</html>
