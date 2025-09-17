

import { bootstrapCameraKit, createMediaStreamSource, Transform2D } from '@snap/camera-kit';

const API_TOKEN = import.meta.env.VITE_SNAP_API_TOKEN;
const LENS_GROUP_ID = import.meta.env.VITE_LENS_GROUP_ID;
const LENS_ID = import.meta.env.VITE_LENS_ID;

const liveCanvas = document.getElementById('live');

(async () => {
  const cameraKit = await bootstrapCameraKit({ apiToken: API_TOKEN });
  const session = await cameraKit.createSession({ liveRenderTarget: liveCanvas });

  const stream = await navigator.mediaDevices.getUserMedia({ video: { width: { ideal: 1920 }, height: { ideal: 1080 } }, audio: false });
  const source = createMediaStreamSource(stream, { transform: Transform2D.MirrorX, cameraType: 'front' }); 
  await session.setSource(source);

  const lens = await cameraKit.lensRepository.loadLens(LENS_ID, LENS_GROUP_ID);
  await session.applyLens(lens);
  await session.play();
})();
