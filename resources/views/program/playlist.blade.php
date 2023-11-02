<x-player-layout>
  @push('scripts')
  <script type="text/javascript">
    console.log('push scripts works here!')
  </script>
  @endpush

  <main class="container">
    <h1>SoundCloud Player</h1>
    <div class="audio-player">
      <button id="playButton" class="play-button">
        <img
          id="playButtonIcon"
          class="play-button-icon"
          src="https://hassancorrigan.github.io/soundcloud-player/assets/icons/play.svg"
          alt="Play Button"
        />
      </button>

      <div class="player-body">
        <p class="title">Artist - Track Title</p>
        <div id="waveform" class="waveform"></div>

        <div class="controls">
          <div class="volume">
            <img
              id="volumeIcon"
              class="volume-icon"
              src="https://hassancorrigan.github.io/soundcloud-player/assets/icons/volume.svg"
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

          <div class="timecode">
            <span id="currentTime">00:00:00</span>
            <span>/</span>
            <span id="totalDuration">00:00:00</span>
          </div>
        </div>
      </div>
    </div>
  </main>
</x-player-layout>


