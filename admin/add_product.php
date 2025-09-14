<?php
session_start();

// Auth check
if (!isset($_SESSION['admin_auth']) || $_SESSION['admin_auth'] != 1) {
    header('Location: a_login.php');
    exit();
}

include 'header.php';
include 'lib/connection.php';

$result = null;

// Fetch existing categories for dropdown
$categories = [];
$catRes = mysqli_query($conn, "SELECT DISTINCT category FROM product ORDER BY category");
if ($catRes) {
    while ($crow = mysqli_fetch_assoc($catRes)) {
        if (!empty($crow['category'])) {
            $categories[] = $crow['category'];
        }
    }
}

if (isset($_POST['submit'])) {
    // Normalize product name: trim, collapse multiple spaces, Title Case (UTF-8 aware)
    $name_raw = isset($_POST['name']) ? trim($_POST['name']) : '';
    $name_clean = preg_replace('/\s+/', ' ', $name_raw);
    if (function_exists('mb_convert_case')) {
        $name = mb_convert_case($name_clean, MB_CASE_TITLE, 'UTF-8');
    } else {
        $name = ucwords(strtolower($name_clean));
    }

    // category may come from select or new_category input
    if (!empty($_POST['category_select']) && $_POST['category_select'] !== 'new') {
        $category = $_POST['category_select'];
    } else {
        $category = trim($_POST['new_category'] ?? '');
    }

    $tag = $_POST['tags'] ?? '';
    $description = $_POST['description'] ?? '';
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;
    $price = isset($_POST['price']) ? (float)$_POST['price'] : 0.0;

    // Handle multiple files: require at least 1 image and allow up to 5
    $uploadedFiles = $_FILES['uploadfile'] ?? null;
    $fileCount = 0;
    if ($uploadedFiles && is_array($uploadedFiles['name'])) {
        $fileCount = count(array_filter($uploadedFiles['name']));
    }

    if ($fileCount < 1) {
        $result = "<div class='alert alert-danger'>Please upload at least 1 image.</div>";
    } elseif ($fileCount > 5) {
        $result = "<div class='alert alert-danger'>You may upload a maximum of 5 images per product.</div>";
    } else {
        $savedNames = [];
        $errors = [];

        // Ensure upload directory exists
        $uploadDir = __DIR__ . '/uploaded_products/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Validation rules
        $allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $allowedMime = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $maxSize = 5 * 1024 * 1024; // 5 MB

        // use finfo for MIME sniffing
        $finfo = finfo_open(FILEINFO_MIME_TYPE);

        for ($i = 0; $i < $fileCount; $i++) {
            $origName = isset($uploadedFiles['name'][$i]) ? $uploadedFiles['name'][$i] : '';
            $tmpName = isset($uploadedFiles['tmp_name'][$i]) ? $uploadedFiles['tmp_name'][$i] : '';
            $errorCode = isset($uploadedFiles['error'][$i]) ? $uploadedFiles['error'][$i] : UPLOAD_ERR_NO_FILE;
            $size = isset($uploadedFiles['size'][$i]) ? (int)$uploadedFiles['size'][$i] : 0;

            if ($errorCode !== UPLOAD_ERR_OK) {
                $errors[] = "File '{$origName}' failed to upload (error code: {$errorCode}).";
                continue;
            }

            if (!is_uploaded_file($tmpName)) {
                $errors[] = "File '{$origName}' is not a valid uploaded file.";
                continue;
            }

            if ($size > $maxSize) {
                $errors[] = "File '{$origName}' exceeds the maximum allowed size of 5MB.";
                continue;
            }

            $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedExt)) {
                $errors[] = "File '{$origName}' has an unsupported extension.";
                continue;
            }

            $detectedMime = finfo_file($finfo, $tmpName);
            if ($detectedMime === false || !in_array($detectedMime, $allowedMime)) {
                $errors[] = "File '{$origName}' does not appear to be a valid image.";
                continue;
            }

            // create a unique, sanitized filename
            $base = pathinfo($origName, PATHINFO_FILENAME);
            $base = preg_replace('/[^A-Za-z0-9_-]/', '_', $base);
            $unique = uniqid($base . '_', true) . '.' . $ext;
            $dest = $uploadDir . $unique;

            if (move_uploaded_file($tmpName, $dest)) {
                // optionally tighten permissions
                @chmod($dest, 0644);
                $savedNames[] = $unique;

                // Resize original to a max dimension to save space (if needed)
                if (function_exists('getimagesize') && function_exists('imagecreatetruecolor')) {
                    $maxOriginal = 1600; // maximum width/height for originals
                    $origInfo = @getimagesize($dest);
                    if ($origInfo !== false) {
                        $ow = $origInfo[0];
                        $oh = $origInfo[1];
                        $omime = $origInfo['mime'];
                        if ($ow > $maxOriginal || $oh > $maxOriginal) {
                            // create image resource from original
                            switch ($omime) {
                                case 'image/jpeg':
                                    $oSrc = @imagecreatefromjpeg($dest);
                                    break;
                                case 'image/png':
                                    $oSrc = @imagecreatefrompng($dest);
                                    break;
                                case 'image/gif':
                                    $oSrc = @imagecreatefromgif($dest);
                                    break;
                                case 'image/webp':
                                    $oSrc = function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($dest) : false;
                                    break;
                                default:
                                    $oSrc = false;
                            }

                            if ($oSrc !== false) {
                                if ($ow > $oh) {
                                    $nw = $maxOriginal;
                                    $nh = intval($oh * ($maxOriginal / $ow));
                                } else {
                                    $nh = $maxOriginal;
                                    $nw = intval($ow * ($maxOriginal / $oh));
                                }
                                $resized = imagecreatetruecolor($nw, $nh);
                                if ($omime === 'image/png' || $omime === 'image/webp') {
                                    imagealphablending($resized, false);
                                    imagesavealpha($resized, true);
                                    $transparent = imagecolorallocatealpha($resized, 255, 255, 255, 127);
                                    imagefilledrectangle($resized, 0, 0, $nw, $nh, $transparent);
                                }
                                imagecopyresampled($resized, $oSrc, 0, 0, 0, 0, $nw, $nh, $ow, $oh);
                                // overwrite original with resized version
                                switch ($omime) {
                                    case 'image/jpeg':
                                        imagejpeg($resized, $dest, 90);
                                        break;
                                    case 'image/png':
                                        imagepng($resized, $dest, 6);
                                        break;
                                    case 'image/gif':
                                        imagegif($resized, $dest);
                                        break;
                                    case 'image/webp':
                                        if (function_exists('imagewebp')) {
                                            imagewebp($resized, $dest, 85);
                                        }
                                        break;
                                }
                                @chmod($dest, 0644);
                                imagedestroy($oSrc);
                                imagedestroy($resized);
                            }
                        }
                    }
                }

                // attempt to create thumbnail in thumbs/ directory using GD
                if (function_exists('getimagesize') && function_exists('imagecreatetruecolor')) {
                    $thumbMax = 300; // max width/height for thumbnail
                    $thumbsDir = $uploadDir . 'thumbs/';
                    if (!is_dir($thumbsDir)) {
                        mkdir($thumbsDir, 0755, true);
                    }
                    $thumbPath = $thumbsDir . $unique;
                    $imgInfo = @getimagesize($dest);
                    if ($imgInfo !== false) {
                        $width = $imgInfo[0];
                        $height = $imgInfo[1];
                        $mime = $imgInfo['mime'];
                        switch ($mime) {
                            case 'image/jpeg':
                                $srcImg = @imagecreatefromjpeg($dest);
                                break;
                            case 'image/png':
                                $srcImg = @imagecreatefrompng($dest);
                                break;
                            case 'image/gif':
                                $srcImg = @imagecreatefromgif($dest);
                                break;
                            case 'image/webp':
                                if (function_exists('imagecreatefromwebp')) {
                                    $srcImg = @imagecreatefromwebp($dest);
                                } else {
                                    $srcImg = false;
                                }
                                break;
                            default:
                                $srcImg = false;
                        }

                        if ($srcImg !== false) {
                            // calculate new dimensions preserving aspect
                            if ($width > $height) {
                                $new_w = $thumbMax;
                                $new_h = intval($height * ($thumbMax / $width));
                            } else {
                                $new_h = $thumbMax;
                                $new_w = intval($width * ($thumbMax / $height));
                            }
                            $thumbImg = imagecreatetruecolor($new_w, $new_h);
                            // preserve PNG transparency
                            if ($mime === 'image/png' || $mime === 'image/webp') {
                                imagealphablending($thumbImg, false);
                                imagesavealpha($thumbImg, true);
                                $transparent = imagecolorallocatealpha($thumbImg, 255, 255, 255, 127);
                                imagefilledrectangle($thumbImg, 0, 0, $new_w, $new_h, $transparent);
                            }
                            imagecopyresampled($thumbImg, $srcImg, 0, 0, 0, 0, $new_w, $new_h, $width, $height);
                            $savedThumb = false;
                            switch ($mime) {
                                case 'image/jpeg':
                                    $savedThumb = imagejpeg($thumbImg, $thumbPath, 85);
                                    break;
                                case 'image/png':
                                    $savedThumb = imagepng($thumbImg, $thumbPath, 6);
                                    break;
                                case 'image/gif':
                                    $savedThumb = imagegif($thumbImg, $thumbPath);
                                    break;
                                case 'image/webp':
                                    if (function_exists('imagewebp')) {
                                        $savedThumb = imagewebp($thumbImg, $thumbPath, 85);
                                    }
                                    break;
                            }
                            if ($savedThumb) {
                                @chmod($thumbPath, 0644);
                            }
                            imagedestroy($srcImg);
                            imagedestroy($thumbImg);
                        }
                    }
                }
            } else {
                $errors[] = "Failed to move uploaded file '{$origName}'.";
            }
        }

        finfo_close($finfo);

        if (count($savedNames) === 0) {
            $msg = "Failed to save uploaded images.";
            if (!empty($errors)) {
                $msg .= ' Errors: ' . implode(' ', array_map('htmlspecialchars', $errors));
            }
            $result = "<div class='alert alert-danger'>" . $msg . "</div>";
        } else {
            if (!empty($errors)) {
                $result = "<div class='alert alert-warning'>Uploaded some files but some files were skipped: " . implode(' | ', array_map('htmlspecialchars', $errors)) . "</div>";
            }

            // store as comma-separated filenames
            $filename_str = implode(',', $savedNames);

            $stmt = $conn->prepare("INSERT INTO product(name, category, tags, description, quantity, price, imgname, lens_id, group_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if ($stmt === false) {
                die('Prepare failed: ' . $conn->error);
            }
            $stmt->bind_param("ssssidsss", $name, $category, $tag, $description, $quantity, $price, $filename_str, $lens_id, $group_id);

            if ($stmt->execute()) {
                // prefer session flash + redirect to avoid resubmit
                $_SESSION['success_message'] = 'Product inserted successfully.';
                header('Location: all_product.php');
                exit();
            } else {
                die("Error: " . $stmt->error);
            }
        }
    }
}
?>
                <meta charset="UTF-8">
                <meta http-equiv="X-UA-Compatible" content="IE=edge">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Add Product</title>
                <link rel="stylesheet" href="css/style.css">
                <script>
                    function previewImage(event) {
                        const files = event.target.files;
                        const container = document.getElementById('imagePreviewContainer');
                        const msg = document.getElementById('imageUploadMessage');
                        container.innerHTML = '';
                        msg.innerText = '';

                        // enforce max files client-side
                        if (files.length > 5) {
                            msg.innerText = 'You selected ' + files.length + ' files. Maximum allowed is 5. Please select up to 5 images.';
                            // clear the file input so user must re-select
                            try {
                                event.target.value = '';
                            } catch (e) {
                                // fallback for older browsers
                                event.target.type = 'text';
                                event.target.type = 'file';
                            }
                            return;
                        }

                        for (let i = 0; i < files.length; i++) {
                            const file = files[i];
                            const reader = new FileReader();
                            reader.onload = function(e) {
                                const img = document.createElement('img');
                                img.src = e.target.result;
                                img.style.maxWidth = '120px';
                                img.style.maxHeight = '120px';
                                img.style.border = '1px solid #ddd';
                                img.style.borderRadius = '4px';
                                img.style.padding = '5px';
                                container.appendChild(img);
                            };
                            reader.readAsDataURL(file);
                        }
                    }
                </script>
            </head>
            <body>
                <div class="container mt-5">
                    <?php echo $result; ?>
                    <h4 class="mb-4">Add Product</h4>
                    <div class="card p-4 shadow-sm">
                        <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="exampleInputName" class="form-label">Product Name</label>
                                <input type="text" name="name" class="form-control" id="exampleInputName" required>
                            </div>

                            <div class="mb-3">
                                <label for="category_select" class="form-label">Category</label>
                                <select name="category_select" id="category_select" class="form-control" required>
                                    <option value="">-- Select category --</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></option>
                                    <?php endforeach; ?>
                                    <option value="new">Add new category...</option>
                                </select>
                                <input type="text" name="new_category" id="new_category" class="form-control mt-2" placeholder="Enter new category" style="display:none;">
                            </div>
                            <script>
                                document.addEventListener('DOMContentLoaded', function() {
                                    var sel = document.getElementById('category_select');
                                    var newCat = document.getElementById('new_category');
                                    sel.addEventListener('change', function() {
                                        if (this.value === 'new') {
                                            newCat.style.display = 'block';
                                            newCat.required = true;
                                        } else {
                                            newCat.style.display = 'none';
                                            newCat.required = false;
                                        }
                                    });
                                });
                            </script>

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
                                <label for="uploadfile" class="form-label">Images (min 1, max 5)</label>
                                <input type="file" name="uploadfile[]" id="uploadfile" class="form-control-file" onchange="previewImage(event)" multiple required>
                                <div class="mt-2" id="imageUploadMessage" style="color: #b00; font-weight: 600;"></div>
                                <div class="mt-2" id="imagePreviewContainer" style="display: flex; gap: 8px; flex-wrap: wrap;"></div>
                            </div>


                            <div class="mb-3">
                                <label for="exampleInputName" class="form-label">Lens ID</label>
                                <input type="text" name="lens_id" class="form-control" id="exampleInputName" required>
                            </div>

                            <div class="mb-3">
                                <label for="exampleInputName" class="form-label">Group ID</label>
                                <input type="text" name="group_id" class="form-control" id="exampleInputName" required>
                            </div>

                            <button type="submit" name="submit" class="btn btn-primary">Submit</button>
                        </form>
                    </div>
                </div>
            </body>
            </html>