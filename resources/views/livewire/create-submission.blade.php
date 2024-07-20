<div class="mt-12 flex-grow w-full max-w-4xl mx-auto p-16 bg-white rounded shadow-xl">
    <x-h2>用户: {{$user->name??'Annous'}} </x-h2>

    <form method="POST" wire:submit.prevent="submit">
        <div class="bg-teal-100 border-t-4 border-teal-500 rounded-b text-teal-900 px-4 py-3 shadow-md" role="alert">
          <div class="flex">
            <div class="py-1 mr-2"><svg class="fill-current h-6 w-6 text-teal-500 mr-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M2.93 17.07A10 10 0 1 1 17.07 2.93 10 10 0 0 1 2.93 17.07zm12.73-1.41A8 8 0 1 0 4.34 4.34a8 8 0 0 0 11.32 11.32zM9 11V9h2v6H9v-4zm0-6h2v2H9V5z"/></svg></div>
            <div>
              <p class="font-bold">{{ $messageTitle }}</p>
              <p class="text-sm">{!! $message !!}</p>
                @if($errors->any())
                <ul class="mt-2 bg-red-100 px-3 py-2">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                @endif
            </div>
          </div>
        </div>
        
        <br>
        <br>
        <x-button dusk="submit" type="submit">提交</x-button>
        <br>
        <br>
        <x-field label="Mp3">
            <livewire:media-library
                wire:model="files"
                name="files"
                :model="$user"
                collection="mp3"

                rules="mimes:mp3"
                :accept="['audio/mpeg']"
                fields-view="custom-properties"
            />
        </x-field>

        <br>
        <br>
        @if($hasNewFile)
            <div class="bg-teal-100 border-t-4 border-teal-500 rounded-b text-teal-900 px-4 py-3 shadow-md" role="alert">
              <div class="flex">
                <div class="py-1 mr-2"><svg class="fill-current h-6 w-6 text-teal-500 mr-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M2.93 17.07A10 10 0 1 1 17.07 2.93 10 10 0 0 1 2.93 17.07zm12.73-1.41A8 8 0 1 0 4.34 4.34a8 8 0 0 0 11.32 11.32zM9 11V9h2v6H9v-4zm0-6h2v2H9V5z"/></svg></div>
                <div>
                  <p class="font-bold">{{ $messageTitle }}</p>
                  <p class="text-sm">{!! $message !!}</p>
                @if($errors->any())
                        <ul class="mt-1 bg-red-100 px-3 py-2 text-red-700">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                @endif
                </div>
              </div>
            </div>
        @endif
        <br>
        <x-button dusk="submit" type="submit">提交</x-button>
    </form>
</div>
