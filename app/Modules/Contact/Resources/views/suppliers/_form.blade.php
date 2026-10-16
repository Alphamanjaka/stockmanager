{{-- Champs communs à la création et à la modification d'un fournisseur ($supplier facultatif) --}}
<div class="row">
    <x-ui.field class="col-md-6" name="name" label="Company name" required maxlength="255"
        :value="old('name', $supplier->name ?? '')" />
    <x-ui.field class="col-md-6" name="phone" label="Phone" required
        :value="old('phone', $supplier->phone ?? '')" />
    <x-ui.field class="col-md-6" name="email" label="Email" type="email"
        :value="old('email', $supplier->email ?? '')" />
    <x-ui.field class="col-md-6" name="address" label="Address"
        :value="old('address', $supplier->address ?? '')" />
    <x-ui.field class="col-12 mb-0" name="notes" label="Notes" type="textarea" rows="3"
        hint="Internal notes: delivery days, payment terms, contact person..."
        :value="old('notes', $supplier->notes ?? '')" />
</div>
