{{-- Champs communs à la création et à la modification d'un client ($customer facultatif) --}}
<div class="row">
    <x-ui.field class="col-md-6" name="name" :label="__('Name')" required :value="old('name', $customer->name ?? '')" />
    <x-ui.field class="col-md-6" name="phone" :label="__('Phone')" :value="old('phone', $customer->phone ?? '')" />
    <x-ui.field class="col-md-6" name="email" :label="__('Email')" type="email" :value="old('email', $customer->email ?? '')" />
    <x-ui.field class="col-md-6" name="address" :label="__('Address')" :value="old('address', $customer->address ?? '')" />
    <x-ui.field class="col-12 mb-0" name="notes" :label="__('Notes')" type="textarea" rows="3"
        :value="old('notes', $customer->notes ?? '')" />
</div>
