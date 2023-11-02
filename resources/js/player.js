import WaveSurfer from 'wavesurfer.js'
// import sampleAudio from 'http://localhost:5174/assets/sample.mp3'
import sampleAudioPreDecodeJson from './sample.json'
// console.log(sampleAudioPreDecodeJson.data)
const url ='https://lytx2021.s3.ap-southeast-1.amazonaws.com/ly/audio/2023/bc/bc230929.mp3'

document.addEventListener("DOMContentLoaded", function() {
  const wavesurfer = WaveSurfer.create({
    container: '#waveform',
    url: url,
    peaks: sampleAudioPreDecodeJson.data,
    barWidth: 2,
    barRadius: 2,
    barGap: 1,
  })
  
  wavesurfer.on('interaction', () => wavesurfer.play());

  const playButton = document.querySelector('#playButton');
  const playButtonIcon = document.querySelector('#playButtonIcon');
  const waveform = document.querySelector('#waveform');
  const volumeIcon = document.querySelector('#volumeIcon');
  const volumeSlider = document.querySelector('#volumeSlider');
  const currentTime = document.querySelector('#currentTime');
  const totalDuration = document.querySelector('#totalDuration');

  // --------------------------------------------------------- //

  // Functions

  /**
   * Toggle play button
   */
  const togglePlay = () => {
    wavesurfer.playPause();

    const isPlaying = wavesurfer.isPlaying();

    if (isPlaying) {
      playButtonIcon.src = 'https://hassancorrigan.github.io/soundcloud-player/assets/icons/pause.svg';
    } else {
      playButtonIcon.src = 'https://hassancorrigan.github.io/soundcloud-player/assets/icons/play.svg';
    }
  };

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
    return new Date(seconds * 1000).toISOString().substr(11, 8);
  };

  /**
   * Toggles mute/unmute of the wavesurfer volume
   * Also changes the volume icon and disables the volume slider
   */
  const toggleMute = () => {
    wavesurfer.toggleMute();

    const isMuted = wavesurfer.getMute();

    if (isMuted) {
      volumeIcon.src = 'https://hassancorrigan.github.io/soundcloud-player/assets/icons/mute.svg';
      volumeSlider.disabled = true;
    } else {
      volumeSlider.disabled = false;
      volumeIcon.src = 'https://hassancorrigan.github.io/soundcloud-player/assets/icons/volume.svg';
    }
  };

  // --------------------------------------------------------- //

  // Create a new instance and load the wavesurfer
  // const wavesurfer = initializeWavesurfer();
  // https://lytx2021.s3.ap-southeast-1.amazonaws.com
  // https://d3ml8yyp1h3hy5.cloudfront.net
  // wavesurfer.load(mp3);

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
  });

  // Sets the timecode current timestamp as audio plays
  wavesurfer.on('audioprocess', () => {
    const time = wavesurfer.getCurrentTime();
    currentTime.innerHTML = formatTimecode(time);
  });

  // Resets the play button icon after audio ends
  wavesurfer.on('finish', () => {
    playButtonIcon.src = 'https://hassancorrigan.github.io/soundcloud-player/assets/icons/play.svg';
  });

});