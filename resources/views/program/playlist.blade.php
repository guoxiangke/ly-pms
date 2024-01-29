<x-player-layout>
    <nav class="border-gray-200 bg-gray-50 dark:bg-gray-100 dark:border-gray-50">
      <div class="max-w-screen-xl flex flex-wrap items-center justify-between m-8 py-4" style="margin-top: 0;">
        <a href="#" class="">
            <img src="https://lpyy729.net/images/729ly_logo_50.png" class="h-10" alt="LOGO Logo" />
        </a>
      </div>
    </nav>
  <main class="m-8">
    
    <details>
      <summary class="cursor-pointer text-2xl font-extrabold tracking-tight text-slate-900">{{$lyMeta->name}}<span class="text-sm font-medium ml-2 text-gray-500">{{$lyMeta->description}}</span></summary>
      <div class="bg-gray-50 text-gray-600">
        <div class="p-2">
          <img src="{{$lyMeta->cover}}" style="float: right;" class="float-right w-36 h-36 ml-2">
          <div style="min-height: 150px;" >
            <p class="font-medium leading-7">{{$lyMeta->getMeta('description_detail')}}</p>
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

          <div class="flex flex-nowrap justify-between controls">
            <div class="volume">
              <img
                id="volumeIcon"
                class="volume-icon"
                src="/waveplayer/volume.svg"
                alt="Volume"
              />
              <input
                id="volumeSlider"
                class="volume-slider"
                type="range"
                name="volume-slider"
                min="0"
                max="100"
                value="50"
              />
            </div>

            <div class="flex items-center text-xs md:text-sm lg:text-base">
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
                      <span class="relative truncate hover:underline">{{$lyItem->description}}</span>
                    </p>
                  </div>
                </div>

                <div class="flex shrink-0 items-center gap-x-4">
                    <div data-url="{{$lyItem->path}}" 
                        title="音频下载 (6.89M)"
                        class="preventEvents downloads cursor-pointer">
                      <svg 
                        class="h-5 w-5 flex-none text-gray-400  hover:text-gray-600" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                          <title></title>
                          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><path d="M 15 4 L 15 20.5625 L 9.71875 15.28125 L 8.28125 16.71875 L 15.28125 23.71875 L 16 24.40625 L 16.71875 23.71875 L 23.71875 16.71875 L 22.28125 15.28125 L 17 20.5625 L 17 4 Z M 7 26 L 7 28 L 25 28 L 25 26 Z"/></svg>
                      </svg>
                    </div>
                    
                    <div class="group relative">
                      <svg 
                        data-url="{{Route('share.lyItem', $lyItem->hashId)}}"
                        class="share cursor-pointer h-5 w-5 flex-none text-gray-400  hover:text-gray-600" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48"><path d="M 35.484375 5.984375 A 1.50015 1.50015 0 0 0 34.439453 8.5605469 L 36.878906 11 L 35.5 11 C 23.64339 11 14 20.64339 14 32.5 A 1.50015 1.50015 0 1 0 17 32.5 C 17 22.26461 25.26461 14 35.5 14 L 36.878906 14 L 34.439453 16.439453 A 1.50015 1.50015 0 1 0 36.560547 18.560547 L 41.431641 13.689453 A 1.50015 1.50015 0 0 0 41.423828 11.302734 L 36.560547 6.4394531 A 1.50015 1.50015 0 0 0 35.484375 5.984375 z M 12.5 6 C 8.9280619 6 6 8.9280619 6 12.5 L 6 35.5 C 6 39.071938 8.9280619 42 12.5 42 L 35.5 42 C 39.071938 42 42 39.071938 42 35.5 L 42 27.5 A 1.50015 1.50015 0 1 0 39 27.5 L 39 35.5 C 39 37.450062 37.450062 39 35.5 39 L 12.5 39 C 10.549938 39 9 37.450062 9 35.5 L 9 12.5 C 9 10.549938 10.549938 9 12.5 9 L 20.5 9 A 1.50015 1.50015 0 1 0 20.5 6 L 12.5 6 z"/></svg>
                      </svg>
                      <span class="pointer-events-none absolute -top-7 -left-14 w-max rounded bg-gray-900 px-2 py-1 text-xs font-medium text-gray-50 opacity-0 shadow transition-all scale-0 group-hover:scale-100 group-hover:opacity-100 ">点此获取分享链接</span>
                    </div>
                </div>
              </div>
            </div>
          </li>
          @endforeach
        </ul>
      </div>
    </div>
 
    <div id="tooltip" class="hidden animate-fade transition-opacity duration-1000 ease-in hover:opacity-0  opacity-100  fixed z-50 top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2"> 
      <div class="w-full mx-auto">
        <div class="flex p-5 rounded-lg shadow bg-white">
          <div class="ml-3">
            <p class="mt-2 text-sm text-gray-800 leading-relaxed">分享链接已复制</p>
          </div>
          <div>
            <svg  class="w-5 h-5 fill-current text-gray-900" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M18,21H6c-1.657,0-3-1.343-3-3V6c0-1.657,1.343-3,3-3h12c1.657,0,3,1.343,3,3v12  C21,19.657,19.657,21,18,21z" opacity=".35"/><path d="M14.812,16.215L7.785,9.188c-0.384-0.384-0.384-1.008,0-1.392l0.011-0.011c0.384-0.384,1.008-0.384,1.392,0l7.027,7.027  c0.384,0.384,0.384,1.008,0,1.392l-0.011,0.011C15.82,16.599,15.196,16.599,14.812,16.215z"/><path d="M7.785,14.812l7.027-7.027c0.384-0.384,1.008-0.384,1.392,0l0.011,0.011c0.384,0.384,0.384,1.008,0,1.392l-7.027,7.027 c-0.384,0.384-1.008,0.384-1.392,0l-0.011-0.011C7.401,15.82,7.401,15.196,7.785,14.812z"/></svg>
          </div>
        </div>
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
        const volumeSlider = document.querySelector('#volumeSlider');
        const currentTime = document.querySelector('#currentTime');
        const totalDuration = document.querySelector('#totalDuration');
        const playButtonPlay = document.querySelector('#playButton-play');
        const playButtonPause = document.querySelector('#playButton-pause');
        const playButtonsPlay = document.getElementsByClassName('playButtons-play');
        const playButtonsPause = document.getElementsByClassName('playButtons-pause');

        const shares = document.querySelectorAll('.share');
        const tooltip = document.getElementById("tooltip");

        shares.forEach(share => {
          share.addEventListener('click', function (e) {
            e.preventDefault();
            const url = e.target.getAttribute('data-url');
            copyToClipboard(url);
            tooltip.classList.remove('hidden');

            setTimeout(function(){
                tooltip.classList.add('hidden');
            }, 3000);
          },false);
        });

        tooltip.addEventListener('mouseover', function (e) {
          e.preventDefault();
          setTimeout(function(){
              tooltip.classList.add('hidden')
          }, 1000);
        })

        // click to download
        links.forEach(link => {
          link.addEventListener('click', function (e) {
            e.preventDefault();
            console.log(e.target.getAttribute('data-url'),e.target);
            const url = e.target.getAttribute('data-url');
            const filename = url.split('/').pop();
            fetchDown(url, filename);
          });
        });

        // download function
        function fetchDown (url, saveas) {
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
            // document.removeChild(anchor);
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
             * Handles changing the volume slider input
             * @param {event} e
             */
            const handleVolumeChange = e => {
              // Set volume as input value divided by 100
              // NB: Wavesurfer only excepts volume value between 0 - 1
              const volume = e.target.value / 100;

              wavesurfer.setVolume(volume);

              // Save the value to local storage so it persists between page reloads
              localStorage.setItem('audio-player-volume', volume);
            };

            /**
             * Retrieves the volume value from localstorage and sets the volume slider
             */
            const setVolumeFromLocalStorage = () => {
              // Retrieves the volume from localstorage, or falls back to default value of 50
              const volume = localStorage.getItem('audio-player-volume') * 100 || 50;

              volumeSlider.value = volume;
            };

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
                volumeSlider.disabled = true;
              } else {
                volumeSlider.disabled = false;
                volumeIcon.src = '/waveplayer/volume.svg';
              }
            };

            // --------------------------------------------------------- //

            // Javascript Event listeners
            window.addEventListener('load', setVolumeFromLocalStorage);
            playButton.addEventListener('click', togglePlay);
            volumeIcon.addEventListener('click', toggleMute);
            volumeSlider.addEventListener('input', handleVolumeChange);

            // --------------------------------------------------------- //

            // Wavesurfer event listeners
            wavesurfer.on('ready', () => {
              // Set wavesurfer volume
              wavesurfer.setVolume(volumeSlider.value / 100);

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
                  let nextTrack = "#track-" + (parseInt(audio.dataset.id)  + 1) % tracks.length
                  document.querySelector(nextTrack).click();
              }
            });
        })();
      });
    </script>
  @endpush
</x-player-layout>


