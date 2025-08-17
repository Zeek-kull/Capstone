<?php 
    ob_start();
    session_start();
  include 'header.php';

// Redirect if already authenticated
if (isset($_SESSION['auth']) && $_SESSION['auth'] == 1) {
    header("location:index.php");
    exit;
}

include "lib/connection.php";

if (isset($_POST['submit'])) {
    // Sanitize and validate user inputs
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'];

    // Validate email format
    if (!$email) {
        $error_message = "Invalid email format";
    } else {
        // Prepared statement to prevent SQL injection
        // Select only needed columns and get the password hash
        $loginquery = "SELECT id, f_name, pass FROM users WHERE email = ?";
        $stmt = $conn->prepare($loginquery);
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $loginres = $stmt->get_result();

        // Check if user exists
        if ($loginres->num_rows > 0) {
            // Fetch user data
            $result = $loginres->fetch_assoc();
            $stored_hash = $result['pass'];
            
            // Verify password using password_verify() for bcrypt compatibility
            if (password_verify($password, $stored_hash)) {
                // Regenerate session ID for security
                session_regenerate_id(true);
                
                // Store user data in session
                $_SESSION['username'] = $result['f_name'];
                $_SESSION['userid'] = $result['id'];
                $_SESSION['auth'] = 1;
                $_SESSION['email'] = $email;
                
                header("location:index.php");
                exit;
            } else {
                $error_message = "Invalid email or password";
            }
        } else {
            $error_message = "Invalid email or password";
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

                        <span>Login</span>
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
                    <div class="login-form">
                        <h2>Login</h2>
                        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" class="w-100" >
                            <div class="group-input">
                                <label for="username">Email address</label>
                                <input type="email" class="form-control form-control-user" id="email" name="email" placeholder="Enter Email Address" required>
                            </div>
                            <div class="group-input">
                                <label for="pass">Password</label>
                                <input type="password" class="form-control form-control-user" id="password" name="password" placeholder="Password" required>
                            </div>
                            <div class="group-input gi-check">
                                
                            </div>
                            <input class="site-btn login-btn" type="submit" name="submit" value="Login">

                        </form>
                        <div class="switch-login">
                            <a href="./register.php" class="or-login">Or Create An Account</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Register Form Section End -->

    <?php include 'footer.php'; ?>   

    <!-- Js Plugins -->
    <script src="js/jquery-3.5.1.slim.min.js"></script>
    <script src="js/jquery-3.3.1.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/jquery-ui.min.js"></script>
    <script src="js/jquery.countdown.min.js"></script>
    <script src="js/jquery.nice-select.min.js"></script>
    <script src="js/jquery.zoom.min.js"></script>
    <script src="js/jquery.dd.min.js"></script>
    <script src="js/jquery.slicknav.js"></script>
    <script src="js/owl.carousel.min.js"></script>
    <script src="js/main.js"></script>
</body>

</html>