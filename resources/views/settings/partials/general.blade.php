<div class="card shadow-sm">
    <div class="card-header bg-white fw-bold">Company Information</div>
    <div class="card-body">
        <div class="mb-3">
            <label class="form-label">Nom du Magasin / Entreprise</label>
            <input type="text" class="form-control" name="company_name" value="{{ $settings['company_name'] ?? '' }}">
        </div>

        <div class="mb-3">
            <label class="form-label">Logo</label>
            <input type="file" class="form-control" name="company_logo">
            @if (!empty($settings['company_logo']))
                <div class="mt-2">
                    <img src="{{ asset('storage/' . $settings['company_logo']) }}" alt="Logo actuel" style="max-height: 50px;">
                </div>
            @endif
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Contact email</label>
                <input type="email" class="form-control" name="company_email" value="{{ $settings['company_email'] ?? '' }}">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Phone</label>
                <input type="text" class="form-control" name="company_phone" value="{{ $settings['company_phone'] ?? '' }}">
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Adresse</label>
            <textarea class="form-control" name="company_address" rows="2">{{ $settings['company_address'] ?? '' }}</textarea>
        </div>

        <hr>
        <h6 class="text-muted mb-3">Tax Identifiers</h6>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Numéro SIRET</label>
                <input type="text" class="form-control" name="company_siret" value="{{ $settings['company_siret'] ?? '' }}">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">TVA Intracommunautaire</label>
                <input type="text" class="form-control" name="company_vat" value="{{ $settings['company_vat'] ?? '' }}">
            </div>
        </div>
    </div>
</div>