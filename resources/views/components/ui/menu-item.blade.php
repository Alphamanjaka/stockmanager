{{--
    Élément de <x-ui.actions-menu> : lien (href) ou action envoyée en formulaire (action).

        <x-ui.menu-item :href="route('admin.brands.edit', $brand)" icon="bi-pencil">Edit</x-ui.menu-item>
        <x-ui.menu-item :action="route('admin.brands.archive')" :ids="[$brand->id]" icon="bi-archive"
            confirm="Archive this brand?">Archive</x-ui.menu-item>
        <x-ui.menu-item :action="route('admin.brands.destroy', $brand)" method="DELETE" icon="bi-trash3" tone="danger"
            confirm="Delete this brand permanently?">Delete permanently</x-ui.menu-item>

    ids     : identifiants envoyés en ids[] (actions d'archivage : App\Core\Http\Controllers\Concerns\ArchivesRecords)
    confirm : question posée avant l'envoi (resources/js/ui.js) ; tone="danger" : action destructrice, en rouge
--}}
@props([
    'href' => null,
    'action' => null,
    'method' => 'POST',
    'ids' => [],
    'icon' => null,
    'confirm' => null,
    'confirmText' => null,
    'tone' => null,
    'disabled' => false,
    'title' => null,
])

@php $danger = $tone === 'danger'; @endphp

<li>
    @if ($action)
        <form action="{{ $action }}" method="POST" class="m-0">
            @csrf
            @if (strtoupper($method) !== 'POST')
                @method($method)
            @endif
            @foreach ($ids as $id)
                <input type="hidden" name="ids[]" value="{{ $id }}">
            @endforeach
            <button type="submit" {{ $attributes->class(['dropdown-item', 'text-danger' => $danger]) }} @disabled($disabled)
                @if ($title) title="{{ $title }}" @endif
                @if ($confirm) data-confirm="{{ $confirm }}" data-confirm-ok="{{ trim(strip_tags($slot)) }}" @endif
                @if ($confirmText) data-confirm-text="{{ $confirmText }}" @endif
                @if ($danger) data-confirm-tone="danger" @endif>
                @if ($icon)
                    <i class="bi {{ $icon }}" aria-hidden="true"></i>
                @endif
                {{ $slot }}
            </button>
        </form>
    @else
        <a href="{{ $disabled ? '#' : $href }}" {{ $attributes->class(['dropdown-item', 'text-danger' => $danger, 'disabled' => $disabled]) }}
            @if ($title) title="{{ $title }}" @endif @if ($disabled) aria-disabled="true" @endif>
            @if ($icon)
                <i class="bi {{ $icon }}" aria-hidden="true"></i>
            @endif
            {{ $slot }}
        </a>
    @endif
</li>
