<?php
  include '../header.php';
  include '../lib/connection.php';
  ?>

<!doctype html>
<html lang="en">
  <head>
  

        <meta charset="UTF-8">
    <meta name="description" content="Fashi Template">
    <meta name="keywords" content="Fashi, unica, creative, html">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <!-- Local Muli Font -->
    <link rel="stylesheet" href="../css/css.css" type="text/css">
    <!-- CSS Styles -->
    <link rel="stylesheet" href="../css/bootstrap.min.css" type="text/css">
    <link rel="stylesheet" href="../css/font-awesome.min.css" type="text/css">
    <link rel="stylesheet" href="../css/themify-icons.css" type="text/css">
    <link rel="stylesheet" href="../css/elegant-icons.css" type="text/css">
    <link rel="stylesheet" href="../css/owl.carousel.min.css" type="text/css">
    <link rel="stylesheet" href="../css/nice-select.css" type="text/css">
    <link rel="stylesheet" href="../css/jquery-ui.min.css" type="text/css">
    <link rel="stylesheet" href="../css/slicknav.min.css" type="text/css">
    <link rel="stylesheet" href="../css/style.css" type="text/css">
    <link rel="stylesheet" href="../css/out-of-stock.css" type="text/css">
</head>

    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Vite App</title>
  </head>
  <body>
    <a href="../product.php" class="site-btn login-btn" aria-label="Admin login" title="Admin login">HOME</a> 
    <center>
      <div style="width: 100%; height: 100vh; display: flex; justify-content: center; align-items: center;" >
           <canvas id="canvas" width="100%" height="auto"></canvas>
      </div>
   
    </center>
    <script type="module" src="/src/ar.js"></script>
    <!-- Vite Dev Scripts -->
<script type="module" src="http://localhost:5173/@vite/client"></script>
<script type="module" src="http://localhost:5173/src/ar.js"></script>

    <script src="../js/jquery-3.6.0.min.js"></script>
    <script src="../js/bootstrap.min.js"></script>
    <script src="../js/jquery-ui.min.js"></script>
    <script src="../js/jquery.countdown.min.js"></script>
    <script src="../js/jquery.nice-select.min.js"></script>
    <script src="../js/jquery.zoom.min.js"></script>
    <script src="../js/jquery.dd.min.js"></script>
    <script src="../js/jquery.slicknav.js"></script>
    <script src="../js/owl.carousel.min.js"></script>
    <script src="../js/main.js"></script>

    <script>
      import { bootstrapCameraKit } from "@snap/camera-kit";

(async function () {
    const cameraKit = await bootstrapCameraKit({
        apiToken: 'eyJhbGciOiJIUzI1NiIsImtpZCI6IkNhbnZhc1MyU0hNQUNQcm9kIiwidHlwIjoiSldUIn0.eyJhdWQiOiJjYW52YXMtY2FudmFzYXBpIiwiaXNzIjoiY2FudmFzLXMyc3Rva2VuIiwibmJmIjoxNzU2NzQ0MTkxLCJzdWIiOiI4YWNmMWVjNy1jNmViLTQzY2UtOTY4Ni1kZTlhYjI0YTAwYTB-U1RBR0lOR344Yzg3ZWJjZS0xYzM3LTRiNWYtODI3Yi1lOWJkMzJiODJhZTEifQ.EGE2EdVo9cH0YIIJoWfeHsfwUflChD1ll0jkwnS40Vs'
    });

// ...existing code...
const liveRenderTarget = /** @type {HTMLCanvasElement} */ (document.getElementById('canvas'));
// ...existing code...
    const session = await cameraKit.createSession({ liveRenderTarget });

    const mediaStream = await navigator.mediaDevices.getUserMedia({
        video: { facingMode: 'user' }
    });

    await session.setSource(mediaStream);
    await session.play();


    const lens = await cameraKit.lensRepository.loadLens('850dbd2f-b51f-4845-8df6-df39b09ff486', 'ba676a53-a9e3-442a-b283-4a1537745b00');
    await session.applyLens(lens);
    
})();
    </script>
  </body>
</html>
