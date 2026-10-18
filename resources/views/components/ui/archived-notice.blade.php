{{--
    Bandeau des archives : sur une liste filtrée sur status=archived (sans argument),
    ou sur la fiche d'un élément archivé (:restore = route de restauration, :id = élément).

        <x-ui.archived-notice />
        <x-ui.archived-notice :restore="route('admin.suppliers.restore')" :id="$supplier->id" :date="$supplier->archived_at">
            This supplier is archived: it is no longer offered in purchase forms.
        </x-ui.archived-notice>
--}}
@props(['restore' => null, 'id' => null, 'date' => null])

<x-ui.alert tone="warning" icon="bi-archive">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span>
            @if ($slot->isNotEmpty())
                {{ $slot }}
            @else
                You are viewing <strong>archived</strong> items. They are hidden everywhere else; restore one to use it again.
            @endif
            @if ($date)
                <span class="text-muted">(archived on {{ $date->format('d/m/Y') }})</span>
            @endif
        </span>
        @if ($restore && $id)
            <form action="{{ $restore }}" method="POST" class="m-0">
                @csrf
                <input type="hidden" name="ids[]" value="{{ $id }}">
                <button type="submit" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> Restore
                </button>
            </form>
        @else
            <a href="{{ request()->fullUrlWithoutQuery(['status', 'page']) }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-list-ul" aria-hidden="true"></i> Back to active items
            </a>
        @endif
    </div>
</x-ui.alert>
