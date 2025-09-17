<?php   include 'header.php'; ?>

<!DOCTYPE html>
<html lang="zxx">

<head>
    <meta charset="UTF-8">
    <meta name="description" content="Fashi Template">
    <meta name="keywords" content="Fashi, unica, creative, html">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <!-- Local Font CSS -->
    <link rel="stylesheet" href="css/css.css" type="text/css">

    <!-- CSS Styles -->
    <link rel="stylesheet" href="css/bootstrap.min.css" type="text/css">
    <link rel="stylesheet" href="css/font-awesome.min.css" type="text/css">
    <link rel="stylesheet" href="css/themify-icons.css" type="text/css">
    <link rel="stylesheet" href="css/elegant-icons.css" type="text/css">
    <link rel="stylesheet" href="css/owl.carousel.min.css" type="text/css">
    <link rel="stylesheet" href="css/nice-select.css" type="text/css">
    <link rel="stylesheet" href="css/jquery-ui.min.css" type="text/css">
    <link rel="stylesheet" href="css/slicknav.min.css" type="text/css">
    <link rel="stylesheet" href="css/style.css" type="text/css">
</head>

<body>
   

    <!-- Breadcrumb Section -->
    <div class="breacrumb-section">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="breadcrumb-text">
                        <a href="default.php"><i class="fa fa-home"></i> Home</a>

                        <span>Contact</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Breadcrumb Section Begin -->

    <!-- Map Section -->
    <div class="map spad">
        <div class="container">
                <div class="map-inner">
                    <iframe id="gmap-iframe"
                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d30882.91452758613!2d120.7499646!3d15.1458706!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3396f1e7c6f3e2e1%3A0x9e8b6e0b1a2e7c2a!2sApalit%2C%20Pampanga!5e0!3m2!1sen!2sph!4v1717930000000!5m2!1sen!2sph"
                        height="610" style="border:0" allowfullscreen onload="this.dataset.loaded='1'"></iframe>
                    <div class="icon">
                        <i class="fa fa-map-marker"></i>
                    </div>

                    <!-- Hidden fallback shown when the iframe is blocked (adblockers) -->
                    <div id="map-fallback" style="display:none;margin-top:12px;">
                        <div style="background:#fff;padding:12px;border-radius:6px;border:1px solid #e9ecef;">
                            <strong>Map blocked</strong>
                            <p style="margin:6px 0;">If you don't see the map above your browser or an extension may be blocking Google Maps. <a href="https://www.google.com/maps/search/?api=1&query=Apalit+Pampanga" target="_blank" rel="noopener">Open in Google Maps</a>.</p>
                            <p style="margin:6px 0;color:#666;font-size:13px;">You can also <a id="static-map-link" href="#" target="_blank" rel="noopener">view a static map image</a>.</p>
                        </div>
                    </div>
                </div>
        </div>
    </div>
    <!-- Map Section Begin -->

    <!-- Contact Section -->
    <section class="contact-section spad">
        <div class="container">
            <div class="row">
                <div class="col-lg-5">
                    <div class="contact-widget">
                        <div class="cw-item">
                            <div class="ci-icon">
                                <i class="ti-location-pin"></i>
                            </div>
                            <div class="ci-text">
                                <span>Address:</span>
                                <p>Apalit, Pampanga, Philippines</p>
                            </div>
                        </div>
                        <div class="cw-item">
                            <div class="ci-icon">
                                <i class="ti-mobile"></i>
                            </div>
                            <div class="ci-text">
                                <span>Phone:</span>
                                <p>+63 912 3456 789</p>
                            </div>
                        </div>
                        <div class="cw-item">
                            <div class="ci-icon">
                                <i class="ti-email"></i>
                            </div>
                            <div class="ci-text">
                                <span>Email:</span>
                                <p>hellocolorlib@gmail.com</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 offset-lg-1">
                    <div class="contact-info-box p-4" style="background:#f8f9fa;border-radius:6px;">
                        <h4>Contact Us</h4>
                        <p>If you have questions or need help, please reach out and we'll respond as soon as possible.</p>
                        <p class="mt-3">
                            <a class="site-btn" href="mailto:hellocolorlib@gmail.com" role="button" aria-label="Email us">Email Us</a>
                            <a class="btn btn-outline-secondary ml-2" href="tel:+639123456789" role="button" aria-label="Call us">Call Us</a>
                        </p>
                        <small class="text-muted">We typically respond within 1-2 business days.</small>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- Contact Section End -->

    <?php include 'footer.php'; ?>

    <!-- JS Plugins -->
    <script src="js/jquery-3.6.0.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/jquery-ui.min.js"></script>
    <script src="js/jquery.countdown.min.js"></script>
    <script src="js/jquery.nice-select.min.js"></script>
    <script src="js/jquery.zoom.min.js"></script>
    <script src="js/jquery.dd.min.js"></script>
    <script src="js/jquery.slicknav.js"></script>
    <script src="js/owl.carousel.min.js"></script>
    <script src="js/main.bundle.js"></script>
    <script>
        // Graceful fallback when Google Maps iframe is blocked (ERR_BLOCKED_BY_CLIENT)
        (function(){
            var iframe = document.getElementById('gmap-iframe');
            var fallback = document.getElementById('map-fallback');
            var staticLink = document.getElementById('static-map-link');
            if (!iframe) return;
            // set static link URL
            var staticUrl = 'https://maps.googleapis.com/maps/api/staticmap?center=Apalit+Pampanga&zoom=13&size=600x300&markers=color:red%7CApalit+Pampanga';
            staticLink.href = staticUrl;

            // after 1 second, if iframe hasn't fired onload, show fallback (likely blocked)
            setTimeout(function(){
                if (iframe.dataset.loaded !== '1') {
                    // show fallback
                    if (fallback) fallback.style.display = 'block';
                    // replace iframe with a static image for visual continuity
                    var img = document.createElement('img');
                    img.src = staticUrl;
                    img.alt = 'Static map - Apalit, Pampanga';
                    img.style.width = '100%';
                    img.style.height = 'auto';
                    img.style.borderRadius = '6px';
                    iframe.parentNode.insertBefore(img, iframe.nextSibling);
                }
            }, 1000);
        })();
    </script>
</body>

</html>