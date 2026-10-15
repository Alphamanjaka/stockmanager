<div class="card shadow-sm">
    <div class="card-header bg-white fw-bold">Interface & System</div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label" for="app_locale">Language</label>
                <select class="form-select" id="app_locale" name="app_locale">
                    @foreach (config('app.supported_locales') as $locale => $language)
                        <option value="{{ $locale }}" {{ app()->getLocale() === $locale ? 'selected' : '' }}>{{ $language }}</option>
                    @endforeach
                </select>
                <div class="form-text">Applies to the whole application, for every user.</div>
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label" for="theme_mode">Theme</label>
                <select class="form-select" id="theme_mode" name="theme_mode">
                    @foreach (['auto' => 'Automatic (follows the system)', 'light' => 'Light', 'dark' => 'Dark'] as $mode => $label)
                        <option value="{{ $mode }}" {{ ($settings['theme_mode'] ?? 'auto') === $mode ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Pagination (items per page)</label>
            <select class="form-select" name="pagination_per_page">
                <option value="10" {{ ($settings['pagination_per_page'] ?? '') == '10' ? 'selected' : '' }}>10</option>
                <option value="15" {{ ($settings['pagination_per_page'] ?? '') == '15' ? 'selected' : '' }}>15</option>
                <option value="25" {{ ($settings['pagination_per_page'] ?? '') == '25' ? 'selected' : '' }}>25</option>
                <option value="50" {{ ($settings['pagination_per_page'] ?? '') == '50' ? 'selected' : '' }}>50</option>
            </select>
        </div>
    </div>
</div>
