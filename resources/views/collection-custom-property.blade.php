<x-app-layout>
<div class="mt-12 flex-grow w-full max-w-4xl mx-auto p-16 bg-white rounded shadow-xl">
    <x-h2>Blade: collection with custom property</x-h2>

    <form method="POST">
        <x-grid>
            @csrf

            <x-field label="title">
                <x-input id="title" name="title" placeholder="Description or friendly title" value="{{ old('title', $fileSubmission->title) }}"  />
            </x-field>
            <x-field label="Mp3">
                <x-media-library-collection
                    name="mp3"
                    :model="$fileSubmission"
                    collection="mp3"
                    rules="mimes:mp3"
                    fields-view="custom-properties"
                />
            </x-field>

            <x-button dusk="submit" type="submit">Submit</x-button>
        </x-grid>
    </form>
</div>
</x-app-layout>
