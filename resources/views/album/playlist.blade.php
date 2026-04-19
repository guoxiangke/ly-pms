<x-player-layout :title="$album->name">
    <nav class="border-gray-200 bg-gray-50 dark:bg-gray-100 dark:border-gray-50">
      <div class="max-w-screen-xl flex flex-wrap items-center justify-between m-8 py-4" style="margin-top: 0;">
        <a href="#" class="">
            <img src="{{asset('logo.png')}}" class="h-10" alt="LY Logo" />
        </a>
      </div>
    </nav>
  <main class="m-8">

    <details open>
      <summary class="cursor-pointer text-2xl font-extrabold tracking-tight text-slate-900">
        {{$album->name}}
        <span class="text-sm font-medium ml-2 text-gray-500">{{$playlist->count()}} episodes</span>
      </summary>
      <div class="mt-2 bg-gray-50 text-gray-600">
        <div class="p-4">
          <div class="text-gray-900">
            <p class="leading-7">{{$album->description}}</p>
            @if($album->is_rrule_mode)
            <p class="mt-1 text-sm text-gray-500">Mode: RRule | Target: {{$album->target?->name}} ({{$album->target?->code}})</p>
            @else
            <p class="mt-1 text-sm text-gray-500">Mode: Manual Clips</p>
            @endif
          </div>
        </div>
      </div>
    </details>

    @if($playlist->count() === 0)
        <div class="text-lg p-4 text-gray">Playlist is empty...</div>
    @else
    <div>
      @php
        $first = $playlist->first();
      @endphp
      <audio class="hidden" id="audio" data-id="0" controls src='{{$first->path}}'></audio>

      <div class="flex flex-nowrap audio-player my-4">
        <div class="p-4" style="padding-right: 0;">
          @if($album->avatar)
          <img id="albumCover" src="{{$album->avatar}}" style="max-width: 150px;" class="rounded-lg bg-slate-100 pt-1" loading="lazy">
          @elseif($album->target)
          <img id="albumCover" src="{{$album->target->cover}}" style="max-width: 150px;" class="rounded-lg bg-slate-100 pt-1" loading="lazy">
          @else
          <img id="albumCover" src="{{$first->lyItem->ly_meta->cover ?? ''}}" style="max-width: 150px;" class="rounded-lg bg-slate-100 pt-1" loading="lazy">
          @endif
          <div class="gap-1 flex items-center justify-center mt-3">
            <button class="prev" type="button" aria-label="Previous">
              <svg width="24" height="24" fill="none">
                <path d="m10 12 8-6v12l-8-6Z" fill="currentColor" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                <path d="M6 6v12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
              </svg>
            </button>
            <button class="rewind" type="button" aria-label="Rewind 10 seconds">
              <svg width="24" height="24" fill="none">
                <path d="M6.492 16.95c2.861 2.733 7.5 2.733 10.362 0 2.861-2.734 2.861-7.166 0-9.9-2.862-2.733-7.501-2.733-10.362 0A7.096 7.096 0 0 0 5.5 8.226" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                <path d="M5 5v3.111c0 .491.398.889.889.889H9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
              </svg>
            </button>
            <button title="Speed" type="button" class="speed text-xs leading-6 font-semibold px-2">1x</button>
            <button class="skip" type="button" aria-label="Skip 10 seconds">
              <svg width="24" height="24" fill="none">
                <path d="M17.509 16.95c-2.862 2.733-7.501 2.733-10.363 0-2.861-2.734-2.861-7.166 0-9.9 2.862-2.733 7.501-2.733 10.363 0 .38.365.711.759.991 1.176" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                <path d="M19 5v3.111c0 .491-.398.889-.889.889H15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
              </svg>
            </button>
            <button class="next" type="button" aria-label="Next">
              <svg width="24" height="24" fill="none">
                <path d="M14 12 6 6v12l8-6Z" fill="currentColor" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                <path d="M18 6v12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
              </svg>
            </button>
          </div>
        </div>

        <div class="flex-auto min-w-0">
          <div class="p-4 player-body">
            <div class="playButton title flex items-center gap-1" id="playButton">
              <svg id="playButton-play" class="cursor-pointer h-5 w-5 flex-none" viewBox="0 0 32 32" fill="currentColor" aria-hidden="true">
                <svg class="h-5 w-5 flex-none -ml-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 50 50"><path d="M 10 5.25 L 10 44.75 L 11.5 43.875 L 42.09375 25.875 L 43.5625 25 L 42.09375 24.125 L 11.5 6.125 Z M 12 8.75 L 39.59375 25 L 12 41.25 Z"/></svg>
              </svg>
              <svg id="playButton-pause" class="cursor-pointer hidden h-5 w-5 flex-none" viewBox="0 0 100 100" fill="currentColor" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><path d="M 10 6 L 10 26 L 12 26 L 12 6 Z M 20 6 L 20 26 L 22 26 L 22 6 Z"/></svg>
              </svg>
              <span class="marquee-wrap"><span class="marquee-inner">@if($album->is_rrule_mode)<span id="play_at">{{$first->lyItem->description ?? $first->playAt?->format('Ymd')}}</span> - <span id="play_title">{{$first->title}}</span>@else<span id="play_at">{{$first->lyItem->description ?? $first->title}}</span> @<span id="play_title">{{$first->title}}</span>@endif</span></span>
            </div>

            <div id="waveform" class="py-2 waveform"></div>

            <div class="flex flex-nowrap justify-between">
              <div class="flex volume hidden lg:block md:block xl:block">
                <img id="volumeIcon" class="volume-icon" src="{{asset('/waveplayer/volume.svg')}}" alt="Mute" title="Mute" />
              </div>
              <div class="flex items-center text-base">
                <span id="currentTime">00:00</span><span>/</span><span id="totalDuration">00:00</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="playlist h-full overflow-y-auto">
        <ul role="list" class="divide-y divide-gray-100">
          @foreach($playlist as $key => $playlistItem)
          <li class="relative py-3 hover:bg-gray-50">
            <div class="px-4 sm:px-6 lg:px-8">
              <div class="mx-auto flex max-w-4xl justify-between gap-x-6">
                <div
                  id="track-{{$loop->index}}"
                  data-id="{{$loop->index}}"
                  data-url='{{$playlistItem->path}}'
                  data-date='{{$playlistItem->playAt?->format("Ymd")}}'
                  data-begin='{{$playlistItem->beginAt}}'
                  data-length='{{$playlistItem->length}}'
                  data-desc='{{$playlistItem->lyItem->description ?? $playlistItem->playAt?->format("Ymd")}}'
                  data-title='{{$playlistItem->title}}'
                  data-cover='{{$playlistItem->lyItem->ly_meta->cover ?? ""}}'
                  title="Play"
                  class="preventEvents track cursor-pointer flex min-w-0 gap-x-4">
                  <div class="flex shrink-0 items-center gap-x-4">
                    <svg data-id="{{$loop->index}}" class="playButtons-play h-5 w-5 flex-none" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                      <svg class="h-5 w-5 flex-none" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 50 50"><path d="M 10 5.25 L 10 44.75 L 11.5 43.875 L 42.09375 25.875 L 43.5625 25 L 42.09375 24.125 L 11.5 6.125 Z M 12 8.75 L 39.59375 25 L 12 41.25 Z"/></svg>
                    </svg>
                    <svg data-id="{{$loop->index}}" class="playButtons-pause hidden h-5 w-5 flex-none" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><path d="M 10 6 L 10 26 L 12 26 L 12 6 Z M 20 6 L 20 26 L 22 26 L 22 6 Z"/></svg>
                    </svg>
                  </div>
                  <div class="min-w-0 flex-auto">
                    <p class="text-base leading-6 text-gray-900">
                      {{$playlistItem->lyItem->description ?? $playlistItem->title}}
                    </p>
                    <p class="mt-1 flex text-sm leading-5 text-gray-500">
                      <span class="relative">
                        {{$playlistItem->title}}
                        @if(!$playlistItem->isFullAudio())
                        <span class="ml-2 text-xs text-orange-500">
                          [{{gmdate('i:s', $playlistItem->beginAt)}} ~ {{gmdate('i:s', $playlistItem->beginAt + $playlistItem->length)}}]
                        </span>
                        @endif
                      </span>
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </li>
          @endforeach
        </ul>
      </div>
    </div>
    @endif

  </main>

  @push('scripts')
    <script type="text/javascript">
      document.addEventListener("DOMContentLoaded", function() {
        var isRrule = {{ $album->is_rrule_mode ? 'true' : 'false' }};
        initWavePlayer({
          timeline: {{ $album->is_rrule_mode ? 'false' : 'true' }},
          volumeOnSrc: "{{ asset('/waveplayer/volume.svg') }}",
          volumeOffSrc: "{{ asset('/waveplayer/mute.svg') }}",
          volumeOnTitle: "Mute",
          volumeOffTitle: "Unmute",
          onTrackChange: function(el) {
            var desc = el.getAttribute('data-desc') || el.getAttribute('data-date');
            var title = el.getAttribute('data-title') || '';
            if (isRrule) {
              document.querySelector('#play_at').innerHTML = desc;
              document.querySelector('#play_title').innerHTML = title;
            } else {
              document.querySelector('#play_at').innerHTML = desc;
              document.querySelector('#play_title').innerHTML = title;
            }
            @if(!$album->avatar && !$album->target)
            var cover = el.getAttribute('data-cover');
            if (cover) {
              var img = document.querySelector('#albumCover');
              if (img) img.src = cover;
            }
            @endif
          }
        });
      });
    </script>
  @endpush
</x-player-layout>
