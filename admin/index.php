<?php
/**
 * Main Admin Dashboard for OXO Furniture
 * Professional control center with tabbed navigation (Products, Analytics, Settings).
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../includes/db.php';

// Force authentication
require_admin_login();

$db = get_db_connection();
$message = '';
$message_type = 'success';

// Determine active tab (default: 'products')
$current_tab = isset($_GET['tab']) ? trim($_GET['tab']) : 'products';
if ($current_tab === 'brands') {
    $current_tab = 'collections';
}
$valid_tabs = ['products', 'add_product', 'analytics', 'settings', 'collections'];
if (!in_array($current_tab, $valid_tabs)) {
    $current_tab = 'products';
}

// 1. ACTION: Handle product deletion
if ($current_tab === 'products' && isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $delete_id = trim($_GET['id']);
    if ($db) {
        try {
            // First fetch the image path to delete the file if it's a local upload
            $img_stmt = $db->prepare("SELECT `image` FROM `oxo_products` WHERE `id` = ?");
            $img_stmt->execute([$delete_id]);
            $img_path = $img_stmt->fetchColumn();
            
            // Delete product entry
            $stmt = $db->prepare("DELETE FROM `oxo_products` WHERE `id` = ?");
            $stmt->execute([$delete_id]);
            
            if ($stmt->rowCount() > 0) {
                if ($img_path && file_exists(__DIR__ . '/../' . $img_path) && strpos($img_path, 'uploads/') !== false) {
                    @unlink(__DIR__ . '/../' . $img_path);
                }
                $message = "Creation '{$delete_id}' was successfully archived and deleted.";
                $message_type = 'success';
            } else {
                $message = "Product not found or already deleted.";
                $message_type = 'danger';
            }
        } catch (\Exception $e) {
            $message = "Failed to delete product: " . $e->getMessage();
            $message_type = 'danger';
        }
    } else {
        $message = "Database offline. Deletion disabled.";
        $message_type = 'danger';
    }
}

// 2. ACTION: Handle marking inquiry as addressed
if ($current_tab === 'analytics' && isset($_GET['action']) && $_GET['action'] === 'address' && isset($_GET['inquiry_id'])) {
    $inquiry_id = (int)$_GET['inquiry_id'];
    if ($db) {
        try {
            $stmt = $db->prepare("UPDATE `oxo_consultations` SET `status` = 'Addressed' WHERE `id` = ?");
            $stmt->execute([$inquiry_id]);
            
            if ($stmt->rowCount() > 0) {
                $message = "Consultation inquiry marked as addressed.";
                $message_type = 'success';
            }
        } catch (\Exception $e) {
            $message = "Failed to update inquiry: " . $e->getMessage();
            $message_type = 'danger';
        }
    }
}

// 3. ACTION: Handle Password Reset
if ($current_tab === 'settings' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_action']) && $_POST['form_action'] === 'reset_password') {
    $current_pass = isset($_POST['current_password']) ? $_POST['current_password'] : '';
    $new_pass = isset($_POST['new_password']) ? $_POST['new_password'] : '';
    $confirm_pass = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
    
    if (empty($current_pass) || empty($new_pass) || empty($confirm_pass)) {
        $message = "All password fields are required.";
        $message_type = 'danger';
    } elseif ($new_pass !== $confirm_pass) {
        $message = "New password and confirmation password do not match.";
        $message_type = 'danger';
    } elseif (strlen($new_pass) < 6) {
        $message = "New password must be at least 6 characters long.";
        $message_type = 'danger';
    } else {
        if ($db) {
            try {
                // Fetch current admin credentials
                $stmt = $db->prepare("SELECT `password` FROM `oxo_admins` WHERE `username` = ?");
                $stmt->execute([$_SESSION['admin_username']]);
                $hashed_pass = $stmt->fetchColumn();
                
                if ($hashed_pass && password_verify($current_pass, $hashed_pass)) {
                    // Update password
                    $new_hashed_pass = password_hash($new_pass, PASSWORD_DEFAULT);
                    $update_stmt = $db->prepare("UPDATE `oxo_admins` SET `password` = ? WHERE `username` = ?");
                    $update_stmt->execute([$new_hashed_pass, $_SESSION['admin_username']]);
                    
                    $message = "Password successfully updated.";
                    $message_type = 'success';
                } else {
                    $message = "Current password is incorrect.";
                    $message_type = 'danger';
                }
            } catch (\Exception $e) {
                $message = "Database error: " . $e->getMessage();
                $message_type = 'danger';
            }
        } else {
            $message = "Database offline. Password update disabled.";
            $message_type = 'danger';
        }
    }
}

// 4. ACTION: Handle Brand Deletion
if ($current_tab === 'collections' && isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $delete_brand_id = (int)$_GET['id'];
    if ($db) {
        try {
            // First fetch the logo path to delete the file if it's local
            $img_stmt = $db->prepare("SELECT `logo_path` FROM `oxo_brands` WHERE `id` = ?");
            $img_stmt->execute([$delete_brand_id]);
            $logo_path = $img_stmt->fetchColumn();
            
            $stmt = $db->prepare("DELETE FROM `oxo_brands` WHERE `id` = ?");
            $stmt->execute([$delete_brand_id]);
            
            if ($stmt->rowCount() > 0) {
                if ($logo_path && file_exists(__DIR__ . '/../' . $logo_path) && strpos($logo_path, 'uploads/') !== false) {
                    @unlink(__DIR__ . '/../' . $logo_path);
                }
                $message = "Brand logo was successfully deleted.";
                $message_type = 'success';
            }
        } catch (\Exception $e) {
            $message = "Failed to delete brand: " . $e->getMessage();
            $message_type = 'danger';
        }
    }
}

// 5. ACTION: Handle Brand Insertion
if ($current_tab === 'collections' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_action']) && $_POST['form_action'] === 'add_brand') {
    $brand_name = isset($_POST['brand_name']) ? trim($_POST['brand_name']) : '';
    $logo_url = isset($_POST['logo_url']) ? trim($_POST['logo_url']) : '';
    $logo_path = '';
    
    if (empty($brand_name)) {
        $message = "Brand Name is required.";
        $message_type = 'danger';
    } else {
        if ($db) {
            try {
                // Handle File Upload
                if (isset($_FILES['logo_file']) && $_FILES['logo_file']['error'] === UPLOAD_ERR_OK) {
                    $upload_dir = __DIR__ . '/../assets/images/uploads/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    
                    $file_name = basename($_FILES['logo_file']['name']);
                    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                    $allowed_exts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
                    
                    if (in_array($file_ext, $allowed_exts)) {
                        $new_file_name = 'brand_' . time() . '_' . rand(100, 999) . '.' . $file_ext;
                        $target_file = $upload_dir . $new_file_name;
                        
                        if (move_uploaded_file($_FILES['logo_file']['tmp_name'], $target_file)) {
                            $logo_path = 'assets/images/uploads/' . $new_file_name;
                        } else {
                            $message = "Failed to upload logo image file.";
                            $message_type = 'danger';
                        }
                    } else {
                        $message = "Invalid image file type. Only PNG, JPG, JPEG, WEBP and GIF are allowed.";
                        $message_type = 'danger';
                    }
                } elseif (!empty($logo_url)) {
                    $logo_path = $logo_url;
                }
                
                if (empty($message)) {
                    $stmt = $db->prepare("INSERT INTO `oxo_brands` (`name`, `logo_path`) VALUES (?, ?)");
                    $stmt->execute([$brand_name, $logo_path]);
                    
                    $message = "Brand '{$brand_name}' added successfully.";
                    $message_type = 'success';
                }
            } catch (\Exception $e) {
                $message = "Database error: " . $e->getMessage();
                $message_type = 'danger';
            }
        } else {
            $message = "Database offline. Adding brand disabled.";
            $message_type = 'danger';
        }
    }
}

// 6. ACTION: Handle Category Deletion
if ($current_tab === 'collections' && isset($_GET['action']) && $_GET['action'] === 'delete_category' && isset($_GET['id'])) {
    $delete_cat_id = (int)$_GET['id'];
    if ($db) {
        try {
            // Get category slug
            $slug_stmt = $db->prepare("SELECT `slug` FROM `oxo_categories` WHERE `id` = ?");
            $slug_stmt->execute([$delete_cat_id]);
            $cat_slug = $slug_stmt->fetchColumn();
            
            if ($cat_slug) {
                // Delete category
                $stmt = $db->prepare("DELETE FROM `oxo_categories` WHERE `id` = ?");
                $stmt->execute([$delete_cat_id]);
                
                $message = "Category '{$cat_slug}' deleted successfully.";
                $message_type = 'success';
            }
        } catch (\Exception $e) {
            $message = "Failed to delete category: " . $e->getMessage();
            $message_type = 'danger';
        }
    }
}

// 7. ACTION: Handle Category Addition
if ($current_tab === 'collections' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_action']) && $_POST['form_action'] === 'add_category') {
    $cat_name = isset($_POST['cat_name']) ? trim($_POST['cat_name']) : '';
    $cat_slug = isset($_POST['cat_slug']) ? trim($_POST['cat_slug']) : '';
    
    if (empty($cat_name) || empty($cat_slug)) {
        $message = "Category Name and Slug are required.";
        $message_type = 'danger';
    } else {
        // Enforce lowercase alphanumeric slug
        $cat_slug = preg_replace('/[^a-z0-9-]/', '', strtolower($cat_slug));
        
        if ($db) {
            try {
                $stmt = $db->prepare("INSERT INTO `oxo_categories` (`slug`, `name`) VALUES (?, ?)");
                $stmt->execute([$cat_slug, $cat_name]);
                
                $message = "Category '{$cat_name}' added successfully.";
                $message_type = 'success';
            } catch (\Exception $e) {
                $message = "Failed to add category: " . $e->getMessage();
                $message_type = 'danger';
            }
        }
    }
}

// 8. ACTION: Handle Material Deletion
if ($current_tab === 'collections' && isset($_GET['action']) && $_GET['action'] === 'delete_material' && isset($_GET['id'])) {
    $delete_mat_id = (int)$_GET['id'];
    if ($db) {
        try {
            // Get material slug
            $slug_stmt = $db->prepare("SELECT `slug` FROM `oxo_materials` WHERE `id` = ?");
            $slug_stmt->execute([$delete_mat_id]);
            $mat_slug = $slug_stmt->fetchColumn();
            
            if ($mat_slug) {
                // Delete material
                $stmt = $db->prepare("DELETE FROM `oxo_materials` WHERE `id` = ?");
                $stmt->execute([$delete_mat_id]);
                
                $message = "Material '{$mat_slug}' deleted successfully.";
                $message_type = 'success';
            }
        } catch (\Exception $e) {
            $message = "Failed to delete material: " . $e->getMessage();
            $message_type = 'danger';
        }
    }
}

// 9. ACTION: Handle Material Addition
if ($current_tab === 'collections' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_action']) && $_POST['form_action'] === 'add_material') {
    $mat_name = isset($_POST['mat_name']) ? trim($_POST['mat_name']) : '';
    $mat_slug = isset($_POST['mat_slug']) ? trim($_POST['mat_slug']) : '';
    
    if (empty($mat_name) || empty($mat_slug)) {
        $message = "Material Name and Slug are required.";
        $message_type = 'danger';
    } else {
        // Enforce lowercase alphanumeric slug
        $mat_slug = preg_replace('/[^a-z0-9-]/', '', strtolower($mat_slug));
        
        if ($db) {
            try {
                $stmt = $db->prepare("INSERT INTO `oxo_materials` (`slug`, `name`) VALUES (?, ?)");
                $stmt->execute([$mat_slug, $mat_name]);
                
                $message = "Material '{$mat_name}' added successfully.";
                $message_type = 'success';
            } catch (\Exception $e) {
                $message = "Failed to add material: " . $e->getMessage();
                $message_type = 'danger';
            }
        }
    }
}

// 9b. ACTION: Handle Color Deletion
if ($current_tab === 'collections' && isset($_GET['action']) && $_GET['action'] === 'delete_color' && isset($_GET['id'])) {
    $delete_color_id = (int)$_GET['id'];
    if ($db) {
        try {
            // Get color name
            $name_stmt = $db->prepare("SELECT `name` FROM `oxo_colors` WHERE `id` = ?");
            $name_stmt->execute([$delete_color_id]);
            $color_name = $name_stmt->fetchColumn();
            
            if ($color_name) {
                // Delete color
                $stmt = $db->prepare("DELETE FROM `oxo_colors` WHERE `id` = ?");
                $stmt->execute([$delete_color_id]);
                
                $message = "Color '{$color_name}' deleted successfully.";
                $message_type = 'success';
            }
        } catch (\Exception $e) {
            $message = "Failed to delete color: " . $e->getMessage();
            $message_type = 'danger';
        }
    }
}

// 9c. ACTION: Handle Color Addition
if ($current_tab === 'collections' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_action']) && $_POST['form_action'] === 'add_color') {
    $color_name = isset($_POST['color_name']) ? trim($_POST['color_name']) : '';
    $color_hex = isset($_POST['color_hex']) ? trim($_POST['color_hex']) : '#ffffff';
    
    if (empty($color_name) || empty($color_hex)) {
        $message = "Color Name and HEX code are required.";
        $message_type = 'danger';
    } else {
        if ($db) {
            try {
                $stmt = $db->prepare("INSERT INTO `oxo_colors` (`name`, `hex`) VALUES (?, ?)");
                $stmt->execute([$color_name, $color_hex]);
                
                $message = "Color '{$color_name}' added successfully.";
                $message_type = 'success';
            } catch (\Exception $e) {
                $message = "Failed to add color: " . $e->getMessage();
                $message_type = 'danger';
            }
        }
    }
}

// 10. ACTION: Handle Product Addition from Add Product Tab
if ($current_tab === 'add_product' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_action']) && $_POST['form_action'] === 'add_product') {
    $prod_title = isset($_POST['title']) ? trim($_POST['title']) : '';
    // Auto-generate ID slug from Title
    $prod_id = strtolower(preg_replace('/[^a-zA-Z0-9\-]+/', '-', $prod_title));
    $prod_id = trim($prod_id, '-');
    
    $prod_price = isset($_POST['price']) ? (int)$_POST['price'] : 0;
    $prod_category = isset($_POST['category']) ? trim($_POST['category']) : '';
    $prod_material = isset($_POST['material_slug']) ? trim($_POST['material_slug']) : '';
    $prod_brand = isset($_POST['brand_id']) && $_POST['brand_id'] !== '' ? (int)$_POST['brand_id'] : null;
    $selected_color_ids = isset($_POST['color_ids']) && is_array($_POST['color_ids']) ? array_map('intval', $_POST['color_ids']) : [];
    $prod_color_id = !empty($selected_color_ids) ? $selected_color_ids[0] : null;
    $color_ids_json = !empty($selected_color_ids) ? json_encode($selected_color_ids) : null;
    $prod_desc = isset($_POST['description']) ? trim($_POST['description']) : '';
    
    $prod_height_cm = isset($_POST['height_cm']) ? (int)$_POST['height_cm'] : 85;
    $prod_width_cm = isset($_POST['width_cm']) ? (int)$_POST['width_cm'] : 100;
    $prod_length_cm = isset($_POST['length_cm']) ? (int)$_POST['length_cm'] : 240;
    $prod_specs = "Dimensions: W: {$prod_width_cm}cm x D: {$prod_length_cm}cm x H: {$prod_height_cm}cm";
    
    $prod_img_url = isset($_POST['image_url']) ? trim($_POST['image_url']) : '';
    $prod_gallery_url = isset($_POST['gallery_url']) ? trim($_POST['gallery_url']) : '';
    
    // Details
    $prod_details = [
        'Material' => isset($_POST['detail_material']) ? trim($_POST['detail_material']) : '',
        'Construction' => isset($_POST['detail_construction']) ? trim($_POST['detail_construction']) : '',
        'Care Instructions' => isset($_POST['detail_care']) ? trim($_POST['detail_care']) : '',
        'Shipping' => isset($_POST['detail_shipping']) ? trim($_POST['detail_shipping']) : ''
    ];
    
    $errors = [];
    
    // Validations
    if (empty($prod_title)) {
        $errors[] = "Product Title / Name is required.";
    } elseif (empty($prod_id)) {
        $errors[] = "A valid Product Title / Name is required to generate the identifier.";
    } else {
        if ($db) {
            $check_stmt = $db->prepare("SELECT COUNT(*) FROM `oxo_products` WHERE `id` = ?");
            $check_stmt->execute([$prod_id]);
            if ($check_stmt->fetchColumn() > 0) {
                // Suffix with short hash code if exact duplicate exists
                $prod_id .= '-' . substr(md5(time() . rand()), 0, 4);
            }
        }
    }
    
    if ($prod_price <= 0) {
        $errors[] = "Price must be a valid positive integer.";
    }
    
    // Unified Visual Assets handling
    $upload_map = [];
    if (isset($_FILES['gallery_files']) && is_array($_FILES['gallery_files']['name'])) {
        $upload_dir = __DIR__ . '/../assets/images/uploads/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        $allowed_exts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        
        for ($i = 0; $i < count($_FILES['gallery_files']['name']); $i++) {
            if ($_FILES['gallery_files']['error'][$i] === UPLOAD_ERR_OK) {
                $file_name = basename($_FILES['gallery_files']['name'][$i]);
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                
                if (in_array($file_ext, $allowed_exts)) {
                    $new_file_name = $prod_id . '_asset_' . $i . '_' . time() . '.' . $file_ext;
                    $target_file = $upload_dir . $new_file_name;
                    if (move_uploaded_file($_FILES['gallery_files']['tmp_name'][$i], $target_file)) {
                        $upload_map["file:{$i}"] = 'assets/images/uploads/' . $new_file_name;
                    }
                }
            }
        }
    }
    
    // Parse order JSON
    $image_order_json = isset($_POST['image_order_json']) ? trim($_POST['image_order_json']) : '';
    $final_image_paths = [];
    
    if (!empty($image_order_json)) {
        $order = json_decode($image_order_json, true);
        if (is_array($order)) {
            foreach ($order as $item) {
                $item_id = is_array($item) ? $item['id'] : $item;
                $item_color = (is_array($item) && isset($item['color_id']) && $item['color_id'] !== '') ? (int)$item['color_id'] : null;
                
                if (strpos($item_id, 'file:') === 0) {
                    if (isset($upload_map[$item_id])) {
                        $final_image_paths[] = [
                            'path' => $upload_map[$item_id],
                            'color_id' => $item_color
                        ];
                    }
                } else {
                    $final_image_paths[] = [
                        'path' => $item_id,
                        'color_id' => $item_color
                    ];
                }
            }
        }
    }
    
    // Fallback: if no order was specified but files were uploaded
    if (empty($final_image_paths) && !empty($upload_map)) {
        foreach ($upload_map as $fpath) {
            $final_image_paths[] = [
                'path' => $fpath,
                'color_id' => null
            ];
        }
    }
    
    // Split into Primary Image (first item) and Gallery Images (rest of items)
    $image_path = '';
    $gallery_paths = [];
    if (!empty($final_image_paths)) {
        $image_path = $final_image_paths[0]['path'];
        $gallery_paths = array_slice($final_image_paths, 1);
        if (!empty($final_image_paths[0]['color_id'])) {
            $prod_color_id = $final_image_paths[0]['color_id'];
        }
    }
    if ($prod_color_id && !in_array((int)$prod_color_id, $selected_color_ids)) {
        $selected_color_ids[] = (int)$prod_color_id;
        $color_ids_json = json_encode($selected_color_ids);
    }
    
    if (empty($image_path)) {
        $errors[] = "At least one image is required. Please upload files or enter image URLs.";
    }
    
    $gallery_json = !empty($gallery_paths) ? json_encode($gallery_paths) : NULL;
    
    if (empty($errors)) {
        if ($db) {
            try {
                $json_details = json_encode($prod_details);
                 $stmt = $db->prepare("INSERT INTO `oxo_products` 
                    (`id`, `title`, `price`, `category`, `image`, `description`, `specs`, `details`, `material_slug`, `brand_id`, `gallery`, `height_cm`, `width_cm`, `length_cm`, `color_id`, `color_ids`) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $prod_id,
                    $prod_title,
                    $prod_price,
                    $prod_category,
                    $image_path,
                    $prod_desc,
                    $prod_specs,
                    $json_details,
                    $prod_material,
                    $prod_brand,
                    $gallery_json,
                    $prod_height_cm,
                    $prod_width_cm,
                    $prod_length_cm,
                    $prod_color_id,
                    $color_ids_json
                ]);
                
                // Redirect back to dashboard creations list with success message
                header("Location: index.php?tab=products&msg=added");
                exit;
            } catch (\Exception $e) {
                $errors[] = "Database insert failed: " . $e->getMessage();
            }
        } else {
            $errors[] = "Database offline. Product addition disabled.";
        }
    }
    
    if (!empty($errors)) {
        $message = implode("<br>", $errors);
        $message_type = 'danger';
    }
}

// Fetch dashboard data
$products = [];
$total_products = 0;
$categories_count = 0;
$average_price = 0;
$inquiries = [];
$total_inquiries = 0;
$pending_inquiries = 0;
$brands = [];
$categories_list = [];
$materials_list = [];
$colors_list = [];

if ($db) {
    try {
        // Fetch products
        $stmt = $db->query("SELECT * FROM `oxo_products` ORDER BY `created_at` DESC");
        $products = $stmt->fetchAll();
        $total_products = count($products);
        
        // Calculate product metrics
        if ($total_products > 0) {
            $prices = array_column($products, 'price');
            $average_price = array_sum($prices) / $total_products;
            $categories = array_unique(array_column($products, 'category'));
            $categories_count = count($categories);
        }
        
        // Fetch inquiries
        $stmt = $db->query("SELECT * FROM `oxo_consultations` ORDER BY `created_at` DESC");
        $inquiries = $stmt->fetchAll();
        $total_inquiries = count($inquiries);
        foreach ($inquiries as $inq) {
            if ($inq['status'] === 'Pending') {
                $pending_inquiries++;
            }
        }
        
        // Fetch brands
        $stmt = $db->query("SELECT * FROM `oxo_brands` ORDER BY `created_at` DESC");
        $brands = $stmt->fetchAll();
        
        // Fetch categories list
        $categories_list = $db->query("SELECT * FROM `oxo_categories` ORDER BY `name` ASC")->fetchAll();
        // Fetch materials list
        $materials_list = $db->query("SELECT * FROM `oxo_materials` ORDER BY `name` ASC")->fetchAll();
        // Fetch colors list
        $colors_list = $db->query("SELECT * FROM `oxo_colors` ORDER BY `name` ASC")->fetchAll();
    } catch (\Exception $e) {
        $message = "Failed to load dashboard data: " . $e->getMessage();
        $message_type = 'danger';
    }
} else {
    // Read-only fallback from array
    $db_file = __DIR__ . '/../includes/products-db.php';
    if (file_exists($db_file)) {
        include $db_file;
        $products = $PRODUCTS_DB;
        $total_products = count($products);
        if ($total_products > 0) {
            $prices = array_column($products, 'price');
            $average_price = array_sum($prices) / $total_products;
            $categories = array_unique(array_column($products, 'category'));
            $categories_count = count($categories);
        }
    }
    $message = "Warning: Database connection is offline. Showing read-only static files.";
    $message_type = 'danger';
}

/**
 * Format currency in Indian numbering system format (INR)
 */
