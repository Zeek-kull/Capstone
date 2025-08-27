<?php

SESSION_START();

if(isset($_SESSION['admin_auth']))
{
    if($_SESSION['admin_auth']!=1)
    {
        header("location:a_login.php");
    }
}
else
{
    header("location:a_login.php");
}

include 'header.php';
include 'lib/connection.php';
$result = null;

if (isset($_POST['submit'])) 
{
    $name = $_POST['name'];
    $category = $_POST['category'];
    $tag = $_POST['tags'];
    $description = $_POST['description'];
    $quantity = $_POST['quantity'];
    $price = $_POST['price'];
    $filename = $_FILES["uploadfile"]["name"];

    $stmt = $conn->prepare("INSERT INTO product(name, category, tags, description, quantity, price, imgname) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssids", $name, $category, $tag, $description, $quantity, $price, $filename);

    if ($stmt->execute()) {
        $result = "<div class='alert alert-success'>Data insert success</div>";
        $tempname = $_FILES["uploadfile"]["tmp_name"];
        $folder = "product_img/" . $filename;

        move_uploaded_file($tempname, $folder);
    } else {
        die("Error: " . $stmt->error);
    }
} 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="admin/css/style.css">
    <script>
        function previewImage(event) {
            const reader = new FileReader();
            reader.onload = function() {
                const output = document.getElementById('imagePreview');
                output.src = reader.result;
                output.style.display = 'block';
            }
            reader.readAsDataURL(event.target.files[0]);
        }
    </script>
</head>
<body>
    <div class="container mt-5">
      <?php echo $result;?>
        <h4 class="mb-4">Add Product</h4>
        <div class="card p-4 shadow-sm">
            <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="exampleInputName" class="form-label">Product Name</label>
                    <input type="text" name="name" class="form-control" id="exampleInputName" required>
                </div>

                <div class="mb-3">
                    <label for="exampleInputType" class="form-label">Category</label>
                    <input type="text" name="category" class="form-control" id="exampleInputType" required>
                </div>

                <div class="mb-3">
                    <label for="exampleInputTag" class="form-label">Tag</label>
                    <select name="tags" class="form-control" id="exampleInputTag" required>
                        <option value="Men">Men</option>
                        <option value="Women">Women</option>
                        <option value="Kid's">Kid's</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="exampleInputDescription" class="form-label">Description</label>
                    <input type="text" name="description" class="form-control" id="exampleInputDescription" required>
                </div>

                <div class="mb-3">
                    <label for="exampleInputQuantity" class="form-label">Quantity</label>
                    <input type="number" name="quantity" class="form-control" id="exampleInputQuantity" required>
                </div>

                <div class="mb-3">
                    <label for="exampleInputPrice" class="form-label">Price</label>
                    <input type="number" name="price" class="form-control" id="exampleInputPrice" required>
                </div>

                <div class="mb-3">
                    <label for="uploadfile" class="form-label">Image</label>
                    <input type="file" name="uploadfile" class="form-control-file" onchange="previewImage(event)" required>
                    <div class="mt-2">
                        <img id="imagePreview" src="#" alt="Image Preview" style="display: none; max-width: 200px; max-height: 200px; border: 1px solid #ddd; border-radius: 4px; padding: 5px;">
                    </div>
                </div>

                <button type="submit" name="submit" class="btn btn-primary">Submit</button>
            </form>
        </div>
    </div>
</body>
</html>
