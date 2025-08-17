<?php
ob_start();
session_start();
include 'header.php';
include "lib/connection.php";

// Initialize variables to store potential results and errors
$result = null;
$email_error = null;
$phone_error = null;
$pass_error = null;
$name_error = null;

if (isset($_POST['u_submit'])) {
    // Retrieve POST variables from the registration form with proper sanitization
    $f_name = ucfirst(strtolower(trim($_POST['u_name'])));
    $l_name = ucfirst(strtolower(trim($_POST['l_name'])));
    $email = trim($_POST['email']);
    $raw_pass = $_POST['pass'];
    $raw_cpass = $_POST['c_pass'];
    $region = $_POST['region_text'];
    $province = $_POST['province_text'];
    $city = $_POST['city_text'];
    $barangay = $_POST['barangay_text'];
    $street = trim($_POST['street']);
    $zone = isset($_POST['zone']) ? trim($_POST['zone']) : null;
    $phone = trim($_POST['phone']);

    // Server-side validation for first and last name (letters and spaces only)
    if (!preg_match('/^[A-Za-z\s]+$/', $f_name)) {
        $name_error = "First name must contain only letters and spaces (no numbers or special characters).";
    } elseif (!preg_match('/^[A-Za-z\s]+$/', $l_name)) {
        $name_error = "Last name must contain only letters and spaces (no numbers or special characters).";
    }

    // Process and validate phone number
    $phone = preg_replace('/[^0-9]/', '', $phone); // Remove non-numeric characters
    // Validate phone length and format for Philippine numbers
    if (strlen($phone) < 10 || strlen($phone) > 11) {
        $phone_error = "Phone number must be 10-11 digits (e.g., 09123456789 or 9123456789)";
    } elseif (!preg_match('/^(09|\+639|639|9)\d{9}$/', $phone)) {
        $phone_error = "Invalid Philippine phone number format. Use format: 09123456789";
    }

    // Password strength validation
    if (
        strlen($raw_pass) < 8 ||
        !preg_match('/[A-Z]/', $raw_pass) ||   // Ensure at least one uppercase letter
        !preg_match('/[a-z]/', $raw_pass) ||      // Ensure at least one lowercase letter
        !preg_match('/[0-9]/', $raw_pass) ||      // Ensure at least one digit
        !preg_match('/[\W_]/', $raw_pass)          // Ensure at least one special character
    ) {
        $pass_error = "Password must be at least 8 characters and include uppercase, lowercase, number, and special character.";
    }

    // Merge errors
    if ($name_error || $pass_error || $phone_error) {
        $result = $name_error ? $name_error : ($pass_error ? $pass_error : $phone_error);
    } else {
        // Hash the password securely using password_hash()
        $pass = password_hash($raw_pass, PASSWORD_DEFAULT);
        
        // Check if the email already exists in the database
        $email_check_sql = "SELECT email FROM users WHERE email = ?";
        if ($stmt_check = $conn->prepare($email_check_sql)) {
            $stmt_check->bind_param("s", $email);
            $stmt_check->execute();
            $stmt_check->store_result();
            if ($stmt_check->num_rows > 0) { // Email is already registered
                $email_error = "Email is already taken.";
            } else {
                // Verify that the passwords match using password_verify()
                if (password_verify($raw_cpass, $pass)) {
                    // Prepare insert SQL statement for account registration
                    $insertSql = "INSERT INTO users (f_name, l_name, email, pass, region, province, city, barangay, street, zone, phone)
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    if ($stmt = $conn->prepare($insertSql)) {
                        $stmt->bind_param(
                            "sssssssssss",
                            $f_name,
                            $l_name,
                            $email,
                            $pass,
                            $region,
                            $province,
                            $city,
                            $barangay,
                            $street,
                            $zone,
                            $phone
                        );
                        // Execute the prepared statement
                        if ($stmt->execute()) {
                            $result = "Account Open success";
                            // Redirect to login page upon successful registration
                            header("Location: login.php");
                            exit();
                        } else {
                            $result = "Error: " . $stmt->error;
                        }
                        $stmt->close();
                    } else {
                        $result = "Error preparing statement: " . $conn->error;
                    }
                } else {
                    $result = "Password Not Match";
                }
            }
            $stmt_check->close();
        } else {
            $result = "Error checking email: " . $conn->error;
        }
    }
}
?>


