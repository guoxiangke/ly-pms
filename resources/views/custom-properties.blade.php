<div @class(['media-library-field', 'media-library-hidden' => ! $editableName])>
    @if($editableName)
        <label class="media-library-label">{{ __("Name") }}</label>
        <input
            disabled
            dusk="media-library-field-name"
            class="media-library-input"
            type="text"
            name="{{ $mediaItem->propertyAttributeName('name') }}"
            value="{{ $mediaItem->name }}"
            wire:model="media.{{ $mediaItem->uuid }}.name"
        />
    @endif

    @error($mediaItem->propertyErrorName('name'))
        <p class="media-library-field-error">
               {{ $message }}
        </p>
    @enderror
</div>


<div class="media-library-field">
    <label class="media-library-label">Description</label>
    <input
        class="media-library-input bg-slate-100"
        style="background-color: rgb(241 245 249);"
        type="text"
        placeholder="提交后不可改动！"
        {{ $mediaItem->livewireCustomPropertyAttributes('description') }}
    />

    @error($mediaItem->customPropertyErrorName('description'))
    <span class="media-library-text-error">
       {{ $message }}
    </span>
    @enderror
</div>