if (!function_exists('format_inr_admin')) {
    function format_inr_admin($amount) {
        $amount = (int)$amount;
        $negative = $amount < 0 ? '-' : '';
        $amount = abs($amount);
        $num = (string)$amount;
        
        if (strlen($num) > 3) {
            $last_three = substr($num, -3);
            $remaining = substr($num, 0, -3);
            $remaining = preg_replace("/\B(?=(\d{2})+(?!\d))/", ",", $remaining);
            $result = $remaining . ',' . $last_three;
        } else {
            $result = $num;
        }
        
        return '₹' . $negative . $result;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OXO Control Studio — Admin Console</title>
    <!-- Fonts, Icons, and Chart.js -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@300;400;500;700;800&family=Playfair+Display:ital,wght@0,400;0,600;1,400&family=Syne:wght@700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin-style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="icon" type="image/png" href="../assets/images/logo.png">
</head>
<body>

    <div class="admin-container">
        <!-- Sidebar Navigation -->
        <aside class="admin-sidebar">
            <div class="sidebar-header">
                <a href="index.php">
                    <img src="../assets/images/logo.png" alt="OXO Premium Furniture" class="admin-logo-img">
                </a>
                <h1 class="admin-logo-text">OXO <span>Studio</span></h1>
            </div>
            
            <nav class="sidebar-nav">
                <a href="index.php?tab=products" class="sidebar-link <?php echo $current_tab === 'products' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-couch"></i> Creations
                </a>
                <a href="index.php?tab=add_product" class="sidebar-link <?php echo $current_tab === 'add_product' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-circle-plus"></i> Add Product
                </a>
                <a href="index.php?tab=analytics" class="sidebar-link <?php echo $current_tab === 'analytics' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-chart-line"></i> Analytics & Inquiries
                    <?php if ($pending_inquiries > 0): ?>
                        <span class="sidebar-badge"><?php echo $pending_inquiries; ?></span>
                    <?php endif; ?>
                </a>
                <a href="index.php?tab=collections" class="sidebar-link <?php echo $current_tab === 'collections' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-shapes"></i> Collections
                </a>
                <a href="index.php?tab=settings" class="sidebar-link <?php echo $current_tab === 'settings' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-gears"></i> Settings
                </a>
                <a href="../index.php" target="_blank" class="sidebar-link">
                    <i class="fa-solid fa-arrow-up-right-from-square"></i> View Store
                </a>
            </nav>
            
            <div class="sidebar-footer">
                <div class="user-info">
                    <span class="user-icon"><i class="fa-solid fa-user-shield"></i></span>
                    <span class="username-text"><?php echo htmlspecialchars(isset($_SESSION['admin_username']) ? $_SESSION['admin_username'] : 'Administrator'); ?></span>
                </div>
                <a href="logout.php" class="sidebar-logout"><i class="fa-solid fa-right-from-bracket"></i> Log Out</a>
            </div>
        </aside>

        <!-- Main Content Area -->
        <main class="admin-content">
        
        <!-- Tab Content Header -->
        <div class="page-header">
            <div>
                <?php if ($current_tab === 'products'): ?>
                    <h2 class="page-title">Curated <span>Creations</span></h2>
                    <p style="color: var(--color-gray); font-size: 0.95rem; margin-top: 5px;">Add, modify, and manage items in the luxury furniture catalog.</p>
                <?php elseif ($current_tab === 'analytics'): ?>
                    <h2 class="page-title">Analytics & <span>Inquiries</span></h2>
                    <p style="color: var(--color-gray); font-size: 0.95rem; margin-top: 5px;">Analyze sales metrics, view trends, and answer client consultation inquiries.</p>
                <?php elseif ($current_tab === 'brands'): ?>
                    <h2 class="page-title">Brand <span>Logos</span></h2>
                    <p style="color: var(--color-gray); font-size: 0.95rem; margin-top: 5px;">Manage partner brand logos displaying in the homepage sliding marquee.</p>
                <?php else: ?>
                    <h2 class="page-title">Studio <span>Settings</span></h2>
                    <p style="color: var(--color-gray); font-size: 0.95rem; margin-top: 5px;">Update credentials, modify database configurations, and control access keys.</p>
                <?php endif; ?>
            </div>
            
            <?php if ($current_tab === 'products' && $db): ?>
                <a href="product-editor.php" class="action-btn">
                    <i class="fa-solid fa-plus"></i> Add New Creation
                </a>
            <?php endif; ?>
        </div>

        <!-- System Alerts -->
        <?php if (!empty($message)): ?>
            <div class="alert <?php echo $message_type === 'success' ? 'alert-success' : ''; ?>" style="margin-bottom: 30px;">
                <i class="fa-solid <?php echo $message_type === 'success' ? 'fa-circle-check' : 'fa-triangle-exclamation'; ?>" style="margin-right: 8px;"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- ==================== TABS SWITCH PANEL ==================== -->

        <!-- TAB A: PRODUCTS TAB -->
        <div class="tab-container <?php echo $current_tab === 'products' ? 'active' : ''; ?>">
            <!-- Quick Stats -->
            <section class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fa-solid fa-couch"></i></div>
                    <div class="stat-info">
                        <h3>Catalog Size</h3>
                        <div class="stat-value"><?php echo $total_products; ?></div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon"><i class="fa-solid fa-tags"></i></div>
                    <div class="stat-info">
                        <h3>Active Categories</h3>
                        <div class="stat-value"><?php echo $categories_count; ?></div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon"><i class="fa-solid fa-hand-holding-dollar"></i></div>
                    <div class="stat-info">
                        <h3>Average Value</h3>
                        <div class="stat-value"><?php echo format_inr_admin($average_price); ?></div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon"><i class="fa-solid fa-shield-halved"></i></div>
                    <div class="stat-info">
                        <h3>System Status</h3>
                        <div class="stat-value" style="font-size: 1.15rem; color: <?php echo $db ? 'var(--color-success)' : 'var(--color-danger)'; ?>">
                            <?php echo $db ? '<i class="fa-solid fa-database"></i> MySQL Connected' : '<i class="fa-solid fa-file-code"></i> File Fallback'; ?>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Table -->
            <div class="table-card">
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th style="width: 80px;">Design</th>
                                <th style="width: 150px;">Product ID</th>
                                <th>Title</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th style="width: 120px; text-align: right;">Operations</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($products)): ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 40px; color: var(--color-gray);">
                                        No creations found in the catalog.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($products as $p): ?>
                                    <tr>
                                        <td>
                                            <img src="../<?php echo htmlspecialchars($p['image']); ?>" alt="" class="table-product-img" onerror="this.src='../assets/images/logo.png';">
                                        </td>
                                        <td>
                                            <code style="color: var(--color-accent); font-family: var(--font-numeric); font-weight: 600; font-size: 0.85rem; background: var(--color-gray-dark); padding: 4px 8px; border-radius: 4px; border: 1px solid var(--color-panel-border);">
                                                <?php echo htmlspecialchars($p['id']); ?>
                                            </code>
                                        </td>
                                        <td style="font-weight: 700; color: var(--color-primary);"><?php echo htmlspecialchars($p['title']); ?></td>
                                        <td>
                                            <span class="table-category-badge"><?php echo htmlspecialchars($p['category']); ?></span>
                                        </td>
                                        <td>
                                            <span class="table-price"><?php echo format_inr_admin($p['price']); ?></span>
                                        </td>
                                        <td style="text-align: right;">
                                            <div class="table-actions" style="justify-content: flex-end;">
                                                <?php if ($db): ?>
                                                    <a href="product-editor.php?action=edit&id=<?php echo urlencode($p['id']); ?>" class="btn-icon edit" title="Edit Design">
                                                        <i class="fa-solid fa-pen"></i>
                                                    </a>
                                                    <a href="index.php?tab=products&action=delete&id=<?php echo urlencode($p['id']); ?>" 
                                                       class="btn-icon delete" 
                                                       title="Delete Design"
                                                       onclick="return confirm('Are you sure you want to delete this premium creation: <?php echo htmlspecialchars($p['title']); ?>? This action cannot be undone.');">
                                                        <i class="fa-solid fa-trash-can"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <span style="color: var(--color-gray); font-size: 0.8rem; font-style: italic;">Read Only</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- TAB B: ANALYTICS & INQUIRIES TAB -->
        <div class="tab-container <?php echo $current_tab === 'analytics' ? 'active' : ''; ?>">
            
            <!-- Analytics Cards -->
            <section class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fa-solid fa-dollar-sign"></i></div>
                    <div class="stat-info">
                        <h3>Mock Gross Revenue</h3>
                        <div class="stat-value">₹14,85,000</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon"><i class="fa-solid fa-inbox"></i></div>
                    <div class="stat-info">
                        <h3>Consultations</h3>
                        <div class="stat-value"><?php echo $total_inquiries; ?> <span style="font-size: 0.9rem; font-weight: 500; color: var(--color-gray);">Total</span></div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon"><i class="fa-solid fa-clock"></i></div>
                    <div class="stat-info">
                        <h3>Pending Inquiries</h3>
                        <div class="stat-value" style="color: <?php echo $pending_inquiries > 0 ? 'var(--color-danger)' : 'var(--color-success)'; ?>">
                            <?php echo $pending_inquiries; ?>
                        </div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon"><i class="fa-solid fa-percent"></i></div>
                    <div class="stat-info">
                        <h3>Conversion Rate</h3>
                        <div class="stat-value">3.65%</div>
                    </div>
                </div>
            </section>

            <!-- Chart.js Graphics Grid -->
            <div class="charts-grid">
                <!-- Line Chart Card -->
                <div class="chart-box">
                    <h3 class="chart-title">Month-over-Month Revenue Trend (Mocked) <span style="font-size: 0.75rem; text-transform: none; color: var(--color-gray);">Gross value</span></h3>
                    <canvas id="revenueChart" style="max-height: 320px;"></canvas>
                </div>
                
                <!-- Category Doughnut Chart -->
                <div class="chart-box">
                    <h3 class="chart-title">Collection Distribution <span style="font-size: 0.75rem; text-transform: none; color: var(--color-gray);">Categories</span></h3>
                    <canvas id="categoryChart" style="max-height: 320px;"></canvas>
                </div>
            </div>

            <!-- Client Inquiries List -->
            <div class="page-header" style="margin-bottom: 25px;">
                <div>
                    <h3 class="page-title" style="font-size: 1.4rem;">Bespoke Client <span>Inquiries</span></h3>
                    <p style="color: var(--color-gray); font-size: 0.85rem; margin-top: 5px;">Client consultation requests submitted from product details page.</p>
                </div>
            </div>

            <div class="table-card">
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th style="width: 180px;">Client</th>
                                <th style="width: 180px;">Contact</th>
                                <th style="width: 180px;">Product Context</th>
                                <th>Message</th>
                                <th style="width: 120px;">Status</th>
                                <th style="width: 100px; text-align: right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($inquiries)): ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 40px; color: var(--color-gray);">
                                        No client inquiries received yet. Submit one from the product concierge.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($inquiries as $inq): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($inq['name']); ?></strong></td>
                                        <td>
                                            <a href="mailto:<?php echo htmlspecialchars($inq['email']); ?>" style="color: var(--color-accent); font-weight: 500; font-family: var(--font-numeric); display: block; margin-bottom: 5px;">
                                                <i class="fa-regular fa-envelope" style="margin-right: 4px; font-size: 0.85rem;"></i><?php echo htmlspecialchars($inq['email']); ?>
                                            </a>
                                            <?php if (!empty($inq['whatsapp'])): ?>
                                                <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $inq['whatsapp']); ?>" target="_blank" style="color: #25D366; font-weight: 500; font-family: var(--font-numeric); display: inline-flex; align-items: center; gap: 4px; text-decoration: none;">
                                                    <i class="fa-brands fa-whatsapp" style="font-size: 0.95rem;"></i> <?php echo htmlspecialchars($inq['whatsapp']); ?>
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span style="font-weight: 600; color: var(--color-primary);"><?php echo htmlspecialchars($inq['product_title']); ?></span>
                                        </td>
                                        <td style="font-size: 0.85rem; line-height: 1.4; color: #4A564E;">
                                            <?php echo htmlspecialchars($inq['message']); ?>
                                            <div style="font-size: 0.72rem; color: var(--color-gray); margin-top: 5px; font-family: var(--font-numeric);">
                                                Submitted: <?php echo date('M d, Y h:ia', strtotime($inq['created_at'])); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge <?php echo $inq['status'] === 'Pending' ? 'pending' : 'addressed'; ?>">
                                                <?php echo htmlspecialchars($inq['status']); ?>
                                            </span>
                                        </td>
                                        <td style="text-align: right;">
                                            <div class="table-actions" style="justify-content: flex-end; align-items: center;">
                                                <?php if (!empty($inq['whatsapp'])): ?>
                                                    <button onclick="openReplyModal(event, <?php echo $inq['id']; ?>, '<?php echo addslashes($inq['name']); ?>', '<?php echo addslashes($inq['whatsapp']); ?>', '<?php echo addslashes($inq['product_title']); ?>', '<?php echo addslashes(str_replace(array("\r", "\n"), ' ', $inq['message'])); ?>')"
                                                       class="btn-icon" 
                                                       style="color: var(--color-primary); border-color: rgba(10, 46, 36, 0.15); background: rgba(10, 46, 36, 0.05); padding: 0;"
                                                       title="Send WhatsApp Response Dialog">
                                                        <img src="../assets/images/logo.png" alt="OXO Logo" style="width: 16px; height: 16px; object-fit: contain; filter: brightness(0.2);">
                                                    </button>
                                                <?php endif; ?>
                                                
                                                <?php if ($inq['status'] === 'Pending'): ?>
                                                    <a href="index.php?tab=analytics&action=address&inquiry_id=<?php echo $inq['id']; ?>" 
                                                       class="btn-icon edit" 
                                                       style="color: var(--color-success); border-color: rgba(95, 173, 138, 0.2); background: rgba(95, 173, 138, 0.08);"
                                                       title="Mark as Addressed">
                                                        <i class="fa-solid fa-check"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <span style="color: var(--color-gray); font-size: 0.75rem; font-style: italic; align-self: center;">Resolved</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- TAB C: SETTINGS TAB (Password Reset) -->
        <div class="tab-container <?php echo $current_tab === 'settings' ? 'active' : ''; ?>">
            <div class="settings-container">
                <div class="settings-card">
                    <h3 class="editor-card-title"><i class="fa-solid fa-lock" style="margin-right: 8px;"></i> Change Admin Password</h3>
                    
                    <form action="index.php?tab=settings" method="POST">
                        <input type="hidden" name="form_action" value="reset_password">
                        
                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <input type="password" id="current_password" name="current_password" class="input-control" required placeholder="Type current password" autocomplete="current-password">
                        </div>
                        
                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input type="password" id="new_password" name="new_password" class="input-control" required placeholder="Choose a secure password (min. 6 chars)" autocomplete="new-password">
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="input-control" required placeholder="Retype new password" autocomplete="new-password">
                        </div>
                        
                        <button type="submit" class="action-btn" style="width: 100%; justify-content: center; margin-top: 10px;">
                            <i class="fa-solid fa-key"></i> Update Credentials
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <!-- TAB F: ADD PRODUCT TAB -->
        <div class="tab-container <?php echo $current_tab === 'add_product' ? 'active' : ''; ?>">
            <div class="page-header" style="margin-bottom: 25px; border-bottom: 1px solid var(--color-panel-border); padding-bottom: 15px; margin-top: 10px;">
                <h3 style="font-family: var(--font-title); font-size: 1.4rem; color: var(--color-primary); display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-circle-plus" style="color: var(--color-accent);"></i> Add New Creation
                </h3>
                <p style="font-size: 0.85rem; color: var(--color-gray); margin-top: 5px;">Register a new luxury furniture design to be displayed in the client-facing store catalog.</p>
            </div>

            <form action="index.php?tab=add_product" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="form_action" value="add_product">
                
                <div class="editor-grid" style="display: grid; grid-template-columns: 2fr 1fr; gap: 40px; align-items: start;">
                    
                    <!-- Left Column: Core Fields -->
                    <div class="editor-left" style="display: flex; flex-direction: column; gap: 30px;">
                        <div class="editor-card" style="padding: 30px;">
                            <h4 style="font-family: var(--font-title); font-size: 1.15rem; color: var(--color-primary); margin-bottom: 20px; border-bottom: 1px solid var(--color-panel-border); padding-bottom: 10px;">Core Specifications</h4>
                            
                            <div class="form-group" style="margin-bottom: 20px;">
                                <label for="prod_title">Title / Name</label>
                                <input type="text" id="prod_title" name="title" class="input-control" required placeholder="e.g. Nirvana Modular Sofa">
                            </div>
                            
                            <div class="form-row" style="display: flex; gap: 20px; margin-bottom: 20px;">
                                <div class="form-group" style="flex: 1;">
                                    <label for="prod_price">Price (INR - ₹)</label>
                                    <input type="number" id="prod_price" name="price" class="input-control" required placeholder="e.g. 185000" min="1">
                                </div>

                                <div class="form-group" style="flex: 1;">
                                    <label for="prod_brand">Brand Partner</label>
                                    <select id="prod_brand" name="brand_id" class="input-control" required>
                                        <option value="">-- Select Brand --</option>
                                        <?php foreach ($brands as $b): ?>
                                            <option value="<?php echo $b['id']; ?>"><?php echo htmlspecialchars($b['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="form-row" style="display: flex; gap: 20px; margin-bottom: 20px;">
                                <div class="form-group" style="flex: 1;">
                                    <label for="prod_category">Category</label>
                                    <select id="prod_category" name="category" class="input-control" required>
                                        <option value="">-- Select Category --</option>
                                        <?php foreach ($categories_list as $cat): ?>
                                            <option value="<?php echo htmlspecialchars($cat['slug']); ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group" style="flex: 1;">
                                    <label for="prod_material_slug">Material Type</label>
                                    <select id="prod_material_slug" name="material_slug" class="input-control" required>
                                        <option value="">-- Select Material --</option>
                                        <?php foreach ($materials_list as $mat): ?>
                                            <option value="<?php echo htmlspecialchars($mat['slug']); ?>"><?php echo htmlspecialchars($mat['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                             <!-- Product Color Selection (Multiple Colors) -->
                             <div class="form-group" style="margin-bottom: 20px;">
                                 <label style="font-weight: 700; display: block; margin-bottom: 10px;">Select Available Colors for this Product</label>
                                 <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 12px; background: var(--color-bg-panel); padding: 15px; border-radius: 8px; border: 1px solid var(--color-panel-border);">
                                     <?php foreach ($colors_list as $color): ?>
                                         <label class="color-checkbox-label" style="display: flex; align-items: center; gap: 8px; cursor: pointer; user-select: none;">
                                             <input type="checkbox" name="color_ids[]" value="<?php echo $color['id']; ?>" class="color-select-checkbox" data-id="<?php echo $color['id']; ?>" data-name="<?php echo htmlspecialchars($color['name']); ?>" data-hex="<?php echo htmlspecialchars($color['hex']); ?>" style="width: 16px; height: 16px; cursor: pointer;">
                                             <span style="display: inline-block; width: 14px; height: 14px; border-radius: 50%; background-color: <?php echo htmlspecialchars($color['hex']); ?>; border: 1px solid var(--color-panel-border); vertical-align: middle;"></span>
                                             <span style="font-size: 0.85rem; color: var(--color-primary); font-weight: 600;"><?php echo htmlspecialchars($color['name']); ?></span>
                                         </label>
                                     <?php endforeach; ?>
                                 </div>
                             </div>

                            <div class="form-group" style="margin-bottom: 20px;">
                                <label for="prod_description">Short Description</label>
                                <textarea id="prod_description" name="description" class="input-control" rows="3" required placeholder="A brief description of the design, highlights, and craftsmanship..."></textarea>
                            </div>
                            
                            <div class="form-row" style="display: flex; gap: 20px;">
                                <div class="form-group" style="flex: 1;">
                                    <label for="prod_height_cm">Height (cm)</label>
                                    <input type="number" id="prod_height_cm" name="height_cm" class="input-control" required placeholder="e.g. 85" min="1" value="85">
                                </div>
                                <div class="form-group" style="flex: 1;">
                                    <label for="prod_width_cm">Width (cm)</label>
                                    <input type="number" id="prod_width_cm" name="width_cm" class="input-control" required placeholder="e.g. 100" min="1" value="100">
                                </div>
                                <div class="form-group" style="flex: 1;">
                                    <label for="prod_length_cm">Length (cm)</label>
                                    <input type="number" id="prod_length_cm" name="length_cm" class="input-control" required placeholder="e.g. 240" min="1" value="240">
                                </div>
                            </div>
                        </div>

                        <div class="editor-card" style="padding: 30px;">
                            <h4 style="font-family: var(--font-title); font-size: 1.15rem; color: var(--color-primary); margin-bottom: 20px; border-bottom: 1px solid var(--color-panel-border); padding-bottom: 10px;">Custom Specifications & Details</h4>
                            
                            <div class="form-group" style="margin-bottom: 15px;">
                                <label for="detail_material">Upholstery & Material Details</label>
                                <input type="text" id="detail_material" name="detail_material" class="input-control" placeholder="e.g. Performance textured linen (80% polyester, 20% linen)">
                            </div>
                            
                            <div class="form-group" style="margin-bottom: 15px;">
                                <label for="detail_construction">Construction & Frame suspension</label>
                                <input type="text" id="detail_construction" name="detail_construction" class="input-control" placeholder="e.g. Double-doweled kiln-dried birch wood frame">
                            </div>
                            
                            <div class="form-group" style="margin-bottom: 15px;">
                                <label for="detail_care">Care & Cleaning Instructions</label>
                                <input type="text" id="detail_care" name="detail_care" class="input-control" placeholder="e.g. Professional upholstery cleaning recommended. Vacuum weekly.">
                            </div>
                            
                            <div class="form-group">
                                <label for="detail_shipping">Shipping, Delivery & Assembly</label>
                                <input type="text" id="detail_shipping" name="detail_shipping" class="input-control" placeholder="e.g. Delivered in 3 modular sections. Free white-glove inside assembly.">
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Image and Actions -->
                    <div class="editor-right" style="display: flex; flex-direction: column; gap: 30px;">
                        <div class="editor-card" style="padding: 30px;">
                            <h4 style="font-family: var(--font-title); font-size: 1.15rem; color: var(--color-primary); margin-bottom: 20px; border-bottom: 1px solid var(--color-panel-border); padding-bottom: 10px;">Visual Assets</h4>
                            
                            <div class="form-group" style="margin-bottom: 20px;">
                                <label>Upload Photo Files (Multiple selections supported)</label>
                                <div class="upload-container" onclick="document.getElementById('prod_gallery_files').click();" style="padding: 25px; border: 2px dashed var(--color-panel-border); border-radius: 8px; text-align: center; cursor: pointer;">
                                    <i class="fa-solid fa-cloud-arrow-up upload-icon" style="font-size: 1.8rem; color: var(--color-accent); margin-bottom: 10px;"></i>
                                    <div class="upload-text"><strong style="font-size: 0.85rem;">Click to Upload Photos</strong></div>
                                    <div style="font-size: 0.68rem; color: var(--color-gray); margin-top: 5px;">PNG, JPG, WEBP formats. Drag & drop files.</div>
                                </div>
                                <input type="file" id="prod_gallery_files" name="gallery_files[]" class="upload-file-input" accept="image/*" multiple style="display: none;">
                            </div>

                            <div class="form-group" style="margin-bottom: 20px;">
                                <label>Or Add Photo from Path / URL</label>
                                <div style="display: flex; gap: 10px;">
                                    <input type="text" id="add_image_url_field" class="input-control" placeholder="assets/images/sofa_x.png" style="flex: 1;">
                                    <button type="button" id="btn_add_url_to_gallery" class="action-btn" style="padding: 0 15px; border-radius: 6px;"><i class="fa-solid fa-plus"></i> Add</button>
                                </div>
                            </div>

                            <input type="hidden" name="image_order_json" id="image_order_json" value="[]">

                            <!-- Live Scale Graph Overlay Preview Box -->
                            <div id="admin-live-scale-preview" style="display: none; margin-bottom: 25px; border-radius: 8px; border: 1px solid var(--color-panel-border); overflow: hidden; position: relative; aspect-ratio: 1 / 1.15; background-color: var(--color-white); justify-content: center; align-items: center;">
                                <img id="admin-preview-cover-img" src="" style="width: 85%; height: 85%; object-fit: contain; display: block; margin: auto;">
                                <div id="admin-preview-svg-overlay" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: transparent; z-index: 5; pointer-events: none;">
                                    <!-- Dynamic SVG Scale Graph -->
                                </div>
                                <span style="position: absolute; bottom: 8px; left: 8px; font-size: 0.62rem; padding: 2px 6px; border-radius: 4px; font-weight: 700; background: var(--color-accent); color: white; z-index: 10;">Scale Overlay Preview</span>
                            </div>

                            <div style="border-top: 1px solid var(--color-panel-border); padding-top: 15px;">
                                <label style="font-size: 0.72rem; text-transform: uppercase; font-weight: 700; color: var(--color-gray); letter-spacing: 0.5px;">Arrange & Select Cover Image</label>
                                <div class="gallery-grid" id="gallery_preview_grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 12px; margin-top: 10px;">
                                    <!-- Dynamic Javascript Preview Cards -->
                                </div>
                            </div>
                        </div>

                        <div class="editor-card" style="padding: 30px; text-align: center;">
                            <button type="submit" class="action-btn" style="width: 100%; justify-content: center; padding: 14px;">
                                <i class="fa-solid fa-circle-check"></i> Add Design to Shop
                            </button>
                            <a href="index.php?tab=products" class="sidebar-link" style="display: block; margin-top: 15px; text-align: center; text-decoration: underline; font-size: 0.85rem; color: var(--color-gray);">
                                Cancel & Discard
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <script>
        // JS function to draw isometric SVG scale blueprint client-side
        function generateScaleGraphSvg(h, w, l) {
            const maxDim = Math.max(h, w, l);
            const scale = maxDim <= 0 ? 1 : 135 / maxDim;
            
            const sw_x = w * scale * 0.866;
            const sw_y = w * scale * 0.5;
            
            const sl_x = l * scale * 0.866;
            const sl_y = l * scale * 0.5;
            
            const sh_y = h * scale;
            
            const p_bf = [200, 210];
            const p_bl = [200 - sw_x, 210 + sw_y];
            const p_br = [200 + sl_x, 210 + sl_y];
            const p_bb = [200 - sw_x + sl_x, 210 + sw_y + sl_y];
            
            const p_tf = [200, 210 - sh_y];
            const p_tl = [200 - sw_x, 210 + sw_y - sh_y];
            const p_tr = [200 + sl_x, 210 + sl_y - sh_y];
            const p_tb = [200 - sw_x + sl_x, 210 + sw_y + sl_y - sh_y];
            
            const xs = [p_bf[0], p_bl[0], p_br[0], p_bb[0], p_tf[0], p_tl[0], p_tr[0], p_tb[0]];
            const ys = [p_bf[1], p_bl[1], p_br[1], p_bb[1], p_tf[1], p_tl[1], p_tr[1], p_tb[1]];
            
            const min_x = Math.min(...xs);
            const max_x = Math.max(...xs);
            const min_y = Math.min(...ys);
            const max_y = Math.max(...ys);
            
            const offset_x = 200 - (min_x + max_x) / 2;
            const offset_y = 215 - (min_y + max_y) / 2;
            
            const shift = (p) => [p[0] + offset_x, p[1] + offset_y];
            
            const bf = shift(p_bf);
            const bl = shift(p_bl);
            const br = shift(p_br);
            const bb = shift(p_bb);
            const tf = shift(p_tf);
            const tl = shift(p_tl);
            const tr = shift(p_tr);
            const tb = shift(p_tb);
            
            const offset = 35; 
            const w_dx = offset * 0.866; 
            const w_dy = offset * 0.5;
            
            const bl_off = [bl[0] - w_dx, bl[1] + w_dy];
            const bf_off = [bf[0] - w_dx, bf[1] + w_dy];
            
            const bf_off2 = [bf[0] + w_dx, bf[1] + w_dy];
            const br_off = [br[0] + w_dx, br[1] + w_dy];
            
            const bl_off3 = [bl[0] - 45, bl[1]];
            const tl_off = [tl[0] - 45, tl[1]];
            
            const w_cx = (bl_off[0] + bf_off[0]) / 2;
            const w_cy = (bl_off[1] + bf_off[1]) / 2;
            
            const l_cx = (bf_off2[0] + br_off[0]) / 2;
            const l_cy = (bf_off2[1] + br_off[1]) / 2;
            
            const h_cx = (bl_off3[0] + tl_off[0]) / 2;
            const h_cy = (bl_off3[1] + tl_off[1]) / 2;
            
            const w_text = `W: ${w} cm`;
            const l_text = `L: ${l} cm`;
            const h_text = `H: ${h} cm`;
            
            const w_w = w_text.length * 5.2 + 10;
            const l_w = l_text.length * 5.2 + 10;
            const h_w = h_text.length * 5.2 + 10;

            return `
            <svg viewBox="0 0 400 460" width="100%" height="100%" xmlns="http://www.w3.org/2000/svg" style="background: transparent;">
                <defs>
                    <marker id="arrow-js" viewBox="0 0 10 10" refX="5" refY="5" markerWidth="6" markerHeight="6" orient="auto-start-reverse">
                        <path d="M 0 1.5 L 10 5 L 0 8.5 z" fill="#bf8f54" />
                    </marker>
                </defs>

                <!-- Faint glassmorphic base footprint -->
                <polygon points="${bl[0]},${bl[1]} ${bf[0]},${bf[1]} ${br[0]},${br[1]} ${bb[0]},${bb[1]}" fill="#bf8f54" opacity="0.04" />
                
                <!-- Outer contours (Thin white-gold wireframe) -->
                <g stroke="#bf8f54" stroke-width="1.0" stroke-linejoin="round" fill="none" opacity="0.85">
                    <!-- Front visible edges -->
                    <line x1="${bl[0]}" y1="${bl[1]}" x2="${bf[0]}" y2="${bf[1]}" />
                    <line x1="${bf[0]}" y1="${bf[1]}" x2="${br[0]}" y2="${br[1]}" />
                    
                    <!-- Hidden base edges -->
                    <line x1="${br[0]}" y1="${br[1]}" x2="${bb[0]}" y2="${bb[1]}" stroke-dasharray="2,3" opacity="0.4" />
                    <line x1="${bb[0]}" y1="${bb[1]}" x2="${bl[0]}" y2="${bl[1]}" stroke-dasharray="2,3" opacity="0.4" />
                    
                    <!-- Top edges -->
                    <line x1="${tl[0]}" y1="${tl[1]}" x2="${tf[0]}" y2="${tf[1]}" />
                    <line x1="${tf[0]}" y1="${tf[1]}" x2="${tr[0]}" y2="${tr[1]}" />
                    <line x1="${tr[0]}" y1="${tr[1]}" x2="${tb[0]}" y2="${tb[1]}" stroke-dasharray="2,3" opacity="0.6" />
                    <line x1="${tb[0]}" y1="${tb[1]}" x2="${tl[0]}" y2="${tl[1]}" stroke-dasharray="2,3" opacity="0.6" />
                    
                    <!-- Vertical pillars -->
                    <line x1="${bl[0]}" y1="${bl[1]}" x2="${tl[0]}" y2="${tl[1]}" />
                    <line x1="${bf[0]}" y1="${bf[1]}" x2="${tf[0]}" y2="${tf[1]}" />
                    <line x1="${br[0]}" y1="${br[1]}" x2="${tr[0]}" y2="${tr[1]}" />
                    <line x1="${bb[0]}" y1="${bb[1]}" x2="${tb[0]}" y2="${tb[1]}" stroke-dasharray="2,3" opacity="0.4" />
                </g>
                
                <!-- Annotations lines with dynamic arrowheads -->
                <g stroke="#bf8f54" stroke-width="0.8" fill="none" opacity="0.75">
                    <!-- Width line offset -->
                    <line x1="${bl_off[0]}" y1="${bl_off[1]}" x2="${bf_off[0]}" y2="${bf_off[1]}" marker-start="url(#arrow-js)" marker-end="url(#arrow-js)" />
                    <line x1="${bl[0]}" y1="${bl[1]}" x2="${bl_off[0]}" y2="${bl_off[1]}" stroke-dasharray="1,2" />
                    <line x1="${bf[0]}" y1="${bf[1]}" x2="${bf_off[0]}" y2="${bf_off[1]}" stroke-dasharray="1,2" />
                    
                    <!-- Length/Depth line offset -->
                    <line x1="${bf_off2[0]}" y1="${bf_off2[1]}" x2="${br_off[0]}" y2="${br_off[1]}" marker-start="url(#arrow-js)" marker-end="url(#arrow-js)" />
                    <line x1="${bf[0]}" y1="${bf[1]}" x2="${bf_off2[0]}" y2="${bf_off2[1]}" stroke-dasharray="1,2" />
                    <line x1="${br[0]}" y1="${br[1]}" x2="${br_off[0]}" y2="${br_off[1]}" stroke-dasharray="1,2" />
                    
                    <!-- Height line offset -->
                    <line x1="${bl_off3[0]}" y1="${bl_off3[1]}" x2="${tl_off[0]}" y2="${tl_off[1]}" marker-start="url(#arrow-js)" marker-end="url(#arrow-js)" />
                    <line x1="${bl[0]}" y1="${bl[1]}" x2="${bl_off3[0]}" y2="${bl_off3[1]}" stroke-dasharray="1,2" />
                    <line x1="${tl[0]}" y1="${tl[1]}" x2="${tl_off[0]}" y2="${tl_off[1]}" stroke-dasharray="1,2" />
                </g>
                
                <!-- Text labels with background pills -->
                <g font-family="sans-serif" font-size="7.5" font-weight="700" text-anchor="middle">
                    <!-- Width -->
                    <rect x="${w_cx - w_w/2}" y="${w_cy - 7}" width="${w_w}" height="14" rx="3" fill="#111111" stroke="#bf8f54" stroke-width="0.8" />
                    <text x="${w_cx}" y="${w_cy + 3}" fill="#faf9f6">${w_text}</text>

                    <!-- Length -->
                    <rect x="${l_cx - l_w/2}" y="${l_cy - 7}" width="${l_w}" height="14" rx="3" fill="#111111" stroke="#bf8f54" stroke-width="0.8" />
                    <text x="${l_cx}" y="${l_cy + 3}" fill="#faf9f6">${l_text}</text>

                    <!-- Height -->
                    <rect x="${h_cx - h_w/2}" y="${h_cy - 7}" width="${h_w}" height="14" rx="3" fill="#111111" stroke="#bf8f54" stroke-width="0.8" />
                    <text x="${h_cx}" y="${h_cy + 3}" fill="#faf9f6">${h_text}</text>
                </g>
                
                <!-- Visual Node markers -->
                <circle cx="${bf[0]}" cy="${bf[1]}" r="2" fill="#faf9f6" stroke="#bf8f54" stroke-width="0.8" />
                <circle cx="${bl[0]}" cy="${bl[1]}" r="2" fill="#faf9f6" stroke="#bf8f54" stroke-width="0.8" />
                <circle cx="${br[0]}" cy="${br[1]}" r="2" fill="#faf9f6" stroke="#bf8f54" stroke-width="0.8" />
                <circle cx="${tf[0]}" cy="${tf[1]}" r="2" fill="#faf9f6" stroke="#bf8f54" stroke-width="0.8" />
                <circle cx="${tl[0]}" cy="${tl[1]}" r="2" fill="#faf9f6" stroke="#bf8f54" stroke-width="0.8" />
                <circle cx="${tr[0]}" cy="${tr[1]}" r="2" fill="#faf9f6" stroke="#bf8f54" stroke-width="0.8" />
            </svg>`;
        }

        document.addEventListener('DOMContentLoaded', () => {
            const colors_list = <?php echo json_encode($colors_list); ?>;
            const initialImages = [];
            let imageList = [...initialImages];

            const fileInput = document.getElementById('prod_gallery_files');
            const addUrlField = document.getElementById('add_image_url_field');
            const addUrlBtn = document.getElementById('btn_add_url_to_gallery');
            const previewGrid = document.getElementById('gallery_preview_grid');
            const orderJsonInput = document.getElementById('image_order_json');

            const hInput = document.getElementById('prod_height_cm');
            const wInput = document.getElementById('prod_width_cm');
            const lInput = document.getElementById('prod_length_cm');

            function updateGrid() {
                previewGrid.innerHTML = '';
                
                // 1. Render all image assets (main and sub-images)
                imageList.forEach((item, index) => {
                    const isCover = index === 0;
                    
                    const card = document.createElement('div');
                    card.className = 'gallery-item-card';
                    card.style.cssText = 'position: relative; background: var(--color-bg-panel); border: 1px solid var(--color-panel-border); border-radius: 8px; overflow: hidden; display: flex; flex-direction: column; align-items: center; padding: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.03);';
                    
                    const img = document.createElement('img');
                    img.className = 'gallery-item-thumb';
                    img.src = item.src;
                    img.style.cssText = 'width: 100%; height: 75px; object-fit: cover; border-radius: 6px;';
                    card.appendChild(img);
                    
                    const badge = document.createElement('span');
                    badge.className = isCover ? 'gallery-item-badge cover' : 'gallery-item-badge secondary';
                    badge.textContent = isCover ? 'Cover Image' : 'Make Cover';
                    badge.style.cssText = isCover 
                        ? 'font-size: 0.62rem; padding: 2px 6px; border-radius: 4px; margin-top: 6px; font-weight: 700; background: var(--color-accent); color: white;'
                        : 'font-size: 0.62rem; padding: 2px 6px; border-radius: 4px; margin-top: 6px; font-weight: 700; background: var(--color-panel-border); color: var(--color-gray); cursor: pointer;';
                    
                    if (!isCover) {
                        badge.addEventListener('click', () => {
                            imageList.splice(index, 1);
                            imageList.unshift(item);
                            updateGrid();
                        });
                    }
                    card.appendChild(badge);
                    
                    const controls = document.createElement('div');
                    controls.className = 'gallery-item-controls';
                    controls.style.cssText = 'display: flex; gap: 4px; margin-top: 6px; width: 100%; justify-content: center;';
                    
                    if (index > 0) {
                        const btnLeft = document.createElement('button');
                        btnLeft.type = 'button';
                        btnLeft.innerHTML = '<i class="fa-solid fa-arrow-left"></i>';
                        btnLeft.style.cssText = 'border: none; background: none; cursor: pointer; color: var(--color-gray); font-size: 0.75rem; padding: 2px 6px;';
                        btnLeft.addEventListener('click', () => {
                            const temp = imageList[index];
                            imageList[index] = imageList[index - 1];
                            imageList[index - 1] = temp;
                            updateGrid();
                        });
                        controls.appendChild(btnLeft);
                    }
                    
                    const btnDel = document.createElement('button');
                    btnDel.type = 'button';
                    btnDel.innerHTML = '<i class="fa-solid fa-trash-can"></i>';
                    btnDel.style.cssText = 'border: none; background: none; cursor: pointer; color: #cc5a5a; font-size: 0.75rem; padding: 2px 6px; margin: 0 4px;';
                    btnDel.addEventListener('click', () => {
                        imageList.splice(index, 1);
                        updateGrid();
                    });
                    controls.appendChild(btnDel);
                    
                    if (index < imageList.length - 1) {
                        const btnRight = document.createElement('button');
                        btnRight.type = 'button';
                        btnRight.innerHTML = '<i class="fa-solid fa-arrow-right"></i>';
                        btnRight.style.cssText = 'border: none; background: none; cursor: pointer; color: var(--color-gray); font-size: 0.75rem; padding: 2px 6px;';
                        btnRight.addEventListener('click', () => {
                            const temp = imageList[index];
                            imageList[index] = imageList[index + 1];
                            imageList[index + 1] = temp;
                            updateGrid();
                        });
                        controls.appendChild(btnRight);
                    }
                    // Color selector for each gallery image
                    const colorSelect = document.createElement('select');
                    colorSelect.className = 'input-control';
                    colorSelect.style.cssText = 'font-size: 0.65rem; padding: 2px 4px; height: auto; margin-top: 6px; width: 100%; border-radius: 4px; border: 1px solid var(--color-panel-border); background: var(--color-bg-panel); color: var(--color-primary);';
                    
                    const optDefault = document.createElement('option');
                    optDefault.value = '';
                    optDefault.textContent = 'All / No Color';
                    colorSelect.appendChild(optDefault);
                    
                    const checkedCheckboxes = Array.from(document.querySelectorAll('.color-select-checkbox:checked'));
                    checkedCheckboxes.forEach(cb => {
                        const cid = cb.getAttribute('data-id');
                        const cname = cb.getAttribute('data-name');
                        const opt = document.createElement('option');
                        opt.value = cid;
                        opt.textContent = cname;
                        if (item.color_id && parseInt(item.color_id) === parseInt(cid)) {
                            opt.selected = true;
                        }
                        colorSelect.appendChild(opt);
                    });
                    
                    colorSelect.addEventListener('change', function() {
                        item.color_id = this.value || null;
                        let fileIdx = 0;
                        const orderData = imageList.map(it => {
                            if (it.file) {
                                return { id: `file:${fileIdx++}`, color_id: it.color_id || null };
                            } else {
                                return { id: it.id, color_id: it.color_id || null };
                            }
                        });
                        orderJsonInput.value = JSON.stringify(orderData);
                    });
                    
                    card.appendChild(colorSelect);
                    card.appendChild(controls);
                    previewGrid.appendChild(card);
                });

                // 2. Render dynamic dimension scale blueprint card at the end
                const scaleCard = document.createElement('div');
                scaleCard.className = 'gallery-item-card scale-preview-card';
                scaleCard.style.cssText = 'position: relative; background: var(--color-bg-panel); border: 1px solid var(--color-panel-border); border-radius: 8px; overflow: hidden; display: flex; flex-direction: column; align-items: center; padding: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.03);';
                
                const h = parseInt(hInput ? hInput.value : 85) || 85;
                const w = parseInt(wInput ? wInput.value : 100) || 100;
                const l = parseInt(lInput ? lInput.value : 240) || 240;
                
                const svgBox = document.createElement('div');
                svgBox.style.cssText = 'width: 100%; height: 75px; display: flex; align-items: center; justify-content: center; overflow: hidden; border-radius: 6px; border: 1px solid var(--color-panel-border);';
                svgBox.innerHTML = generateScaleGraphSvg(h, w, l);
                scaleCard.appendChild(svgBox);
                
                const badge = document.createElement('span');
                badge.style.cssText = 'font-size: 0.62rem; padding: 2px 6px; border-radius: 4px; margin-top: 6px; font-weight: 700; background: var(--color-bg-panel); color: var(--color-accent); border: 1px solid var(--color-accent);';
                badge.textContent = 'Auto Scale';
                scaleCard.appendChild(badge);
                
                const label = document.createElement('div');
                label.style.cssText = 'font-size: 0.7rem; color: var(--color-gray); margin-top: 6px; font-weight: 600; text-transform: uppercase;';
                label.textContent = 'Blueprint';
                scaleCard.appendChild(label);
                
                previewGrid.appendChild(scaleCard);
                
                // 3. Render Large Main Image Preview with Scale Overlay in sidebar
                const coverItem = imageList[0];
                const previewContainer = document.getElementById('admin-live-scale-preview');
                const previewImg = document.getElementById('admin-preview-cover-img');
                const previewOverlay = document.getElementById('admin-preview-svg-overlay');
                
                if (coverItem) {
                    if (previewContainer) previewContainer.style.display = 'flex';
                    if (previewImg) previewImg.src = coverItem.src;
                    if (previewOverlay) {
                        previewOverlay.innerHTML = generateScaleGraphSvg(h, w, l);
                    }
                } else {
                    if (previewContainer) previewContainer.style.display = 'none';
                }
                
                // Save image list to hidden input field as JSON
                let fileIdx = 0;
                const orderData = imageList.map(item => {
                    if (item.file) {
                        return { id: `file:${fileIdx++}`, color_id: item.color_id || null };
                    } else {
                        return { id: item.id, color_id: item.color_id || null };
                    }
                });
                orderJsonInput.value = JSON.stringify(orderData);
            }

            // Bind inputs to dynamic uploader preview updates
            [hInput, wInput, lInput].forEach(inp => {
                if (inp) {
                    inp.addEventListener('input', updateGrid);
                }
            });

            // Add File Event Listener
            if (fileInput) {
                fileInput.addEventListener('change', function() {
                    const files = Array.from(this.files);
                    let pending = files.length;
                    if (pending === 0) return;
                    
                    files.forEach((file) => {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            imageList.push({
                                id: `file_temp_${Math.random()}`,
                                src: e.target.result,
                                file: file
                            });
                            pending--;
                            if (pending === 0) {
                                updateGrid();
                            }
                        };
                        reader.readAsDataURL(file);
                    });
                    this.value = ''; // clear input to allow selection of more files
                });
            }

            // Add URL Button Event Listener
            if (addUrlBtn && addUrlField) {
                addUrlBtn.addEventListener('click', () => {
                    const url = addUrlField.value.trim();
                    if (url) {
                        imageList.push({
                            id: url,
                            src: url.startsWith('http') || url.startsWith('/') ? url : '../' + url
                        });
                        addUrlField.value = '';
                        updateGrid();
                    }
                });
            }

            // Bind form submit to populate fileInput before submit with all accumulated files
            const form = fileInput ? fileInput.closest('form') : null;
            if (form && fileInput) {
                form.addEventListener('submit', (e) => {
                    const dt = new DataTransfer();
                    imageList.forEach(item => {
                        if (item.file) {
                            dt.items.add(item.file);
                        }
                    });
                    fileInput.files = dt.files;
                });
            }

            // Bind colors checkboxes to update grid and sync color mappings
            document.querySelectorAll('.color-select-checkbox').forEach(cb => {
                cb.addEventListener('change', () => {
                    const checkedIds = Array.from(document.querySelectorAll('.color-select-checkbox:checked')).map(c => parseInt(c.value));
                    imageList.forEach(item => {
                        if (item.color_id && !checkedIds.includes(parseInt(item.color_id))) {
                            item.color_id = null;
                        }
                    });
                    updateGrid();
                });
            });

            // Initial draw
            updateGrid();
        });
        </script>

        <!-- TAB D: COLLECTIONS & BRANDS TAB (Categories, Materials, and Brand Logos combined) -->
        <div class="tab-container <?php echo $current_tab === 'collections' ? 'active' : ''; ?>">
            
            <!-- Section 1: Partner Brands (First) -->
            <div class="page-header" style="margin-bottom: 20px; border-bottom: 1px solid var(--color-panel-border); padding-bottom: 10px; margin-top: 10px;">
                <h3 style="font-family: var(--font-title); font-size: 1.3rem; color: var(--color-primary); display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-certificate" style="color: var(--color-accent);"></i> Partner Brands & Logos
                </h3>
            </div>
            
            <div class="brands-grid" style="margin-bottom: 50px;">
                <!-- Left: List of brands -->
                <div class="brands-list-panel">
                    <div class="table-card">
                        <div class="table-responsive">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th style="width: 150px;">Visual Logo</th>
                                        <th>Brand Name</th>
                                        <th style="width: 100px; text-align: right;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($brands)): ?>
                                        <tr>
                                            <td colspan="3" style="text-align: center; padding: 40px; color: var(--color-gray);">No brands registered.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($brands as $b): ?>
                                            <tr>
                                                <td>
                                                    <?php if (!empty($b['logo_path'])): ?>
                                                        <img src="../<?php echo htmlspecialchars($b['logo_path']); ?>" alt="<?php echo htmlspecialchars($b['name']); ?>" class="brand-logo-preview" onerror="this.style.display='none';">
                                                    <?php else: ?>
                                                        <span class="brand-text-fallback"><?php echo htmlspecialchars($b['name']); ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td style="font-weight: 700; color: var(--color-primary);"><?php echo htmlspecialchars($b['name']); ?></td>
                                                <td style="text-align: right;">
                                                    <a href="index.php?tab=collections&action=delete&id=<?php echo urlencode($b['id']); ?>" 
                                                       class="btn-icon delete" 
                                                       title="Delete Brand"
                                                       onclick="return confirm('Are you sure you want to delete this brand: <?php echo htmlspecialchars($b['name']); ?>? This action cannot be undone.');">
                                                        <i class="fa-solid fa-trash-can"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Right: Add Brand form -->
                <div class="brands-form-panel">
                    <div class="editor-card" style="padding: 30px;">
                        <h4 style="font-family: var(--font-title); font-size: 1.1rem; color: var(--color-primary); margin-bottom: 20px;">Add New Brand</h4>
                        <form action="index.php?tab=collections" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="form_action" value="add_brand">
                            
                            <div class="form-group" style="margin-bottom: 15px;">
                                <label for="brand_name">Brand Name</label>
                                <input type="text" id="brand_name" name="brand_name" class="input-control" required placeholder="e.g. Aethera Studio">
                            </div>
                            
                            <div class="form-group" style="margin-bottom: 15px;">
                                <label>Brand Logo Image (PNG / JPG / WEBP)</label>
                                <div class="upload-container" onclick="document.getElementById('logo_file').click();">
                                    <i class="fa-solid fa-cloud-arrow-up upload-icon"></i>
                                    <div class="upload-text"><strong>Click to Upload Logo File</strong></div>
                                    <div style="font-size: 0.7rem; color: var(--color-gray); margin-top: 5px;">Supports transparent PNG, JPG</div>
                                </div>
                                <input type="file" id="logo_file" name="logo_file" class="upload-file-input" accept="image/*">
                            </div>
                            
                            <div class="form-group" style="margin-bottom: 15px;">
                                <label for="logo_url">Or Logo Image URL Path</label>
                                <input type="text" id="logo_url" name="logo_url" class="input-control" placeholder="assets/images/logo_client.png">
                                <span style="font-size: 0.7rem; color: var(--color-gray); margin-top: 5px; display: inline-block;">If no image/URL is specified, the brand name will be displayed as a fallback.</span>
                            </div>
                            
                            <!-- Image Preview Box -->
                            <div class="preview-box" id="logo-preview-wrapper" style="display: none;">
                                <span style="font-size: 0.75rem; text-transform: uppercase; color: var(--color-accent); display: block; margin-bottom: 5px;">Logo Preview</span>
                                <img src="" alt="Preview" class="preview-img" id="logo-preview-img" style="max-height: 80px; object-fit: contain;">
                            </div>
                            
                            <button type="submit" class="action-btn" style="width: 100%; justify-content: center; margin-top: 20px;">
                                <i class="fa-solid fa-circle-check"></i> Register Brand
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Section 2: Furniture Categories (Second) -->
            <div class="page-header" style="margin-bottom: 20px; border-bottom: 1px solid var(--color-panel-border); padding-bottom: 10px;">
                <h3 style="font-family: var(--font-title); font-size: 1.3rem; color: var(--color-primary); display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-layer-group" style="color: var(--color-accent);"></i> Furniture Categories
                </h3>
            </div>
            
            <div class="brands-grid" style="margin-bottom: 50px;">
                <!-- Left: List of categories -->
                <div class="brands-list-panel">
                    <div class="table-card">
                        <div class="table-responsive">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>Category Name</th>
                                        <th>Slug</th>
                                        <th style="width: 80px; text-align: right;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($categories_list)): ?>
                                        <tr>
                                            <td colspan="3" style="text-align: center; padding: 20px; color: var(--color-gray);">No categories registered.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($categories_list as $cat): ?>
                                            <tr>
                                                <td style="font-weight: 700; color: var(--color-primary);"><?php echo htmlspecialchars($cat['name']); ?></td>
                                                <td><code style="color: var(--color-accent); font-family: var(--font-numeric); font-size: 0.85rem; background: var(--color-gray-dark); padding: 4px 8px; border-radius: 4px;"><?php echo htmlspecialchars($cat['slug']); ?></code></td>
                                                <td style="text-align: right;">
                                                    <a href="index.php?tab=collections&action=delete_category&id=<?php echo $cat['id']; ?>" 
                                                       class="btn-icon delete" 
                                                       title="Delete Category"
                                                       onclick="return confirm('Are you sure you want to delete this category: <?php echo htmlspecialchars($cat['name']); ?>? Any associated products will need to be reclassified.');">
                                                        <i class="fa-solid fa-trash-can"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Right: Add Category form -->
                <div class="brands-form-panel">
                    <div class="editor-card" style="padding: 30px;">
                        <h4 style="font-family: var(--font-title); font-size: 1.1rem; color: var(--color-primary); margin-bottom: 20px;">Add New Category</h4>
                        <form action="index.php?tab=collections" method="POST">
                            <input type="hidden" name="form_action" value="add_category">
                            
                            <div class="form-group" style="margin-bottom: 15px;">
                                <label for="cat_name">Category Name</label>
                                <input type="text" id="cat_name" name="cat_name" class="input-control" required placeholder="e.g. Armchairs" oninput="document.getElementById('cat_slug').value = this.value.toLowerCase().replace(/[^a-z0-9]/g, '-').replace(/-+/g, '-');">
                            </div>
                            
                            <div class="form-group" style="margin-bottom: 15px;">
                                <label for="cat_slug">Category Slug</label>
                                <input type="text" id="cat_slug" name="cat_slug" class="input-control" required placeholder="e.g. armchairs">
                            </div>
                            
                            <button type="submit" class="action-btn" style="width: 100%; justify-content: center;">
                                <i class="fa-solid fa-circle-plus"></i> Register Category
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Section 3: Material Types (Last) -->
            <div class="page-header" style="margin-bottom: 20px; border-bottom: 1px solid var(--color-panel-border); padding-bottom: 10px;">
                <h3 style="font-family: var(--font-title); font-size: 1.3rem; color: var(--color-primary); display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-cubes" style="color: var(--color-accent);"></i> Material Types
                </h3>
            </div>
            
            <div class="brands-grid" style="margin-bottom: 20px;">
                <!-- Left: List of materials -->
                <div class="brands-list-panel">
                    <div class="table-card">
                        <div class="table-responsive">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>Material Name</th>
                                        <th>Slug</th>
                                        <th style="width: 80px; text-align: right;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($materials_list)): ?>
                                        <tr>
                                            <td colspan="3" style="text-align: center; padding: 20px; color: var(--color-gray);">No materials registered.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($materials_list as $mat): ?>
                                            <tr>
                                                <td style="font-weight: 700; color: var(--color-primary);"><?php echo htmlspecialchars($mat['name']); ?></td>
                                                <td><code style="color: var(--color-accent); font-family: var(--font-numeric); font-size: 0.85rem; background: var(--color-gray-dark); padding: 4px 8px; border-radius: 4px;"><?php echo htmlspecialchars($mat['slug']); ?></code></td>
                                                <td style="text-align: right;">
                                                    <a href="index.php?tab=collections&action=delete_material&id=<?php echo $mat['id']; ?>" 
                                                       class="btn-icon delete" 
                                                       title="Delete Material"
                                                       onclick="return confirm('Are you sure you want to delete this material: <?php echo htmlspecialchars($mat['name']); ?>? Any associated products will default back to wood.');">
                                                        <i class="fa-solid fa-trash-can"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Right: Add Material form -->
                <div class="brands-form-panel">
                    <div class="editor-card" style="padding: 30px;">
                        <h4 style="font-family: var(--font-title); font-size: 1.1rem; color: var(--color-primary); margin-bottom: 20px;">Add Material Type</h4>
                        <form action="index.php?tab=collections" method="POST">
                            <input type="hidden" name="form_action" value="add_material">
                            
                            <div class="form-group" style="margin-bottom: 15px;">
                                <label for="mat_name">Material Name</label>
                                <input type="text" id="mat_name" name="mat_name" class="input-control" required placeholder="e.g. Teak Wood" oninput="document.getElementById('mat_slug').value = this.value.toLowerCase().replace(/[^a-z0-9]/g, '-').replace(/-+/g, '-');">
                            </div>
                            
                            <div class="form-group" style="margin-bottom: 15px;">
                                <label for="mat_slug">Material Slug</label>
                                <input type="text" id="mat_slug" name="mat_slug" class="input-control" required placeholder="e.g. teak-wood">
                            </div>
                            
                            <button type="submit" class="action-btn" style="width: 100%; justify-content: center;">
                                <i class="fa-solid fa-circle-plus"></i> Register Material
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Section 4: Color Palette (New) -->
            <div class="page-header" style="margin-bottom: 20px; border-bottom: 1px solid var(--color-panel-border); padding-bottom: 10px; margin-top: 35px;">
                <h3 style="font-family: var(--font-title); font-size: 1.3rem; color: var(--color-primary); display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-palette" style="color: var(--color-accent);"></i> Colors & Palette
                </h3>
            </div>
            
            <div class="brands-grid" style="margin-bottom: 50px;">
                <!-- Left: List of colors -->
                <div class="brands-list-panel">
                    <div class="table-card">
                        <div class="table-responsive">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th style="width: 100px;">Color Swatch</th>
                                        <th>Color Name</th>
                                        <th>HEX Value</th>
                                        <th style="width: 80px; text-align: right;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($colors_list)): ?>
                                        <tr>
                                            <td colspan="4" style="text-align: center; padding: 20px; color: var(--color-gray);">No colors registered.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($colors_list as $color): ?>
                                            <tr>
                                                <td>
                                                    <span style="display: inline-block; width: 22px; height: 22px; border-radius: 50%; background-color: <?php echo htmlspecialchars($color['hex']); ?>; border: 1px solid var(--color-panel-border); vertical-align: middle; box-shadow: 0 1px 3px rgba(0,0,0,0.1);"></span>
                                                </td>
                                                <td style="font-weight: 700; color: var(--color-primary);"><?php echo htmlspecialchars($color['name']); ?></td>
                                                <td><code style="color: var(--color-accent); font-family: var(--font-numeric); font-size: 0.85rem; background: var(--color-gray-dark); padding: 4px 8px; border-radius: 4px;"><?php echo htmlspecialchars($color['hex']); ?></code></td>
                                                <td style="text-align: right;">
                                                    <a href="index.php?tab=collections&action=delete_color&id=<?php echo $color['id']; ?>" 
                                                       class="btn-icon delete" 
                                                       title="Delete Color"
                                                       onclick="return confirm('Are you sure you want to delete this color: <?php echo htmlspecialchars($color['name']); ?>? Any associated products will default back to no color.');">
                                                        <i class="fa-solid fa-trash-can"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Right: Add Color form -->
                <div class="brands-form-panel">
                    <div class="editor-card" style="padding: 30px;">
                        <h4 style="font-family: var(--font-title); font-size: 1.1rem; color: var(--color-primary); margin-bottom: 20px;">Add New Color</h4>
                        <form action="index.php?tab=collections" method="POST">
                            <input type="hidden" name="form_action" value="add_color">
                            
                            <div class="form-group" style="margin-bottom: 15px;">
                                <label for="color_name">Color Name</label>
                                <input type="text" id="color_name" name="color_name" class="input-control" required placeholder="e.g. Royal Gold">
                            </div>
                            
                            <div class="form-group" style="margin-bottom: 15px;">
                                <label for="color_hex">Color HEX Code & Picker</label>
                                <div style="display: flex; gap: 10px; align-items: center;">
                                    <input type="text" id="color_hex" name="color_hex" class="input-control" required placeholder="#ffffff" value="#ffffff" pattern="^#([A-Fa-f0-9]{6})$" title="Must be a valid hex color code starting with #, followed by 6 hex characters.">
                                    <input type="color" id="color_picker" class="input-control" style="width: 45px; height: 42px; padding: 2px; border-radius: 6px; cursor: pointer; border: 1px solid var(--color-panel-border);" value="#ffffff">
                                </div>
                            </div>
                            
                            <button type="submit" class="action-btn" style="width: 100%; justify-content: center; margin-top: 20px;">
                                <i class="fa-solid fa-circle-plus"></i> Register Color
                            </button>
                        </form>
                    </div>
                </div>
            </div>

        </div>

        <script>
        document.addEventListener('DOMContentLoaded', () => {
            const logoInput = document.getElementById('logo_file');
            const logoPreviewWrapper = document.getElementById('logo-preview-wrapper');
            const logoPreviewImg = document.getElementById('logo-preview-img');
            const logoUrlInput = document.getElementById('logo_url');

            if (logoInput && logoPreviewWrapper && logoPreviewImg && logoUrlInput) {
                logoInput.addEventListener('change', function() {
                    const file = this.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            logoPreviewImg.src = e.target.result;
                            logoPreviewWrapper.style.display = 'block';
                            logoUrlInput.value = 'assets/images/uploads/' + file.name + ' (Uploaded)';
                        }
                        reader.readAsDataURL(file);
                    }
                });

                logoUrlInput.addEventListener('input', function() {
                    const val = this.value.trim();
                    if (val && !val.includes('(Uploaded)')) {
                        logoPreviewImg.src = val.startsWith('http') || val.startsWith('/') ? val : '../' + val;
                        logoPreviewWrapper.style.display = 'block';
                    }
                });
            }

            // Sync Color HEX input and Color Picker
            const colorHexInput = document.getElementById('color_hex');
            const colorPickerInput = document.getElementById('color_picker');
            if (colorHexInput && colorPickerInput) {
                colorHexInput.addEventListener('input', function() {
                    const val = this.value;
                    if (/^#[0-9A-Fa-f]{6}$/.test(val)) {
                        colorPickerInput.value = val;
                    }
                });
                colorPickerInput.addEventListener('input', function() {
                    colorHexInput.value = this.value;
                });
            }
        });
        </script>

        </main>
    </div>

    <!-- Chart rendering Script -->
    <?php if ($current_tab === 'analytics'): ?>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // 1. Line Chart: Gross Monthly Revenue
            const ctxRev = document.getElementById('revenueChart').getContext('2d');
            const revenueChart = new Chart(ctxRev, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                    datasets: [{
                        label: 'Sales Revenue (INR)',
                        data: [180000, 240000, 190000, 310000, 260000, 305000],
                        borderColor: '#527A63',
                        backgroundColor: 'rgba(82, 122, 99, 0.08)',
                        borderWidth: 3,
                        pointBackgroundColor: '#527A63',
                        pointBorderColor: '#FFFFFF',
                        pointHoverRadius: 6,
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            grid: { color: 'rgba(82, 122, 99, 0.05)' },
                            ticks: {
                                color: '#5A6B60',
                                callback: function(value) { return '₹' + value/1000 + 'k'; }
                            }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { color: '#5A6B60' }
                        }
                    }
                }
            });

            // Calculate category counts dynamically from PHP array values
            <?php
                $cat_counts = [];
                foreach ($products as $p) {
                    $cat = ucfirst($p['category']);
                    $cat_counts[$cat] = isset($cat_counts[$cat]) ? $cat_counts[$cat] + 1 : 1;
                }
                $js_labels = array_keys($cat_counts);
                $js_data = array_values($cat_counts);
            ?>

            // 2. Doughnut Chart: Catalog Collection Distribution
            const ctxCat = document.getElementById('categoryChart').getContext('2d');
            const categoryChart = new Chart(ctxCat, {
                type: 'doughnut',
                data: {
                    labels: <?php echo json_encode($js_labels); ?>,
                    datasets: [{
                        data: <?php echo json_encode($js_data); ?>,
                        backgroundColor: [
                            '#3B5B49',
                            '#527A63',
                            '#8FAF9B',
                            '#B2CBB5',
                            '#D1E0D5'
                        ],
                        borderWidth: 2,
                        borderColor: '#FFFFFF'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                color: '#5A6B60',
                                padding: 15,
                                font: { size: 11 }
                            }
                        }
                    },
                    cutout: '65%'
                }
            });
        });
    </script>
    <!-- Concierge Reply Modal Overlay -->
    <div id="concierge-reply-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 99999; justify-content: center; align-items: center; font-family: 'Inter', sans-serif;">
        <div style="background: #ffffff; width: 90%; max-width: 500px; padding: 30px; border-radius: 16px; border: 1px solid rgba(10, 46, 36, 0.08); box-shadow: 0 20px 50px rgba(0,0,0,0.15); display: flex; flex-direction: column; gap: 20px; position: relative;">
            <!-- Close Button -->
            <button onclick="closeReplyModal()" style="position: absolute; top: 20px; right: 20px; background: transparent; border: none; font-size: 1.2rem; cursor: pointer; color: #888;">
                <i class="fa-solid fa-xmark"></i>
            </button>
            
            <div style="display: flex; align-items: center; gap: 10px; border-bottom: 1px solid rgba(0,0,0,0.05); padding-bottom: 15px;">
                <img src="../assets/images/logo.png" alt="OXO Logo" style="width: 28px; height: 28px; object-fit: contain; filter: brightness(0.2);">
                <div>
                    <h3 style="margin: 0; font-size: 1.15rem; font-weight: 700; color: #0A2E24; font-family: 'Outfit', sans-serif; text-transform: uppercase; letter-spacing: 0.05em;">Concierge Response</h3>
                    <p style="margin: 3px 0 0 0; font-size: 0.75rem; color: #888;">Send official response via WhatsApp</p>
                </div>
            </div>
            
            <!-- Client Context -->
            <div style="background: rgba(10,46,36,0.03); border: 1px solid rgba(10,46,36,0.05); border-radius: 8px; padding: 12px; font-size: 0.82rem; line-height: 1.5; color: #4A564E;">
                <div><strong>Client:</strong> <span id="modal-client-name">John Doe</span> (<span id="modal-client-phone">+91 9999999999</span>)</div>
                <div style="margin-top: 4px;"><strong>Regarding:</strong> <span id="modal-client-subject">General Contact</span></div>
            </div>
            
            <!-- Textarea -->
            <div>
                <label style="display: block; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #bf8f54; margin-bottom: 8px;">Response Message</label>
                <textarea id="modal-reply-text" rows="6" style="width: 100%; border: 1px solid rgba(10, 46, 36, 0.15); padding: 12px; font-size: 0.85rem; border-radius: 8px; font-family: inherit; line-height: 1.5; resize: vertical; box-sizing: border-box;"></textarea>
            </div>
            
            <p style="margin: 0; font-size: 0.78rem; color: #888; line-height: 1.4;">
                💡 <strong>How to add Logo:</strong> Click "Copy Logo Image" first, then click "Send via WhatsApp". Once the chat opens, paste (<strong>Ctrl+V</strong>) the image directly into the chat input.
            </p>
            
            <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 5px;">
                <button onclick="copyLogoToClipboard(this)" style="background: #ffffff; border: 1px solid rgba(10, 46, 36, 0.15); color: #0a2e24; padding: 12px 18px; font-size: 0.78rem; font-weight: 700; border-radius: 8px; cursor: pointer; text-transform: uppercase; letter-spacing: 0.05em; display: inline-flex; align-items: center; gap: 6px; transition: all 0.3s;">
                    <span id="copy-btn-label">📋 Copy Logo Image</span>
                </button>
                <button onclick="submitWhatsAppReply()" style="background: #25D366; border: 1px solid #20BA5A; color: #ffffff; padding: 12px 20px; font-size: 0.78rem; font-weight: 700; border-radius: 8px; cursor: pointer; text-transform: uppercase; letter-spacing: 0.05em; display: inline-flex; align-items: center; gap: 6px; transition: all 0.3s; box-shadow: 0 4px 12px rgba(37, 211, 102, 0.15);">
                    <span>Send via WhatsApp 🚀</span>
                </button>
            </div>
        </div>
    </div>

    <script>
    let currentInquiryId = null;
    let currentClientPhone = "";

    function openReplyModal(event, id, name, phone, title, rawMessage) {
        event.preventDefault();
        currentInquiryId = id;
        currentClientPhone = phone.replace(/[^0-9]/g, '');
        
        document.getElementById('modal-client-name').innerText = name;
        document.getElementById('modal-client-phone').innerText = phone;
        document.getElementById('modal-client-subject').innerText = title;
        
        // Auto-filled message template
        const autoMsg = `Hello ${name},\n\nThank you for contacting OXO. We received your inquiry regarding '${title}': "${rawMessage}".\n\nOur concierge team is reviewing it. How can we help you further?`;
        document.getElementById('modal-reply-text').value = autoMsg;
        
        // Reset Copy Button label
        const copyLabel = document.getElementById('copy-btn-label');
        copyLabel.innerHTML = "📋 Copy Logo Image";
        
        document.getElementById('concierge-reply-modal').style.display = 'flex';
    }

    function closeReplyModal() {
        document.getElementById('concierge-reply-modal').style.display = 'none';
    }

    async function copyLogoToClipboard(btn) {
        const label = document.getElementById('copy-btn-label');
        const originalText = label.innerHTML;
        label.innerHTML = "⏳ Copying...";
        btn.disabled = true;
        
        try {
            const logoUrl = '../assets/images/logo.png';
            const response = await fetch(logoUrl);
            if (!response.ok) throw new Error('Failed to fetch image file.');
            const blob = await response.blob();
            
            // Write the image to the clipboard
            const item = new ClipboardItem({ [blob.type]: blob });
            await navigator.clipboard.write([item]);
            
            label.innerHTML = "✅ Logo Copied!";
            btn.style.borderColor = "#20BA5A";
            btn.style.color = "#20BA5A";
            btn.style.background = "rgba(37, 211, 102, 0.05)";
            
            setTimeout(() => {
                label.innerHTML = originalText;
                btn.style.borderColor = "rgba(10, 46, 36, 0.15)";
                btn.style.color = "#0a2e24";
                btn.style.background = "#ffffff";
                btn.disabled = false;
            }, 3000);
        } catch (err) {
            console.error('Clipboard copy failed:', err);
            label.innerHTML = "❌ Failed to Copy";
            setTimeout(() => {
                label.innerHTML = originalText;
                btn.disabled = false;
            }, 3000);
        }
    }

    function submitWhatsAppReply() {
        const message = document.getElementById('modal-reply-text').value;
        const url = `https://wa.me/${currentClientPhone}?text=${encodeURIComponent(message)}`;
        
        // Open WhatsApp
        window.open(url, '_blank');
        
        // Auto-mark the inquiry as replied/addressed in OXO
        if (currentInquiryId) {
            fetch(`index.php?tab=analytics&action=address&inquiry_id=${currentInquiryId}`)
                .then(response => {
                    if (response.ok) {
                        // Success callback: refresh dashboard to update stats and table status
                        window.location.reload();
                    }
                })
                .catch(err => console.error('Failed to mark inquiry as addressed:', err));
        }
        
        closeReplyModal();
    }
    </script>
    <?php endif; ?>
</body>
</html>
