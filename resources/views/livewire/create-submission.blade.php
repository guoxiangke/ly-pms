<div class="mt-12 flex-grow w-full max-w-4xl mx-auto p-16 bg-white rounded shadow-xl">
    <x-h2>用户: {{$user->name??'Annous'}} </x-h2>

    <form method="POST" wire:submit.prevent="submit">
        <div class="bg-teal-100 border-t-4 border-teal-500 rounded-b text-teal-900 px-4 py-3 shadow-md" role="alert">
          <div class="flex">
            <div class="py-1 mr-2"><svg class="fill-current h-6 w-6 text-teal-500 mr-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M2.93 17.07A10 10 0 1 1 17.07 2.93 10 10 0 0 1 2.93 17.07zm12.73-1.41A8 8 0 1 0 4.34 4.34a8 8 0 0 0 11.32 11.32zM9 11V9h2v6H9v-4zm0-6h2v2H9V5z"/></svg></div>
            <div>
              <p class="text-sm">1. 上传节目音频注意：<br/>本页面只供提交未上传过的节目音频。如节目音频早前已上传，但需要更换，请把新的音频发到program@liangyou.net，通知中文事工办公室同工。<br/>2. 可以同时上传多个节目音频，惟不可超出音频大小上限512M。<br/>3. 节目音频格式须为64 kbps、48 kHz、mono、mp3。<br/>4. 节目音频档名须为xxxyymmdd.mp3（xxx为节目代号，yymmdd为播出日期的年年月月日日；良院或指定节目除外）。<br/>5. 节目音频和有关的节目简介，须同时提交。<br/>6. 提交节目简介后如需要修改，请发电邮到program@liangyou.net通知中文事工办公室同工。</p>

            </div>
          </div>
        </div>

        @if($hasNewFile)
        <div class="bg-teal-100 border-t-4 border-teal-500 rounded-b text-teal-900 px-4 py-3 shadow-md" role="alert">
          <div class="flex">
            <div class="py-1 mr-2"><svg class="fill-current h-6 w-6 text-red-500 mr-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M2.93 17.07A10 10 0 1 1 17.07 2.93 10 10 0 0 1 2.93 17.07zm12.73-1.41A8 8 0 1 0 4.34 4.34a8 8 0 0 0 11.32 11.32zM9 11V9h2v6H9v-4zm0-6h2v2H9V5z"/></svg></div>
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
        
        <div class="p-4">
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
        </div>
        <x-button dusk="submit" type="submit">提交</x-button>
    </form>
</div>
