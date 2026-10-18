{{--
    PDF d'un export de liste (App\Core\Exports\ListExport::toPdf).
    Variables : $title, $columns (Column), $rows, $totals (position => total formaté), $summary, $filters,
    $truncated (nombre de lignes affichées si la liste a été tronquée, sinon null).
--}}
@extends('layouts.pdf')

@section('title', $title)

@section('meta')
    {{ $rows->count() }} row(s)
    @if ($truncated)
        (first {{ number_format($truncated) }})
    @endif
@endsection

@section('content')
    @if ($filters || $summary)
        <div class="pdf-chips">
            @foreach ($summary as $label => $value)
                <span class="pdf-chip">{{ $label }} : <strong>{{ $value }}</strong></span>
            @endforeach
            @foreach ($filters as $label => $value)
                <span class="pdf-chip">{{ $label }} : <strong>{{ $value }}</strong></span>
            @endforeach
        </div>
    @endif

    <table class="pdf-table">
        <thead>
            <tr>
                @foreach ($columns as $column)
                    <th @class(['text-end' => $column->isNumeric()]) @if ($column->pdfWidth()) style="width: {{ $column->pdfWidth() }};" @endif>
                        {{ $column->label }}
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr @class(['is-even' => $loop->even])>
                    @foreach ($columns as $column)
                        <td @class(['text-end' => $column->isNumeric()])>{{ $column->display($row) }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($columns) }}" class="text-muted">No data.</td>
                </tr>
            @endforelse
        </tbody>
        @if ($totals && $rows->isNotEmpty())
            <tfoot>
                <tr>
                    @foreach ($columns as $index => $column)
                        <td @class(['text-end' => $column->isNumeric()])>
                            {{ $totals[$index] ?? ($loop->first ? 'Total' : '') }}
                        </td>
                    @endforeach
                </tr>
            </tfoot>
        @endif
    </table>

    @if ($truncated)
        <div class="pdf-note">
            The PDF is limited to the first {{ number_format($truncated) }} rows. Export to Excel to get the full list,
            or narrow it down with the filters.
        </div>
    @endif
@endsection
