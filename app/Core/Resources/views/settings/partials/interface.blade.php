<x-ui.card :title="__('Interface & system')" :subtitle="__('Language, theme and list size, for every user')">
    <div class="row">
        <x-ui.field class="col-md-6" name="app_locale" :label="__('Language')" :hint="__('Applies to the whole application, for every user.')">
            <select class="form-select" id="app_locale" name="app_locale">
                @foreach (config('app.supported_locales') as $locale => $language)
                    <option value="{{ $locale }}" @selected(app()->getLocale() === $locale)>{{ $language }}</option>
                @endforeach
            </select>
        </x-ui.field>

        <x-ui.field class="col-md-6" name="theme_mode" :label="__('Theme')">
            <select class="form-select" id="theme_mode" name="theme_mode">
                @foreach (['auto' => __('Automatic (follows the system)'), 'light' => __('Light'), 'dark' => __('Dark')] as $mode => $label)
                    <option value="{{ $mode }}" @selected(($settings['theme_mode'] ?? 'auto') === $mode)>{{ $label }}</option>
                @endforeach
            </select>
        </x-ui.field>

        <x-ui.field class="col-md-6 mb-0" name="pagination_per_page" :label="__('Items per page')">
            <select class="form-select" id="pagination_per_page" name="pagination_per_page">
                @foreach ([10, 15, 25, 50] as $size)
                    <option value="{{ $size }}" @selected(($settings['pagination_per_page'] ?? '') == $size)>{{ $size }}</option>
                @endforeach
            </select>
        </x-ui.field>
    </div>
</x-ui.card>
