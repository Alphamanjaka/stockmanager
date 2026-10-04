<div class="card shadow-sm">
    <div class="card-header bg-white fw-bold">Interface & System</div>
    <div class="card-body">
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