<!DOCTYPE html>
<html lang="zxx">

<head>
    <meta charset="UTF-8">
    <meta name="description" content="Fashi Template">
    <meta name="keywords" content="Fashi, unica, creative, html">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css?family=Muli:300,400,500,600,700,800,900&display=swap" rel="stylesheet">

    <!-- Css Styles -->
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
    <!-- Page Preloder -->
    <!-- <div id="preloder">
        <div class="loader"></div>
    </div> -->

   

    <!-- Breadcrumb Section Begin -->
    <div class="breacrumb-section">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="breadcrumb-text">
                        <a href="index.php"><i class="fa fa-home"></i> Home</a>

                        <span>Register</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Breadcrumb Form Section Begin -->
     
    <!-- Register Section Begin -->
    <div class="register-login-section spad">
        <div class="container">
            <div class="row">
                <div class="col-lg-6 offset-lg-3">
                    <div class="register-form">
                        <h2>Register</h2>
                        <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
                            <div class="text-center mb-4">
                                    <?php if ($result) {
                                        echo "<div class='alert alert-info'>$result</div>";
                                    } ?>
                                    <?php if ($email_error) {
                                        echo "<div class='alert alert-danger'>$email_error</div>";
                                    } ?>
                                </div>
                            <div class="form-group row">
                                    <div class="col-sm-6 mb-3 mb-sm-0">
                                        <label for="fname">First Name</label>
                                        <input type="text" class="form-control form-control-user" id="exampleFirstName" placeholder="First Name" name="u_name" required>
                                    </div>
                                    <div class="col-sm-6">
                                        <label for="lname">Last Name</label>
                                        <input type="text" class="form-control form-control-user" id="exampleLastName" placeholder="Last Name" name="l_name" required>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="street">Street Number/House Number</label>
                                    <input type="text" class="form-control" id="street" name="street" required>
                                </div>
                                <div class="form-group">
                                    <label for="zone">Zone (optional)</label>
                                    <input type="text" class="form-control" id="zone" name="zone">
                                </div>
                                <!-- Philippine Address Dropdowns START -->
                                <div class="form-group">
                                    <label for="region">Region</label>
                                    <select id="region" class="form-control" required></select>
                                    <input type="hidden" name="region_text" id="region-text">
                                </div>
                                <div class="form-group">
                                    <label for="province">Province</label>
                                    <select id="province" class="form-control" required></select>
                                    <input type="hidden" name="province_text" id="province-text">
                                </div>
                                <div class="form-group">
                                    <label for="city">City/Municipality</label>
                                    <select id="city" class="form-control" required></select>
                                    <input type="hidden" name="city_text" id="city-text">
                                </div>
                                <div class="form-group">
                                    <label for="barangay">Barangay</label>
                                    <select id="barangay" class="form-control" required></select>
                                    <input type="hidden" name="barangay_text" id="barangay-text">
                                </div>
                                <!-- Philippine Address Dropdowns END -->
                                <div class="form-group">
                                    <label for="email">Email</label>
                                    <input type="email" class="form-control form-control-user" id="exampleInputEmail" placeholder="Email Address" name="email" required>
                                </div>
                                <div class="form-group">
                                    <label for="phone">Phone Number</label>
                                    <input type="tel" class="form-control form-control-user" id="phone" placeholder="09123456789" name="phone" pattern="[0-9]{11}" maxlength="11" required>
                                    <small class="form-text text-muted">Format: 09123456789 (11 digits starting with 09)</small>
                                </div>
                                <div class="form-group row">
                                    <div class="col-sm-6 mb-3 mb-sm-0">
                                        <label for="pass">Password</label>
                                        <input type="password" class="form-control form-control-user" id="exampleInputPassword" placeholder="Password" name="pass" required>
                                    </div>
                                    <div class="col-sm-6">
                                        <label for="c_pass">Confirm Password</label>
                                        <input type="password" class="form-control form-control-user" id="exampleRepeatPassword" placeholder="Repeat Password" name="c_pass" required>
                                    </div>
                                </div>
                            <button type="submit" class="site-btn register-btn" name="u_submit">REGISTER</button>
                        </form>
                        <div class="switch-login">
                            <a href="./login.php" class="or-login">Already have an account? Login!</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Register Form Section End -->
    


    <!-- Footer Section Begin -->
    <footer class="footer-section">
        <div class="container">
            <div class="row">
                <div class="col-lg-3">
                    <div class="footer-left">
                        <div class="footer-logo">
                            <a href="#"><img src="img/amLogo.jpg" alt=""></a>
                        </div>
                        <ul>
                            <li>Address: Apalit, Pampanga</li>
                            <li>Phone: +63 912 3456 789</li>
                            <li>Email: exampler@gmail.com</li>
                        </ul>
                        <div class="footer-social">
                                    <a href="https://www.facebook.com/amclosetapalit"><i class="fa fa-facebook"></i></a>
                            <a href="#"><i class="fa fa-instagram"></i></a>
                            <a href="#"><i class="fa fa-twitter"></i></a>
                            <a href="#"><i class="fa fa-pinterest"></i></a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2 offset-lg-1">
                    <div class="footer-widget">
                        <h5>Information</h5>
                        <ul>
                            <li>                            <li><a href="blog-details.php">About Us</a></li>
