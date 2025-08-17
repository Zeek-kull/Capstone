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
    <title>Document</title>
</head>
<body>
    <div class="container">
      <?php echo $result;?>
        <h4>Add Product</h4>
    <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" enctype="multipart/form-data">
  <div class="mb-3">
    <label for="exampleInputName" class="form-label">Product Name</label>
    <input type="text" name="name" class="form-control" id="exampleInputName" required>
  </div>

  <div class="mb-3">
    <label for="exampleInputType" class="form-label">Category</label>
    <input type="text" name="category"  class="form-control" id="exampleInputType" required>
  </div>

  <div class="mb-3">
    <label for="exampleInputTag" class="form-label">Tag</label>
    <input type="text" name="tags" class="form-control" id="exampleInputTag" required>

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
    <input type="Number" name="price" class="form-control" id="exampleInputPrice" required>
  </div>

  <div class="mb-3">
        <label for="uploadfile" class="form-label">Image</label>
        <input type="file" name="uploadfile" required>
    </div>

  <button type="submit" name="submit" class="btn btn-primary">Submit</button>
  
</form>
    </div>
</body>
</html>