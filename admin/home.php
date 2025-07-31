<?php
include 'header.php';
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check admin session, not user session
if (!isset($_SESSION['admin_auth']) || $_SESSION['admin_auth'] != 1) {
    header("location: a_login.php");
    exit;
}
include 'lib/connection.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>

    <!-- Bootstrap CSS for responsive design -->
    <link href="../css/bootstrap.min.css" rel="stylesheet">

    <!-- Google Fonts for better typography -->
    <link href="../css/css2.css?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">

    <!-- Custom CSS
    <link rel="stylesheet" href="css/home.css"> -->

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
            padding-top: 50px;
        }

        .homebody {
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            padding: 30px;
        }

        h1 {
            color: #007bff;
            font-size: 32px;
            font-weight: 600;
            margin-bottom: 20px;
        }

        .welcome-card {
            border-radius: 12px;
            background-color: #ffffff;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            padding: 30px;
            text-align: center;
            margin-bottom: 20px;
        }

        .welcome-card h3 {
            color: #343a40;
            font-size: 24px;
            font-weight: 500;
        }

        .btn-custom {
            background-color: #007bff;
            color: white;
            border-radius: 5px;
            padding: 10px 20px;
            text-decoration: none;
            font-weight: 500;
            transition: background-color 0.3s ease;
        }

        .btn-custom:hover {
            background-color: #0056b3;
            text-decoration: none;
        }
    </style>
</head>

<body>
    <div class="container homebody">
        <div class="row">
            <div class="col-md-12">
                <div class="welcome-card">
                    <h1>Welcome To The Admin Panel</h1>
                    <h3>Manage your platform with ease and efficiency.</h3>
                    <p class="mt-4">Here you can manage orders, products, users, and much more. Make sure everything is
                        up-to-date!</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS and Popper.js -->
    <script src="../js/popper.min.js"></script>
    <script src="../js/bootstrap.min.js"></script>
</body>

</html>
