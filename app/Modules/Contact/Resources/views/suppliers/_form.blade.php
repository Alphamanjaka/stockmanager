{{-- Champs communs à la création et à la modification d'un fournisseur ($supplier facultatif) --}}
<div class="row">
    <x-ui.field class="col-md-6" name="name" :label="__('Company name')" required maxlength="255"
        :value="old('name', $supplier->name ?? '')" />
    <x-ui.field class="col-md-6" name="phone" :label="__('Phone')" required
        :value="old('phone', $supplier->phone ?? '')" />
    <x-ui.field class="col-md-6" name="email" :label="__('Email')" type="email"
        :value="old('email', $supplier->email ?? '')" />
    <x-ui.field class="col-md-6" name="address" :label="__('Address')"
        :value="old('address', $supplier->address ?? '')" />
    <x-ui.field class="col-md-6" name="lead_time_days" :label="__('Delivery lead time (days)')" type="number" min="0" max="365"
        :placeholder="__('Default: :days', ['days' => settings('default_lead_time_days', 7)])"
        :hint="__('From the order to the delivery. Restocking orders enough to last until then.')"
        :value="old('lead_time_days', $supplier->lead_time_days ?? '')" />
    <x-ui.field class="col-12 mb-0" name="notes" :label="__('Notes')" type="textarea" rows="3"
        :hint="__('Internal notes: delivery days, payment terms, contact person...')"
        :value="old('notes', $supplier->notes ?? '')" />
</div>
