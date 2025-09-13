import { bootstrapCameraKit } from "@snap/camera-kit";
(async function () {
    const cameraKit = await bootstrapCameraKit({
        apiToken: 'eyJhbGciOiJIUzI1NiIsImtpZCI6IkNhbnZhc1MyU0hNQUNQcm9kIiwidHlwIjoiSldUIn0.eyJhdWQiOiJjYW52YXMtY2FudmFzYXBpIiwiaXNzIjoiY2FudmFzLXMyc3Rva2VuIiwibmJmIjoxNzU2NzQ0MTkxLCJzdWIiOiI4YWNmMWVjNy1jNmViLTQzY2UtOTY4Ni1kZTlhYjI0YTAwYTB-U1RBR0lOR344Yzg3ZWJjZS0xYzM3LTRiNWYtODI3Yi1lOWJkMzJiODJhZTEifQ.EGE2EdVo9cH0YIIJoWfeHsfwUflChD1ll0jkwnS40Vs'
    });
    const liveRenderTarget = /** @type {HTMLCanvasElement} */ (document.getElementById('canvas'));
    const session = await cameraKit.createSession({ liveRenderTarget });
    const mediaStream = await navigator.mediaDevices.getUserMedia({
        video: { facingMode: 'user' }
    });
    await session.setSource(mediaStream);
    await session.play();
    const lens = await cameraKit.lensRepository.loadLens('6852b949-7596-4613-b95f-70d70b25af8c', 'ba676a53-a9e3-442a-b283-4a1537745b00');
    await session.applyLens(lens);
})();
