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

  $sql = "SELECT * FROM product";
  $result = $conn -> query ($sql);

 if(isset($_POST['update_update_btn'])){
  $name = $_POST['update_name'];
  $category = $_POST['update_category'];
  $tag = $_POST['update_tag'];
  $quantity = $_POST['update_quantity'];
  $price = $_POST['update_Price'];
  $update_id = $_POST['update_id'];
  $update_quantity_query = mysqli_query($conn, "UPDATE `product` SET quantity = '$quantity' , name='$name' , category='$category' , tags='$tag' , price='$price'  WHERE p_id = '$update_id'");
  if($update_quantity_query){
     header('location:all_product.php');
  };
};

 if(isset($_GET['remove'])){
  $remove_id = $_GET['remove'];
  mysqli_query($conn, "DELETE FROM `product` WHERE p_id = '$remove_id'");
  header('location:all_product.php');
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="css/pending_orders.css">

</head>
<body>

<div class="container pendingbody">
  <h5>All Product</h5>
<table class="table">
  <thead>
    <tr>
      <th scope="col">Image</th>
      <th scope="col">Name</th>
      <th scope="col">Category</th>
      <th scope="col">Tag</th>
      <th scope="col">Quantity</th>
      <th scope="col">Price</th>
      <th scope="col">Action</th>
    </tr>
  </thead>
  <tbody>
  <?php
          if (mysqli_num_rows($result) > 0) {
            // output data of each row
            while($row = mysqli_fetch_assoc($result)) {
              ?>
    <tr>
  <td><img src="product_img/<?php echo $row['imgname']; ?>" style="width:50px;"></td>
  <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
    <input type="hidden" name="update_id" value="<?php echo $row['p_id']; ?>">
    <td><input type="text" name="update_name" value="<?php echo $row['name']; ?>"></td>
    <td><input type="text" name="update_category" value="<?php echo $row['category']; ?>"></td>
    <td>
      <select name="update_tag">
        <option value="">-- Select Tag --</option>
        <option value="Men" <?php if($row['tags'] == "Men") echo "selected"; ?>>Men</option>
        <option value="Women" <?php if($row['tags'] == "Women") echo "selected"; ?>>Women</option>
        <option value="Kids" <?php if($row['tags'] == "Kids") echo "selected"; ?>>Kid's</option>
      </select>
    </td>
    <td><input type="number" name="update_quantity" value="<?php echo $row['quantity']; ?>"></td>
    <td><input type="number" name="update_Price" value="<?php echo $row['price']; ?>"></td>
    <td><input type="submit" value="update" name="update_update_btn"></td>
  </form>
  <td><a href="all_product.php?remove=<?php echo $row['p_id']; ?>">remove</a></td>
</tr>

    <?php 
    }
        } 
        else 
            echo "0 results";
        ?>
  </tbody>
</table>



</div>
    
</body>
</html>