<x-ui.card title="Interface & system" subtitle="Language, theme and list size, for every user">
    <div class="row">
        <x-ui.field class="col-md-6" name="app_locale" label="Language" hint="Applies to the whole application, for every user.">
            <select class="form-select" id="app_locale" name="app_locale">
                @foreach (config('app.supported_locales') as $locale => $language)
                    <option value="{{ $locale }}" @selected(app()->getLocale() === $locale)>{{ $language }}</option>
                @endforeach
            </select>
        </x-ui.field>

        <x-ui.field class="col-md-6" name="theme_mode" label="Theme">
            <select class="form-select" id="theme_mode" name="theme_mode">
                @foreach (['auto' => 'Automatic (follows the system)', 'light' => 'Light', 'dark' => 'Dark'] as $mode => $label)
                    <option value="{{ $mode }}" @selected(($settings['theme_mode'] ?? 'auto') === $mode)>{{ $label }}</option>
                @endforeach
            </select>
        </x-ui.field>

        <x-ui.field class="col-md-6 mb-0" name="pagination_per_page" label="Items per page">
            <select class="form-select" id="pagination_per_page" name="pagination_per_page">
                @foreach ([10, 15, 25, 50] as $size)
                    <option value="{{ $size }}" @selected(($settings['pagination_per_page'] ?? '') == $size)>{{ $size }}</option>
                @endforeach
            </select>
        </x-ui.field>
    </div>
</x-ui.card>
