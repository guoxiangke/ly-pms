/**
 * Shared WaveSurfer playlist player.
 *
 * Usage (in Blade @push('scripts')):
 *   initWavePlayer({
 *     volumeOnSrc:    "{{ asset('/waveplayer/volume.svg') }}",
 *     volumeOffSrc:   "{{ asset('/waveplayer/mute.svg') }}",
 *     volumeOnTitle:  "静音",
 *     volumeOffTitle: "取消静音",
 *     onTrackChange:  function(trackEl) { ... }
 *   });
 */
window.initWavePlayer = function (options = {}) {
  const audio = document.getElementById('audio');
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

  const rewindButton = document.querySelectorAll('.rewind');
  const skipButton = document.querySelectorAll('.skip');
  const speedButton = document.querySelectorAll('.speed');
  const nextButton = document.querySelectorAll('.next');
  const prevButton = document.querySelectorAll('.prev');

  // Clip time constraints (begin_at + length)
  let currentBeginAt = 0;
  let currentLength = 0;

  // --- Clip view helpers --- //
  const isClipMode = () => currentBeginAt > 0 && currentLength > 0;

  const scrollToClipStart = () => {
    const scrollContainer = wavesurfer.getWrapper().parentElement;
    if (!scrollContainer) return;
    const duration = wavesurfer.getDuration();
    if (duration > 0) {
      const scrollFrac = currentBeginAt / duration;
      scrollContainer.scrollLeft = scrollFrac * scrollContainer.scrollWidth;
    }
  };

  const applyClipView = () => {
    if (!isClipMode()) return removeClipView();
    // 先记录容器宽度（zoom 前），再锁定防止被撑开
    const containerWidth = waveform.clientWidth;
    waveform.style.maxWidth = containerWidth + 'px';
    waveform.style.overflow = 'hidden';
    const pxPerSec = containerWidth / currentLength;
    wavesurfer.zoom(pxPerSec);
    scrollToClipStart();
  };

  const removeClipView = () => {
    wavesurfer.zoom(0);
    waveform.style.maxWidth = '';
    waveform.style.overflow = '';
  };

  audio.dataset.id = 0;

  // Read initial track constraints
  const firstTrack = document.getElementById('track-0');
  if (firstTrack) {
    currentBeginAt = parseInt(firstTrack.dataset.begin) || 0;
    currentLength = parseInt(firstTrack.dataset.length) || 0;
    if (currentBeginAt > 0) {
      audio.currentTime = currentBeginAt;
    }
  }

  const plugins = [];
  if (options.timeline !== false) {
    plugins.push(WaveSurferTimeline.create({
      height: 16,
      insertPosition: 'beforebegin',
      style: { fontSize: '11px', color: '#888' },
      formatTimeCallback: (seconds) => {
        const m = Math.floor(seconds / 60);
        const s = Math.round(seconds % 60);
        return `${m}:${String(s).padStart(2, '0')}`;
      },
    }));
  }

  const wavesurfer = WaveSurfer.create({
    container: '#waveform',
    peaks: preDecodeJson.data,
    barWidth: 2,
    barGap: 1,
    barRadius: 2,
    media: audio,
    barAlign: 'bottom',
    plugins,
  });

  // Clip mode: show loading blur until real decode
  if (isClipMode()) {
    waveform.classList.add('waveform-loading');
  }

  // --- Waveform peaks cache: memory + localStorage (1 day) --- //
  const peaksCache = {};
  const CACHE_TTL = 86400000;

  const cacheKey = (url) => 'wf_' + url.split('/').pop();

  const getCachedPeaks = (url) => {
    const key = cacheKey(url);
    if (peaksCache[key]) return peaksCache[key];
    try {
      const stored = JSON.parse(localStorage.getItem(key));
      if (stored && stored.e > Date.now()) {
        peaksCache[key] = stored.d;
        return stored.d;
      }
      localStorage.removeItem(key);
    } catch (e) {}
    return null;
  };

  const cachePeaks = (url) => {
    const key = cacheKey(url);
    if (peaksCache[key]) return;
    try {
      const exported = wavesurfer.exportPeaks({ maxLength: 8000 });
      peaksCache[key] = exported;
      localStorage.setItem(key, JSON.stringify({ d: exported, e: Date.now() + CACHE_TTL }));
    } catch (e) {}
  };

  // Always load without peaks so wavesurfer fetches/decodes independently
  // and never touches audio.src (keeps audio.play() instant)
  const loadTrack = (url) => {
    wavesurfer.load(url);
  };

  // Clip mode: use audio metadata duration to apply correct zoom on placeholder peaks,
  // then loadTrack to decode real waveform in background
  if (isClipMode() && firstTrack) {
    const firstUrl = firstTrack.getAttribute('data-url');
    const applyInitialClipView = () => {
      if (audio.duration && isFinite(audio.duration)) {
        // Apply zoom to placeholder peaks using real audio duration
        const containerWidth = waveform.clientWidth;
        if (containerWidth > 0) {
          waveform.style.maxWidth = containerWidth + 'px';
          waveform.style.overflow = 'hidden';
          const pxPerSec = containerWidth / currentLength;
          wavesurfer.zoom(pxPerSec);
          scrollToClipStart();
        }
        totalDuration.innerHTML = formatTimecode(audio.duration);
      }
    };
    if (audio.duration && isFinite(audio.duration)) {
      applyInitialClipView();
    } else {
      audio.addEventListener('loadedmetadata', applyInitialClipView, { once: true });
    }
    if (firstUrl) {
      waveform.classList.add('waveform-loading');
      // Pass resolved URL so setSrc skips replacing audio.src
      loadTrack(audio.src || firstUrl);
    }
  }

  // --- Play/pause button sync --- //
  const syncPlayButtons = (isPlaying) => {
    const index = audio.dataset.id;
    playButtonPlay.classList.toggle('hidden', isPlaying);
    playButtonPause.classList.toggle('hidden', !isPlaying);
    playButtonsPlay[index].classList.toggle('hidden', isPlaying);
    playButtonsPause[index].classList.toggle('hidden', !isPlaying);
    playButton.classList.toggle('paused', !isPlaying);
  };

  const togglePlay = (e) => {
    e.preventDefault();
    wavesurfer.playPause();
  };

  wavesurfer.on('play', () => syncPlayButtons(true));
  wavesurfer.on('pause', () => syncPlayButtons(false));
  wavesurfer.on('interaction', () => wavesurfer.play());

  // --- Track click --- //
  Array.from(tracks).forEach((track, index) => {
    track.addEventListener('click', function (e) {
      e.preventDefault();

      if (audio.dataset.id != index) {
        audio.dataset.id = index;

        for (let i = 0; i < tracks.length; i++) {
          playButtonsPlay[i].classList.remove('hidden');
          playButtonsPause[i].classList.add('hidden');
        }

        // Custom title / metadata update
        if (options.onTrackChange) {
          playButton.classList.remove('marquee');
          options.onTrackChange(e.target);
          requestAnimationFrame(updateMarquee);
        }

        // Read clip constraints
        currentBeginAt = parseInt(e.target.getAttribute('data-begin')) || 0;
        currentLength = parseInt(e.target.getAttribute('data-length')) || 0;

        const url = e.target.getAttribute('data-url');
        if (!getCachedPeaks(url)) {
          waveform.classList.add('waveform-loading');
        }
        // Play immediately, wavesurfer decodes independently in background
        audio.src = url;
        if (currentBeginAt > 0) {
          audio.addEventListener('loadedmetadata', function onLoaded() {
            audio.currentTime = currentBeginAt;
            audio.removeEventListener('loadedmetadata', onLoaded);
          });
        }
        audio.play();
        // Pass resolved URL so wavesurfer.setSrc() sees src===url and skips
        // replacing audio.src with blob URL (which would reset currentTime)
        loadTrack(audio.src);
      } else {
        togglePlay(e);
      }
    });
  });

  // --- Helpers --- //
  const marqueeWrap = playButton.querySelector('.marquee-wrap');
  const marqueeInner = playButton.querySelector('.marquee-inner');
  const updateMarquee = () => {
    if (!marqueeInner || !marqueeWrap) return;
    const overflow = marqueeInner.scrollWidth - marqueeWrap.clientWidth;
    if (overflow > 0) {
      const pauseTime = 5;
      const scrollTime = Math.max(4.5, overflow / 100 * 4.5);
      const duration = scrollTime + pauseTime * 2;
      const p1 = Math.round(pauseTime / duration * 100);
      const p2 = Math.round((pauseTime + scrollTime) / duration * 100);
      const dist = -overflow + 'px';
      const animName = 'mq-' + Date.now();
      const keyframes = `@keyframes ${animName} { 0%,${p1}% { transform: translateX(0); } ${p2}%,100% { transform: translateX(${dist}); } }`;
      let styleEl = document.getElementById('marquee-kf');
      if (!styleEl) { styleEl = document.createElement('style'); styleEl.id = 'marquee-kf'; document.head.appendChild(styleEl); }
      styleEl.textContent = keyframes;
      marqueeInner.style.animation = `${animName} ${duration}s linear infinite`;
      playButton.classList.add('marquee');
    } else {
      playButton.classList.remove('marquee');
      marqueeInner.style.animation = '';
    }
  };
  // Initial check (after fonts loaded); start paused
  playButton.classList.add('paused');
  requestAnimationFrame(updateMarquee);

  const formatTimecode = (seconds) =>
    new Date(seconds * 1000).toISOString().substr(14, 5);

  const toggleMute = () => {
    audio.muted = !audio.muted;
    if (audio.muted) {
      volumeIcon.src = options.volumeOffSrc || '';
      volumeIcon.title = options.volumeOffTitle || 'Unmute';
    } else {
      volumeIcon.src = options.volumeOnSrc || '';
      volumeIcon.title = options.volumeOnTitle || 'Mute';
    }
  };

  const handleSpeedChange = (e) => {
    let rate = wavesurfer.getPlaybackRate();
    rate = rate < 2 ? rate + 0.25 : 0.75;
    e.target.innerText = rate + 'x';
    wavesurfer.setPlaybackRate(rate);
  };

  const handleNext = () => {
    const nextTrack = '#track-' + ((parseInt(audio.dataset.id) + 1) % tracks.length);
    document.querySelector(nextTrack).click();
  };

  const handlePrev = () => {
    let id = parseInt(audio.dataset.id);
    id = id <= 0 ? tracks.length - 1 : --id % tracks.length;
    document.querySelector('#track-' + id).click();
  };

  // --- Event listeners --- //
  playButton.addEventListener('click', togglePlay);
  volumeIcon.addEventListener('click', toggleMute);
  const clampedSkip = (delta) => {
    if (isClipMode()) {
      const current = wavesurfer.getCurrentTime();
      const target = Math.max(currentBeginAt, Math.min(current + delta, currentBeginAt + currentLength));
      wavesurfer.setTime(target);
    } else {
      wavesurfer.skip(delta);
    }
  };

  rewindButton.forEach((bt) => bt.addEventListener('click', () => clampedSkip(-10)));
  skipButton.forEach((bt) => bt.addEventListener('click', () => clampedSkip(10)));
  speedButton.forEach((bt) => bt.addEventListener('click', handleSpeedChange));
  nextButton.forEach((bt) => bt.addEventListener('click', handleNext));
  prevButton.forEach((bt) => bt.addEventListener('click', handlePrev));

  // Ensure clip starts at beginAt position after wavesurfer ready
  const restoreClipPosition = () => {
    if (audio.currentTime < currentBeginAt) {
      wavesurfer.setTime(currentBeginAt);
    }
    currentTime.innerHTML = formatTimecode(audio.currentTime);
  };

  wavesurfer.on('ready', () => {
    wavesurfer.setVolume(1);
    if (isClipMode()) {
      totalDuration.innerHTML = formatTimecode(wavesurfer.getDuration());
      applyClipView();
      restoreClipPosition();
    } else {
      totalDuration.innerHTML = formatTimecode(wavesurfer.getDuration());
      removeClipView();
    }
    waveform.classList.remove('waveform-loading');
    cachePeaks(audio.src);
  });

  wavesurfer.on('audioprocess', () => {
    const time = wavesurfer.getCurrentTime();
    currentTime.innerHTML = formatTimecode(time);
    if (currentLength > 0 && time >= currentBeginAt + currentLength) {
      handleNext();
    }
  });

  wavesurfer.on('seeking', () => {
    currentTime.innerHTML = formatTimecode(wavesurfer.getCurrentTime());
  });

  wavesurfer.on('finish', () => handleNext());

  wavesurfer.on('redrawcomplete', () => {
    if (isClipMode()) {
      scrollToClipStart();
    }
  });

  // --- Keyboard shortcuts --- //
  window.addEventListener('keydown', function (e) {
    if (e.metaKey || e.ctrlKey || e.altKey) return;
    if (e.keyCode === 32 || e.key === 'Enter') {
      e.preventDefault();
      playButton.click();
    }
    if (e.key === 'ArrowRight') {
      e.preventDefault();
      clampedSkip(30);
    }
    if (e.key === 'ArrowLeft') {
      e.preventDefault();
      clampedSkip(-30);
    }
    if (e.key === 'ArrowDown') {
      e.preventDefault();
      handleNext();
    }
    if (e.key === 'ArrowUp') {
      e.preventDefault();
      handlePrev();
    }
  });
};
