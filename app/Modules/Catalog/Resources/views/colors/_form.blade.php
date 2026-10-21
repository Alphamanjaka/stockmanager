{{-- Champs communs à la création et à la modification d'une couleur ($item facultatif) --}}
<div class="row">
    <x-ui.field class="col-md-6" name="name" :label="__('Color name')" required :placeholder="__('e.g. Bright red')"
        :value="old('name', $item->name ?? '')" />

    <x-ui.field class="col-md-6 mb-0" name="code" :label="__('Color code')" :hint="__('Hex or CSS color. You can also use the picker.')">
        <div class="input-group">
            <input type="text" name="code" id="code" placeholder="{{ __('e.g. #FF0000') }}"
                value="{{ old('code', $item->code ?? '') }}" @class(['form-control', 'is-invalid' => $errors->has('code')])>
            <input type="color" class="form-control form-control-color" aria-label="{{ __('Pick a color') }}"
                value="{{ preg_match('/^#[0-9a-f]{6}$/i', old('code', $item->code ?? '')) ? old('code', $item->code ?? '') : '#000000' }}"
                oninput="document.getElementById('code').value = this.value">
        </div>
    </x-ui.field>
</div>