</li>
                            <li>                            <li><a href="check-out.php">Checkout</a></li>
</li>
                            <li><a href="contact.php">Contact</a></li>
                            <li><a href="#">Serivius</a></li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-2">
                    <div class="footer-widget">
                        <h5>My Account</h5>
                        <ul>
                               <li><a href="myAccount.php">My Account</a></li>
                            <li><a href="contact.php">Contact</a></li>
                            <li><a href="shopping-cart.php">Shopping Cart</a></li>

                            <li>                            <li><a href="shop.php">Shop</a></li></li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="newslatter-item">
                        <h5>Join Our Newsletter Now</h5>
                        <p>Get E-mail updates about our latest shop and special offers.</p>
                        <form action="#" class="subscribe-form">
                            <input type="text" placeholder="Enter Your Mail">
                            <button type="button">Subscribe</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="copyright-reserved">
            <div class="container">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="copyright-text">
                            <!-- Link back to Colorlib can't be removed. Template is licensed under CC BY 3.0. -->
Copyright &copy;<script>document.write(new Date().getFullYear());</script> All rights reserved <!-- Link back to Colorlib can't be removed. Template is licensed under CC BY 3.0. -->
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </footer>
    <!-- Footer Section End -->

    <!-- Js Plugins -->
    <script src="js/jquery-3.6.0.min.js"></script>
    <script src="js/jquery-3.3.1.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/jquery-ui.min.js"></script>
    <script src="js/jquery.countdown.min.js"></script>
    <script src="js/jquery.nice-select.min.js"></script>
    <script src="js/jquery.zoom.min.js"></script>
    <script src="js/jquery.dd.min.js"></script>
    <script src="js/jquery.slicknav.js"></script>
    <script src="js/ph-address-selector.js"></script>
    <script src="js/owl.carousel.min.js"></script>
    <script src="js/main.js"></script>

    <script>
        document.querySelector('form').addEventListener('submit', function(e) {
            var pass = document.getElementById('exampleInputPassword').value;
            var cpass = document.getElementById('exampleRepeatPassword').value;
            var phone = document.getElementById('phone').value;
            var error = "";

            // Phone number validation
            var phoneClean = phone.replace(/[^0-9]/g, '');
            if (phoneClean.length !== 11) {
                error = "Phone number must be exactly 11 digits.";
            } else if (!phoneClean.startsWith('09')) {
                error = "Phone number must start with '09'.";
            } else if (!/^09\d{9}$/.test(phoneClean)) {
                error = "Invalid phone number format. Use: 09123456789";
            }

            // Password validation
            if (!error) {
                if (
                    pass.length < 8 ||
                    !/[A-Z]/.test(pass) ||
                    !/[a-z]/.test(pass) ||
                    !/[0-9]/.test(pass) ||
                    !/[\W_]/.test(pass)
                ) {
                    error = "Password must be at least 8 characters and include uppercase, lowercase, number, and special character.";
                } else if (pass !== cpass) {
                    error = "Passwords do not match.";
                }
            }

            if (error) {
                alert(error);
                e.preventDefault();
            }
        });

        // Real-time phone number formatting
        document.getElementById('phone').addEventListener('input', function(e) {
            var value = e.target.value.replace(/[^0-9]/g, '');
            if (value.length > 11) {
                value = value.substring(0, 11);
            }
            e.target.value = value;
        });
    </script>

</body>

</html>