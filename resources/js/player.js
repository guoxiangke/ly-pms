import WaveSurfer from 'wavesurfer.js'
import Timeline from 'wavesurfer.js/dist/plugins/timeline.esm.js'
window.WaveSurfer = WaveSurfer;
window.WaveSurferTimeline = Timeline;

import preDecodeJson from './audiowave-init-peaks.json'
window.preDecodeJson = preDecodeJson;

import './waveplayer.js'