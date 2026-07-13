<?php
/**
 * Product Editor (Add/Edit CRUD) for OXO Furniture
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../includes/db.php';

// Force authentication
require_admin_login();

$db = get_db_connection();
if (!$db) {
    header("Location: index.php");
    exit;
}
$pending_inquiries = 0;
    try {
        $stmt = $db->query("SELECT COUNT(*) FROM `oxo_consultations` WHERE `status` = 'Pending'");
        $pending_inquiries = (int)$stmt->fetchColumn();
    } catch (\Exception $e) {
        error_log("Failed to count inquiries: " . $e->getMessage());
    }

$is_edit = isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id']);
$product_id = $is_edit ? trim($_GET['id']) : '';

// Form state variables
$id = '';
$title = '';
$price = '';
$category = 'sofas';
$material_slug = 'wood';
$brand_id = '';
$image = '';
$description = '';
$specs = '';
$height_cm = 85;
$width_cm = 100;
$length_cm = 240;
$gallery = '';
$details = [
    'Material' => '',
    'Construction' => '',
    'Care Instructions' => '',
    'Shipping' => ''
];

$errors = [];
$success_msg = '';
$color_id = null;

// Load available categories, materials, brands, and colors from DB for selection
$categories_list = [];
$materials_list = [];
$brands_list = [];
$colors_list = [];
if ($db) {
    try {
        $categories_list = $db->query("SELECT * FROM `oxo_categories` ORDER BY `name` ASC")->fetchAll();
        $materials_list = $db->query("SELECT * FROM `oxo_materials` ORDER BY `name` ASC")->fetchAll();
        $brands_list = $db->query("SELECT * FROM `oxo_brands` ORDER BY `name` ASC")->fetchAll();
        $colors_list = $db->query("SELECT * FROM `oxo_colors` ORDER BY `name` ASC")->fetchAll();
    } catch (\Exception $e) {
        error_log("Failed to load options in product-editor: " . $e->getMessage());
    }
}

// Load product details if in Edit Mode
if ($is_edit) {
    try {
        $stmt = $db->prepare("SELECT * FROM `oxo_products` WHERE `id` = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();
        
        if ($product) {
            $id = $product['id'];
            $title = $product['title'];
            $price = $product['price'];
            $category = $product['category'];
            $material_slug = isset($product['material_slug']) ? $product['material_slug'] : 'wood';
            $brand_id = isset($product['brand_id']) ? $product['brand_id'] : '';
            $image = $product['image'];
            $description = $product['description'];
            $specs = $product['specs'];
            $height_cm = isset($product['height_cm']) ? (int)$product['height_cm'] : 85;
            $width_cm = isset($product['width_cm']) ? (int)$product['width_cm'] : 100;
            $length_cm = isset($product['length_cm']) ? (int)$product['length_cm'] : 240;
            $color_id = isset($product['color_id']) ? $product['color_id'] : null;
            $gallery = isset($product['gallery']) ? $product['gallery'] : '';
            
            $decoded_details = json_decode($product['details'], true);
            if (is_array($decoded_details)) {
                $details = array_merge($details, $decoded_details);
            }
        } else {
            $errors[] = "The design requested for editing does not exist.";
            $is_edit = false; // Fallback to Add Mode
        }
    } catch (\Exception $e) {
        $errors[] = "Error reading product details: " . $e->getMessage();
    }
}

// Action: Mark inquiry as addressed from this product details view
if ($is_edit && isset($_GET['inquiry_action']) && $_GET['inquiry_action'] === 'address' && isset($_GET['inquiry_id'])) {
    $inquiry_id = (int)$_GET['inquiry_id'];
    try {
        $up_stmt = $db->prepare("UPDATE `oxo_consultations` SET `status` = 'Addressed' WHERE `id` = ?");
        $up_stmt->execute([$inquiry_id]);
        
        // Redirect to avoid refresh/re-submission anomalies
        header("Location: product-editor.php?action=edit&id=" . urlencode($product_id));
        exit;
    } catch (\Exception $e) {
        error_log("Failed to update inquiry status in product-editor: " . $e->getMessage());
    }
}

// Load inquiries for this specific product
$product_inquiries = [];
if ($is_edit && !empty($title)) {
    try {
        $inq_stmt = $db->prepare("SELECT * FROM `oxo_consultations` WHERE `product_title` = ? ORDER BY `created_at` DESC");
        $inq_stmt->execute([$title]);
        $product_inquiries = $inq_stmt->fetchAll();
    } catch (\Exception $e) {
        error_log("Failed to load inquiries for specific product: " . $e->getMessage());
    }
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? trim($_POST['id']) : '';
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $price = isset($_POST['price']) ? (int)$_POST['price'] : 0;
    $category = isset($_POST['category']) ? trim($_POST['category']) : '';
    $material_slug = isset($_POST['material_slug']) ? trim($_POST['material_slug']) : 'wood';
    $brand_id = isset($_POST['brand_id']) && $_POST['brand_id'] !== '' ? (int)$_POST['brand_id'] : null;
    $image_url_input = isset($_POST['image_url']) ? trim($_POST['image_url']) : '';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    
    $height_cm = isset($_POST['height_cm']) ? (int)$_POST['height_cm'] : 85;
    $width_cm = isset($_POST['width_cm']) ? (int)$_POST['width_cm'] : 100;
    $length_cm = isset($_POST['length_cm']) ? (int)$_POST['length_cm'] : 240;
    $selected_color_ids = isset($_POST['color_ids']) && is_array($_POST['color_ids']) ? array_map('intval', $_POST['color_ids']) : [];
    $color_id = !empty($selected_color_ids) ? $selected_color_ids[0] : null;
    $color_ids_json = !empty($selected_color_ids) ? json_encode($selected_color_ids) : null;
    $specs = "Dimensions: W: {$width_cm}cm x D: {$length_cm}cm x H: {$height_cm}cm";
    $gallery_url_input = isset($_POST['gallery_url']) ? trim($_POST['gallery_url']) : '';
    
    // Custom Details Array builder
    $details['Material'] = isset($_POST['detail_material']) ? trim($_POST['detail_material']) : '';
    $details['Construction'] = isset($_POST['detail_construction']) ? trim($_POST['detail_construction']) : '';
    $details['Care Instructions'] = isset($_POST['detail_care']) ? trim($_POST['detail_care']) : '';
    $details['Shipping'] = isset($_POST['detail_shipping']) ? trim($_POST['detail_shipping']) : '';

    // Validation Rules
    if (!$is_edit) {
        // Auto-generate ID slug from Title
        $id = strtolower(preg_replace('/[^a-zA-Z0-9\-]+/', '-', $title));
        $id = trim($id, '-');
        
        if (empty($id)) {
            $errors[] = "A valid Product Title is required to generate the identifier.";
        } else {
            // Check uniqueness of ID
            $check_stmt = $db->prepare("SELECT COUNT(*) FROM `oxo_products` WHERE `id` = ?");
            $check_stmt->execute([$id]);
            if ($check_stmt->fetchColumn() > 0) {
                $id .= '-' . substr(md5(time() . rand()), 0, 4);
            }
        }
    }

    if (empty($title)) {
        $errors[] = "Product Title is required.";
    }

    if ($price <= 0) {
        $errors[] = "Price must be a valid positive integer.";
    }

    if (empty($category)) {
        $errors[] = "Please select or type a category.";
    }

    // Unified Visual Assets handling
    $upload_map = [];
    $target_id = $is_edit ? $product_id : $id;
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
                    $new_file_name = $target_id . '_asset_' . $i . '_' . time() . '.' . $file_ext;
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
    
    // Fallback: if no order was specified but files were uploaded, use uploaded map
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
            $color_id = $final_image_paths[0]['color_id'];
        }
    }
    if ($color_id && !in_array((int)$color_id, $selected_color_ids)) {
        $selected_color_ids[] = (int)$color_id;
        $color_ids_json = json_encode($selected_color_ids);
    } else {
        // Keep current image if no changes made
        $image_path = $is_edit ? $image : '';
        if (!empty($gallery) && $is_edit) {
            $gallery_decoded = json_decode($gallery, true);
            if (is_array($gallery_decoded)) {
                $gallery_paths = $gallery_decoded;
            }
        }
    }
    
    if (empty($image_path)) {
        $errors[] = "At least one image is required. Please upload files or enter image URLs.";
    }
    
    $gallery_json = !empty($gallery_paths) ? json_encode($gallery_paths) : NULL;

    // Execute Save if there are no errors
    if (empty($errors)) {
        try {
            $json_details = json_encode($details);
            
            if ($is_edit) {
                // Update Entry
                 $stmt = $db->prepare("UPDATE `oxo_products` SET 
                    `title` = ?, 
                    `price` = ?, 
                    `category` = ?, 
                    `image` = ?, 
                    `description` = ?, 
                    `specs` = ?, 
                    `details` = ?,
                    `material_slug` = ?,
                    `brand_id` = ?,
                    `gallery` = ?,
                    `height_cm` = ?,
                    `width_cm` = ?,
                    `length_cm` = ?,
                    `color_id` = ?,
                    `color_ids` = ?
                    WHERE `id` = ?");
                $stmt->execute([
                    $title, 
                    $price, 
                    $category, 
                    $image_path, 
                    $description, 
                    $specs, 
                    $json_details, 
                    $material_slug, 
                    $brand_id, 
                    $gallery_json,
                    $height_cm,
                    $width_cm,
                    $length_cm,
                    $color_id,
                    $color_ids_json,
                    $product_id
                ]);
                
                $success_msg = "Creation updated successfully!";
                $image = $image_path; // update current state image
                $gallery = $gallery_json; // update gallery state
            } else {
                // Insert Entry
                $stmt = $db->prepare("INSERT INTO `oxo_products` 
                    (`id`, `title`, `price`, `category`, `image`, `description`, `specs`, `details`, `material_slug`, `brand_id`, `gallery`, `height_cm`, `width_cm`, `length_cm`, `color_id`) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $id, 
                    $title, 
                    $price, 
                    $category, 
                    $image_path, 
                    $description, 
                    $specs, 
                    $json_details, 
                    $material_slug, 
                    $brand_id, 
                    $gallery_json,
                    $height_cm,
                    $width_cm,
                    $length_cm,
                    $color_id
                ]);
                
                // Redirect back to dashboard on successful add to prevent double post
                header("Location: index.php?msg=added");
                exit;
            }
        } catch (\Exception $e) {
            $errors[] = "Failed to save to database: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $is_edit ? 'Edit Design — ' . htmlspecialchars($title) : 'Create New Creation'; ?> — OXO Admin</title>
    <!-- Fonts & Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@300;400;500;700;800&family=Playfair+Display:ital,wght@0,400;0,600;1,400&family=Syne:wght@700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin-style.css">
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
                <a href="index.php?tab=products" class="sidebar-link active">
                    <i class="fa-solid fa-couch"></i> Creations
                </a>
                <a href="index.php?tab=add_product" class="sidebar-link">
                    <i class="fa-solid fa-circle-plus"></i> Add Product
                </a>
                <a href="index.php?tab=analytics" class="sidebar-link">
                    <i class="fa-solid fa-chart-line"></i> Analytics & Inquiries
                    <?php if ($pending_inquiries > 0): ?>
                        <span class="sidebar-badge"><?php echo $pending_inquiries; ?></span>
                    <?php endif; ?>
                </a>
                <a href="index.php?tab=collections" class="sidebar-link">
                    <i class="fa-solid fa-shapes"></i> Collections
                </a>
                <a href="index.php?tab=settings" class="sidebar-link">
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
        
        <!-- Navigation Breadcrumbs / Title -->
        <div class="page-header">
            <div>
                <p style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.1em; color: var(--color-accent); margin-bottom: 5px;">
                    <a href="index.php" style="color: var(--color-gray);"><i class="fa-solid fa-arrow-left"></i> Dashboard</a> / 
                    <?php echo $is_edit ? 'Edit' : 'Create'; ?>
                </p>
                <h2 class="page-title"><?php echo $is_edit ? 'Edit <span>Creation</span>' : 'New <span>Creation</span>'; ?></h2>
            </div>
            <a href="index.php" class="action-btn secondary">
                Cancel
            </a>
        </div>

        <!-- Success & Error Messages -->
        <?php if (!empty($success_msg)): ?>
            <div class="alert alert-success" style="margin-bottom: 30px;">
                <i class="fa-solid fa-circle-check" style="margin-right: 8px;"></i>
                <?php echo htmlspecialchars($success_msg); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert" style="margin-bottom: 30px;">
                <h4 style="margin-bottom: 8px;"><i class="fa-solid fa-triangle-exclamation" style="margin-right: 8px;"></i> Validation Errors:</h4>
                <ul style="list-style-type: square; padding-left: 20px; font-size: 0.85rem;">
                    <?php foreach ($errors as $err): ?>
                        <li><?php echo htmlspecialchars($err); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Editor Form -->
        <form action="" method="POST" enctype="multipart/form-data">
            <div class="editor-grid">
                
                <!-- Left Main Section -->
                <div class="editor-left">
                    <div class="editor-card">
                        <h3 class="editor-card-title">Core Specifications</h3>
                        
                        <div class="form-row">
                            <?php if ($is_edit): ?>
                                <!-- Send the ID through hidden field because disabled controls are omitted in POST requests -->
                                <input type="hidden" name="id" value="<?php echo htmlspecialchars($id); ?>">
                            <?php endif; ?>
                            
                            <div class="form-group">
                                <label for="category">Category</label>
                                <select id="category" name="category" class="input-control" required>
                                    <?php foreach ($categories_list as $cat): ?>
                                        <option value="<?php echo htmlspecialchars($cat['slug']); ?>" <?php echo $category === $cat['slug'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="material_slug">Material Type</label>
                                <select id="material_slug" name="material_slug" class="input-control" required>
                                    <?php foreach ($materials_list as $mat): ?>
                                        <option value="<?php echo htmlspecialchars($mat['slug']); ?>" <?php echo $material_slug === $mat['slug'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($mat['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                         <!-- Product Colors Selection (Multiple Colors) -->
                         <div class="form-group" style="margin-bottom: 20px;">
                             <label style="font-weight: 700; display: block; margin-bottom: 10px;">Select Available Colors for this Product</label>
                             <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 12px; background: var(--color-bg-panel); padding: 15px; border-radius: 8px; border: 1px solid var(--color-panel-border);">
                                 <?php 
                                 $checked_color_ids = [];
                                 if (isset($product['color_ids']) && !empty($product['color_ids'])) {
                                     $decoded = json_decode($product['color_ids'], true);
                                     if (is_array($decoded)) {
                                         $checked_color_ids = array_map('intval', $decoded);
                                     }
                                 }
                                 // Fallback: if no color_ids but single color_id is set
                                 if (empty($checked_color_ids) && !empty($color_id)) {
                                     $checked_color_ids[] = (int)$color_id;
                                 }
                                 ?>
                                 <?php foreach ($colors_list as $color): ?>
                                     <label class="color-checkbox-label" style="display: flex; align-items: center; gap: 8px; cursor: pointer; user-select: none;">
                                         <input type="checkbox" name="color_ids[]" value="<?php echo $color['id']; ?>" class="color-select-checkbox" data-id="<?php echo $color['id']; ?>" data-name="<?php echo htmlspecialchars($color['name']); ?>" data-hex="<?php echo htmlspecialchars($color['hex']); ?>" <?php echo in_array((int)$color['id'], $checked_color_ids) ? 'checked' : ''; ?> style="width: 16px; height: 16px; cursor: pointer;">
                                         <span style="display: inline-block; width: 14px; height: 14px; border-radius: 50%; background-color: <?php echo htmlspecialchars($color['hex']); ?>; border: 1px solid var(--color-panel-border); vertical-align: middle;"></span>
                                         <span style="font-size: 0.85rem; color: var(--color-primary); font-weight: 600;"><?php echo htmlspecialchars($color['name']); ?></span>
                                     </label>
                                 <?php endforeach; ?>
                             </div>
                         </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="title">Title / Name</label>
                                <input type="text" id="title" name="title" class="input-control" 
                                       value="<?php echo htmlspecialchars($title); ?>" 
                                       placeholder="e.g. Aurelia Accent Chair" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="price">Price (INR - ₹)</label>
                                <input type="number" id="price" name="price" class="input-control" 
                                       value="<?php echo htmlspecialchars($price); ?>" 
                                       placeholder="e.g. 85000" min="1" required>
                            </div>

                            <div class="form-group">
                                <label for="brand_id">Brand Partner</label>
                                <select id="brand_id" name="brand_id" class="input-control" required>
                                    <option value="">-- Select Brand --</option>
                                    <?php foreach ($brands_list as $b): ?>
                                        <option value="<?php echo $b['id']; ?>" <?php echo (int)$brand_id === (int)$b['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($b['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="description">Short Description</label>
                            <textarea id="description" name="description" class="input-control" rows="4" 
                                      placeholder="Provide a luxurious description showcasing the craftsmanship and design..." required><?php echo htmlspecialchars($description); ?></textarea>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="height_cm">Height (cm)</label>
                                <input type="number" id="height_cm" name="height_cm" class="input-control" 
                                       value="<?php echo htmlspecialchars($height_cm); ?>" required placeholder="e.g. 85" min="1">
                            </div>
                            <div class="form-group">
                                <label for="width_cm">Width (cm)</label>
                                <input type="number" id="width_cm" name="width_cm" class="input-control" 
                                       value="<?php echo htmlspecialchars($width_cm); ?>" required placeholder="e.g. 100" min="1">
                            </div>
                            <div class="form-group">
                                <label for="length_cm">Length (cm)</label>
                                <input type="number" id="length_cm" name="length_cm" class="input-control" 
                                       value="<?php echo htmlspecialchars($length_cm); ?>" required placeholder="e.g. 240" min="1">
                            </div>
                        </div>
                    </div>

                    <div class="editor-card">
                        <h3 class="editor-card-title">Bespoke Specifications Accordions</h3>
                        
                        <div class="form-group">
                            <label for="detail_material">Material Detail</label>
                            <textarea id="detail_material" name="detail_material" class="input-control" rows="2" 
                                      placeholder="Details regarding luxury threads, wool ratio, wood quality..."><?php echo htmlspecialchars($details['Material']); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="detail_construction">Construction Detail</label>
                            <textarea id="detail_construction" name="detail_construction" class="input-control" rows="2" 
                                      placeholder="Internal framing, suspension mechanisms, weight limitations..."><?php echo htmlspecialchars($details['Construction']); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="detail_care">Care Instructions</label>
                            <textarea id="detail_care" name="detail_care" class="input-control" rows="2" 
                                      placeholder="Instructions for cleaning, vacuuming, exposure limits..."><?php echo htmlspecialchars($details['Care Instructions']); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="detail_shipping">Shipping & Handover</label>
                            <textarea id="detail_shipping" name="detail_shipping" class="input-control" rows="2" 
                                      placeholder="Delivery timelines, packaging methods, assembly service..."><?php echo htmlspecialchars($details['Shipping']); ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Right Sidebar Section -->
                <div class="editor-right">
                    <div class="editor-card" style="position: sticky; top: 100px;">
                        <h3 class="editor-card-title">Asset Control</h3>
                        
                        <div class="form-group" style="margin-bottom: 20px;">
                            <label>Upload Photo Files (Multiple selections supported)</label>
                            <div class="upload-container" onclick="document.getElementById('gallery_files').click();" style="padding: 25px; border: 2px dashed var(--color-panel-border); border-radius: 8px; text-align: center; cursor: pointer;">
                                <i class="fa-solid fa-cloud-arrow-up upload-icon" style="font-size: 1.8rem; color: var(--color-accent); margin-bottom: 10px;"></i>
                                <div class="upload-text"><strong style="font-size: 0.85rem;">Click to Upload Photos</strong></div>
                                <div style="font-size: 0.68rem; color: var(--color-gray); margin-top: 5px;">PNG, JPG, WEBP formats. Drag & drop files.</div>
                            </div>
                            <input type="file" id="gallery_files" name="gallery_files[]" class="upload-file-input" accept="image/*" multiple style="display: none;">
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

                        <!-- Save Actions -->
                        <div class="form-actions" style="margin-top: 30px;">
                            <button type="submit" class="action-btn" style="width: 100%; justify-content: center;">
                                <i class="fa-solid fa-floppy-disk"></i> Save Creation
                            </button>
                        </div>
                    </div>
                </div>

            </div>
        </form>

        <?php if ($is_edit): ?>
            <!-- Product Inquiries Card -->
            <div class="editor-card" style="margin-top: 30px; clear: both;">
                <h3 class="editor-card-title" style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--color-panel-border); padding-bottom: 12px; margin-bottom: 20px;">
                    <span style="display: inline-flex; align-items: center; gap: 8px;">
                        <i class="fa-solid fa-comments" style="color: var(--color-accent); font-size: 1.1rem;"></i>
                        Product Inquiries 
                        <span style="font-size: 0.78rem; font-weight: 500; background: var(--color-panel-border); padding: 2px 8px; border-radius: 12px; color: var(--color-primary);">
                            <?php echo count($product_inquiries); ?> inquiries
                        </span>
                    </span>
                </h3>
                
                <?php if (empty($product_inquiries)): ?>
                    <div style="text-align: center; padding: 30px 15px; color: var(--color-gray);">
                        <i class="fa-regular fa-comment-dots" style="font-size: 2.2rem; margin-bottom: 12px; opacity: 0.5; display: block; color: var(--color-accent);"></i>
                        <p style="font-size: 0.88rem; margin: 0;">No bespoke inquiries received for this specific product yet.</p>
                    </div>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.82rem; min-width: 600px;">
                            <thead>
                                <tr style="border-bottom: 2px solid var(--color-panel-border); font-family: var(--font-title); font-weight: 700; text-transform: uppercase; color: var(--color-gray); letter-spacing: 0.5px;">
                                    <th style="padding: 12px 10px;">Client</th>
                                    <th style="padding: 12px 10px;">Contact Details</th>
                                    <th style="padding: 12px 10px;">Message</th>
                                    <th style="padding: 12px 10px;">Status</th>
                                    <th style="padding: 12px 10px; text-align: right;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($product_inquiries as $inq): 
                                    $is_pending = (strtolower($inq['status']) === 'pending');
                                    $status_color = $is_pending ? '#E05A47' : '#2D8B57';
                                ?>
                                    <tr style="border-bottom: 1px solid var(--color-panel-border); transition: background 0.2s;">
                                        <td style="padding: 14px 10px; font-weight: 700; color: var(--color-primary);"><?php echo htmlspecialchars($inq['name']); ?></td>
                                        <td style="padding: 14px 10px;">
                                            <a href="mailto:<?php echo htmlspecialchars($inq['email']); ?>" style="color: var(--color-accent); font-weight: 500; font-family: var(--font-numeric); text-decoration: none; display: block; margin-bottom: 5px;">
                                                <i class="fa-regular fa-envelope" style="margin-right: 4px;"></i><?php echo htmlspecialchars($inq['email']); ?>
                                            </a>
                                            <?php if (!empty($inq['whatsapp'])): ?>
                                                <div style="margin-top: 4px;">
                                                    <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $inq['whatsapp']); ?>" target="_blank" style="color: #25D366; font-weight: 500; font-family: var(--font-numeric); display: inline-flex; align-items: center; gap: 4px; text-decoration: none;">
                                                        <i class="fa-brands fa-whatsapp"></i> <?php echo htmlspecialchars($inq['whatsapp']); ?>
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td style="padding: 14px 10px; max-width: 320px; line-height: 1.5; color: #4A564E;"><?php echo nl2br(htmlspecialchars($inq['message'])); ?></td>
                                        <td style="padding: 14px 10px;">
                                            <span style="display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; background: <?php echo $status_color; ?>12; color: <?php echo $status_color; ?>;">
                                                <?php echo htmlspecialchars($inq['status']); ?>
                                            </span>
                                        </td>
                                        <td style="padding: 14px 10px; text-align: right;">
                                            <?php if ($is_pending): ?>
                                                <a href="product-editor.php?action=edit&id=<?php echo urlencode($id); ?>&inquiry_action=address&inquiry_id=<?php echo $inq['id']; ?>" 
                                                   class="action-btn" style="padding: 6px 12px; font-size: 0.7rem; border-radius: 4px; display: inline-flex; align-items: center; gap: 4px; border: 1px solid var(--color-accent); background: transparent; color: var(--color-accent); text-decoration: none;">
                                                    <i class="fa-solid fa-check"></i> Mark Addressed
                                                </a>
                                            <?php else: ?>
                                                <span style="color: var(--color-gray); font-size: 0.75rem; font-weight: 600; text-transform: uppercase;"><i class="fa-solid fa-circle-check"></i> Resolved</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const colors_list = <?php echo json_encode($colors_list); ?>;
            const hInput = document.getElementById('height_cm');
            const wInput = document.getElementById('width_cm');
            const lInput = document.getElementById('length_cm');
            
            const initialImages = [];
            <?php if (!empty($image)): ?>
                initialImages.push({ 
                    id: "<?php echo htmlspecialchars($image); ?>", 
                    src: "../<?php echo htmlspecialchars($image); ?>",
                    color_id: <?php echo !empty($color_id) ? (int)$color_id : 'null'; ?>
                });
            <?php endif; ?>
            <?php 
            if (!empty($gallery)) {
                $decoded = json_decode($gallery, true);
                if (is_array($decoded)) {
                    foreach ($decoded as $gitem) {
                        $gpath = is_array($gitem) ? $gitem['path'] : $gitem;
                        $gcolor = (is_array($gitem) && isset($gitem['color_id'])) ? (int)$gitem['color_id'] : 'null';
                        if ($gpath !== $image) { // avoid duplicate cover in gallery array
                            echo "initialImages.push({ id: '" . htmlspecialchars($gpath) . "', src: '../" . htmlspecialchars($gpath) . "', color_id: {$gcolor} });\n";
                        }
                    }
                }
            }
            ?>

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

            let imageList = [...initialImages];

            const fileInput = document.getElementById('gallery_files');
            const urlInput = document.getElementById('add_image_url_field');
            const btnAddUrl = document.getElementById('btn_add_url_to_gallery');
            const previewGrid = document.getElementById('gallery_preview_grid');
            const orderJsonInput = document.getElementById('image_order_json');

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
            if (btnAddUrl && urlInput) {
                btnAddUrl.addEventListener('click', () => {
                    const url = urlInput.value.trim();
                    if (url) {
                        imageList.push({
                            id: url,
                            src: url.startsWith('http') || url.startsWith('/') ? url : '../' + url
                        });
                        urlInput.value = '';
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

</body>
</html>
