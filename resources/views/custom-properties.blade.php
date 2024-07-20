@include('media-library::livewire.partials.collection.fields')


<div class="media-library-field">
    <label class="media-library-label">{{__("Description")}}</label>
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