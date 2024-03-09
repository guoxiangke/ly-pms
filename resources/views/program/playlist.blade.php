<x-player-layout>
    <nav class="border-gray-200 bg-gray-50 dark:bg-gray-100 dark:border-gray-50">
      <div class="max-w-screen-xl flex flex-wrap items-center justify-between m-8 py-4" style="margin-top: 0;">
        <a href="#" class="">
            <img src="/logo.png" class="h-10" alt="LOGO Logo" />
        </a>
      </div>
    </nav>
  <main class="m-8">
    
    <details>
      <summary class="cursor-pointer text-2xl font-extrabold tracking-tight text-slate-900">{{$lyMeta->name}}<span class="text-sm font-medium ml-2 text-gray-500">{{$lyMeta->description}}</span></summary>
      <div class="bg-gray-50 text-gray-600">
        <div class="p-2">
          <div class="text-gray-900" >
            <p class=" leading-7">{{$lyMeta->getMeta('description_detail')}}</p>
            <p class="mt-1">电邮：{{$lyMeta->getMeta('program_email')}}</p>
            <p class="mt-1">短信：{{$lyMeta->getMeta('program_sms')}} @if($keyword = $lyMeta->getMeta('program_sms_keyword')) {{$keyword}} @endif</p>
          </div>
        </div>
      </div>
      <div>
          
      </div>
    </details>
    @if($playlist->count()===0)
        <div class="text-lg p-4 text-gray">播放列表暂时为空，请耐心等待...</div>
    @else
    <div >
      @php
        $music =  $playlist->firstOrFail();
      @endphp
      <audio class="hidden" id="audio" data-id="0" controls src='{{$music->path}}'></audio>

      <div class="flex flex-nowrap audio-player my-4">
        <div class="p-4" style="padding-right: 0;">
          <img src="{{$lyMeta->cover}}" class="rounded-lg bg-slate-100 pt-1" loading="lazy">
          <div class="gap-1 flex items-center justify-center mt-3">
                  <button id="prev" type="button" class="" aria-label="Previous">
                    <svg width="24" height="24" fill="none">
                      <path d="m10 12 8-6v12l-8-6Z" fill="currentColor" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                      <path d="M6 6v12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                  </button>
                  <button id="rewind" type="button" aria-label="Rewind 10 seconds">
                    <svg width="24" height="24" fill="none">
                      <title>倒退10s</title>
                      <path d="M6.492 16.95c2.861 2.733 7.5 2.733 10.362 0 2.861-2.734 2.861-7.166 0-9.9-2.862-2.733-7.501-2.733-10.362 0A7.096 7.096 0 0 0 5.5 8.226" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                      <path d="M5 5v3.111c0 .491.398.889.889.889H9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                  </button>
                  <button title="速度切换" alt="倍速播放" id="speed" type="button" class=" rounded-lg text-xs leading-6 font-semibold px-2 ring-2 ring-inset ring-slate-500 text-slate-500 dark:text-slate-100 dark:ring-0 dark:bg-slate-500">
                    1x
                  </button>

                  <button id="skip" type="button" aria-label="Skip 10 seconds">

                    <svg width="24" height="24" fill="none">
                      <title>前进10s</title>
                      <path d="M17.509 16.95c-2.862 2.733-7.501 2.733-10.363 0-2.861-2.734-2.861-7.166 0-9.9 2.862-2.733 7.501-2.733 10.363 0 .38.365.711.759.991 1.176" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                      <path d="M19 5v3.111c0 .491-.398.889-.889.889H15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                  </button>
                  <button id="next" type="button" class="sm:block lg:hidden xl:block" aria-label="Next">
                    <svg width="24" height="24" fill="none">
                      <path d="M14 12 6 6v12l8-6Z" fill="currentColor" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                      <path d="M18 6v12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                  </button>
          </div>
        </div>
        <div class="flex-auto">
          <div class="p-4 player-body">
            <p
              class="playButton title" id="playButton">
              <svg id="playButton-play" class="cursor-pointer inline h-5 w-5 flex-none" viewBox="0 0 32 32" fill="currentColor" aria-hidden="true">
                <title>点击播放</title>
                <svg class="h-5 w-5 flex-none inline -ml-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 50 50"><path d="M 10 5.25 L 10 44.75 L 11.5 43.875 L 42.09375 25.875 L 43.5625 25 L 42.09375 24.125 L 11.5 6.125 Z M 12 8.75 L 39.59375 25 L 12 41.25 Z"/></svg>
              </svg>
              <svg id="playButton-pause" class="cursor-pointer hidden inline flex-none h-5 w-5 m-auto h-full" viewBox="0 0 100 100" fill="currentColor" aria-hidden="true">
                <title>点击暂停</title>
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><path d="M 10 6 L 10 26 L 12 26 L 12 6 Z M 20 6 L 20 26 L 22 26 L 22 6 Z"/></svg>
              </svg>
              {{$lyMeta->name}} - <span id="play_at">{{$music->play_at->format('Ymd')}}</span>
            </p>

          <div id="waveform" class="py-2 waveform"></div>

          <div class="flex flex-nowrap justify-between">
            <div class="flex volume hidden lg:block md:block xl:block">
              <img
                id="volumeIcon"
                class="volume-icon"
                src="/waveplayer/volume.svg"
                alt="Volume"
              />
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
          @foreach($playlist as $key => $lyItem)
          <li class="relative py-3 hover:bg-gray-50">
            <div class="px-4 sm:px-6 lg:px-8">
              <div class="mx-auto flex max-w-4xl justify-between gap-x-6">
                <div 
                  id="track-{{$loop->index}}"
                  data-id="{{$loop->index}}"
                  data-url='{{$lyItem->path}}' 
                  data-date='{{$lyItem->play_at->format("Ymd")}}' 
                  title="点击播放"
                  class="preventEvents track cursor-pointer flex min-w-0 gap-x-4">
                  <div class="flex shrink-0 items-center gap-x-4">
                    <svg
                      data-id="{{$loop->index}}"
                      class="playButtons-play h-5 w-5 flex-none"
                      viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                      <title>点击播放</title>
                      <svg class="h-5 w-5 flex-none" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 50 50"><path d="M 10 5.25 L 10 44.75 L 11.5 43.875 L 42.09375 25.875 L 43.5625 25 L 42.09375 24.125 L 11.5 6.125 Z M 12 8.75 L 39.59375 25 L 12 41.25 Z"/></svg>
                    </svg>

                    <svg
                      data-id="{{$loop->index}}"
                      class="playButtons-pause hidden h-5 w-5 flex-none"
                      viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                      <title>点击暂停</title>
                      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><path d="M 10 6 L 10 26 L 12 26 L 12 6 Z M 20 6 L 20 26 L 22 26 L 22 6 Z"/></svg>
                    </svg>
                  </div>
                  <div class="min-w-0 flex-auto">
                    <p class="text-base  leading-6 text-gray-900">
                      {{$lyMeta->name}}-{{$lyItem->play_at->format('Ymd')}}
                    </p>
                    <p class="mt-1 flex text-sm leading-5 text-gray-500">
                      <span class="relative hover:underline">{{$lyItem->description}}</span>
                    </p>
                  </div>
                </div>

                <div class="flex shrink-0 items-center gap-x-4">
                    <div data-url="{{$lyItem->path}}" 
                        title="音频下载 ({{$lyItem->filesize?:'6.9M'}})"
                        class="preventEvents downloads cursor-pointer text-gray-400  hover:text-gray-600">
                      <svg 
                        class="h-5 w-5 flex-none " viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                          <title></title>
                          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><path d="M 15 4 L 15 20.5625 L 9.71875 15.28125 L 8.28125 16.71875 L 15.28125 23.71875 L 16 24.40625 L 16.71875 23.71875 L 23.71875 16.71875 L 22.28125 15.28125 L 17 20.5625 L 17 4 Z M 7 26 L 7 28 L 25 28 L 25 26 Z"/></svg>
                      </svg>
                    </div>
                    
                    <div class="group relative" title="点此分享">

                      <a href="{{Route('share.lyItem', $lyItem->hashId)}}" target="_blank">
                      <svg 
                        class="share cursor-pointer h-5 w-5 flex-none text-gray-400  hover:text-gray-600" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48"><path d="M 35.484375 5.984375 A 1.50015 1.50015 0 0 0 34.439453 8.5605469 L 36.878906 11 L 35.5 11 C 23.64339 11 14 20.64339 14 32.5 A 1.50015 1.50015 0 1 0 17 32.5 C 17 22.26461 25.26461 14 35.5 14 L 36.878906 14 L 34.439453 16.439453 A 1.50015 1.50015 0 1 0 36.560547 18.560547 L 41.431641 13.689453 A 1.50015 1.50015 0 0 0 41.423828 11.302734 L 36.560547 6.4394531 A 1.50015 1.50015 0 0 0 35.484375 5.984375 z M 12.5 6 C 8.9280619 6 6 8.9280619 6 12.5 L 6 35.5 C 6 39.071938 8.9280619 42 12.5 42 L 35.5 42 C 39.071938 42 42 39.071938 42 35.5 L 42 27.5 A 1.50015 1.50015 0 1 0 39 27.5 L 39 35.5 C 39 37.450062 37.450062 39 35.5 39 L 12.5 39 C 10.549938 39 9 37.450062 9 35.5 L 9 12.5 C 9 10.549938 10.549938 9 12.5 9 L 20.5 9 A 1.50015 1.50015 0 1 0 20.5 6 L 12.5 6 z"/></svg>
                      </svg>
                      </a>
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
        

        const audio = document.getElementById("audio");
        const links = document.querySelectorAll('.downloads');
        const tracks = document.getElementsByClassName('track');
        const playButton = document.querySelector('#playButton');
        const waveform = document.querySelector('#waveform');
        const volumeIcon = document.querySelector('#volumeIcon');
        const currentTime = document.querySelector('#currentTime');
        const totalDuration = document.querySelector('#totalDuration');
        const playButtonPlay = document.querySelector('#playButton-play');
        const playButtonPause = document.querySelector('#playButton-pause');
        const playButtonsPlay = document.getElementsByClassName('playButtons-play');
        const playButtonsPause = document.getElementsByClassName('playButtons-pause');


        const rewindButton = document.querySelector('#rewind');
        const skipButton = document.querySelector('#skip');
        const speedButton = document.querySelector('#speed');
        const nextButton = document.querySelector('#next');
        const prevButton = document.querySelector('#prev');

        

        // click to download
        links.forEach(link => {
          link.addEventListener('click', function (e) {
            e.preventDefault();
            const url = e.target.getAttribute('data-url');
            const filename = url.split('/').pop();
            e.currentTarget.className += " img-rotate";
            fetchDown(url, filename, e);
          });
        });

        // download function
        function fetchDown (url, saveas, e) {
          e.currentTarget.className += " text-gray-600";
          fetch(url)
          .then(res => {
            if (res.status != 200) { throw new Error("Bad server response"); }
            return res.blob();
          })
          .then(data => {
            var url = window.URL.createObjectURL(data),
                anchor = document.createElement("a");
            anchor.href = url;
            anchor.download = saveas;
            anchor.click();
            window.URL.revokeObjectURL(url);
            e.target.classList.toggle('img-rotate');
            e.target.classList.toggle('text-gray-600');
          })
          .catch(err => console.error(err));
        }

        function copyToClipboard(text) {
          if (window.clipboardData && window.clipboardData.setData) {
              // IE specific code path to prevent textarea being shown while dialog is visible.
              return clipboardData.setData("Text", text); 

          } else if (document.queryCommandSupported && document.queryCommandSupported("copy")) {
              var textarea = document.createElement("textarea");
              textarea.textContent = text;
              textarea.style.position = "fixed";  // Prevent scrolling to bottom of page in MS Edge.
              document.body.appendChild(textarea);
              textarea.select();
              try {
                  return document.execCommand("copy");  // Security exception may be thrown by some browsers.
              } catch (ex) {
                  console.warn("Copy to clipboard failed.", ex);
                  return false;
              } finally {
                  document.body.removeChild(textarea);
              }
          }
      }

        (function() {
            audio.dataset.id = 0;
            const peaks= preDecodeJson.data;
            const wavesurfer = WaveSurfer.create({
              container: '#waveform',
              peaks: peaks,
              barWidth: 2,
              barGap: 1,
              barRadius: 2,
              media: audio,
              barAlign:'bottom'
              // height: 60,
              // url: url,
            })

            const togglePlay = (e) => {
              e.preventDefault();
              playButtonPlay.classList.toggle("hidden");
              playButtonPause.classList.toggle("hidden");

              // toggle playlist buttons
              var index = audio.dataset.id;
              playButtonsPlay[index].classList.toggle("hidden");
              playButtonsPause[index].classList.toggle("hidden");

              wavesurfer.playPause();
            };

            wavesurfer.on('interaction', () => {
              wavesurfer.play();

              playButtonPlay.classList.add("hidden");
              playButtonPause.classList.remove("hidden");

              var index = audio.dataset.id;
              playButtonsPlay[index].classList.add("hidden");
              playButtonsPause[index].classList.remove("hidden");

            });

            // Load the track on click
            Array.from(tracks).forEach((track, index) => {
                track.addEventListener('click', function (e) {
                    e.preventDefault();
                    // 切换
                    waveform.classList.add("animate-pulse");
                    waveform.classList.add("blur-1x");

                    if(audio.dataset.id != index){
                      audio.dataset.id = index

                      for (var i = 0; i < tracks.length; i++) {
                        playButtonsPlay[i].classList.remove("hidden");
                        playButtonsPause[i].classList.add("hidden");
                      }

                      // change play_at
                      var play_at = e.target.getAttribute('data-date');
                      document.querySelector('#play_at').innerHTML=play_at;
                      // audio play.
                      audio.src = e.target.getAttribute('data-url');
                      audio.play();
                      
                      playButtonPlay.classList.add("hidden");
                      playButtonPause.classList.remove("hidden");
                      playButtonsPlay[index].classList.add("hidden");
                      playButtonsPause[index].classList.remove("hidden");
                      wavesurfer.load(e.target.getAttribute('data-url'))
                    }else{
                      togglePlay(e);
                    }
                });
            });

            /**
             * Formats time as HH:MM:SS
             * @param {number} seconds
             * @returns time as HH:MM:SS
             */
            const formatTimecode = seconds => {
              return new Date(seconds * 1000).toISOString().substr(14, 5);
            };

            /**
             * Toggles mute/unmute of the wavesurfer volume
             * Also changes the volume icon and disables the volume slider
             */
            const toggleMute = () => {
              // wavesurfer.toggleMute();
              audio.muted = !audio.muted; 
              if (audio.muted) {
                volumeIcon.src = '/waveplayer/mute.svg';
              } else {
                volumeIcon.src = '/waveplayer/volume.svg';
              }
            };

            const handleSpeedChange = e => {
              let rate = wavesurfer.getPlaybackRate();
              if(rate<2) {
                rate+=0.5;
              }else{
                rate = 1;
              }
              e.target.innerText = rate+"x";
              wavesurfer.setPlaybackRate(rate);
            };

            const handleNext = e => {
              let nextTrack = "#track-" + (parseInt(audio.dataset.id)  + 1) % tracks.length
              document.querySelector(nextTrack).click();
            };
            const handlePrev = e => {
              let id = parseInt(audio.dataset.id);
              if(id <= 0) {
                id = tracks.length-1
              }else{
               id = --id % tracks.length
              }
              let nextTrack = "#track-" + id
              document.querySelector(nextTrack).click();
            };

            // --------------------------------------------------------- //

            // Javascript Event listeners
            playButton.addEventListener('click', togglePlay);
            volumeIcon.addEventListener('click', toggleMute);

            rewindButton.addEventListener('click', () => {wavesurfer.skip(-10)});
            skipButton.addEventListener('click', () => {wavesurfer.skip(10)});
            speedButton.addEventListener('click', handleSpeedChange);
            nextButton.addEventListener('click', handleNext);
            prevButton.addEventListener('click', handlePrev);

            // --------------------------------------------------------- //

            // Wavesurfer event listeners
            wavesurfer.on('ready', () => {
              // Set wavesurfer volume
              wavesurfer.setVolume(1);

              // Set audio track total duration
              const duration = wavesurfer.getDuration();
              totalDuration.innerHTML = formatTimecode(duration);

              waveform.classList.remove("animate-pulse");
              waveform.classList.remove("blur-1x");
            });

            // Sets the timecode current timestamp as audio plays
            wavesurfer.on('audioprocess', () => {
              const time = wavesurfer.getCurrentTime();
              currentTime.innerHTML = formatTimecode(time);
            });

            // Resets the play button icon after audio ends
            wavesurfer.on('finish', () => {
              let nextTrack = "#track-" + (parseInt(audio.dataset.id)  + 1) % tracks.length
              document.querySelector(nextTrack).click();
            });

            window.addEventListener("keydown", function (e) {
              if (e.keyCode === 32 || e.key == "Enter") {
                  e.preventDefault();
                  const playButton = document.querySelector('#playButton');
                  playButton.click()
              }
              if (e.key === "ArrowRight" || e.key == "ArrowDown") {
                  e.preventDefault();
                  handleNext()
              }

              if (e.key === "ArrowLeft" || e.key == "ArrowUp") {
                  e.preventDefault();
                  handlePrev()
              }
            });
        })();
      });
    </script>
  @endpush
</x-player-layout>


