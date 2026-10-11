<div class="mb-3">
    <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
    <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror"
        value="{{ old('name', $brand->name ?? '') }}" required maxlength="255">
    @error('name')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
