<?php
/**
 * Main Admin Dashboard for OXO Furniture
 * Professional control center with tabbed navigation (Products, Analytics, Settings).
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/generate-docs.php';

// Force authentication
require_admin_login();

$db = get_db_connection();
$message = '';
$message_type = 'success';

// Determine active tab & section (default: 'products')
$current_tab = isset($_GET['tab']) ? trim($_GET['tab']) : 'products';
$current_section = isset($_GET['section']) ? trim($_GET['section']) : 'overview';

if ($current_tab === 'brands' || $current_tab === 'collections') {
    $current_tab = 'settings';
    $current_section = 'collections';
} elseif ($current_tab === 'announcement') {
    $current_tab = 'settings';
    $current_section = 'announcement';
}

$valid_tabs = ['products', 'add_product', 'analytics', 'settings'];
if (!in_array($current_tab, $valid_tabs)) {
    $current_tab = 'products';
}

// Compress and optimize uploaded images to small KB format for fast loading
function compress_admin_uploaded_image($source_filepath, $quality = 78) {
    if (!file_exists($source_filepath) || filesize($source_filepath) === 0) return;
    $info = @getimagesize($source_filepath);
    if (!$info) return;

    $mime = $info['mime'];
    $width = $info[0];
    $height = $info[1];
    $max_dim = 1200;

    if ($width > $max_dim || $height > $max_dim) {
        $ratio = min($max_dim / $width, $max_dim / $height);
        $new_width = (int)round($width * $ratio);
        $new_height = (int)round($height * $ratio);
    } else {
        $new_width = $width;
        $new_height = $height;
    }

    $image = null;
    switch ($mime) {
        case 'image/jpeg':
        case 'image/jpg':
            if (function_exists('imagecreatefromjpeg')) $image = @imagecreatefromjpeg($source_filepath);
            break;
        case 'image/png':
            if (function_exists('imagecreatefrompng')) $image = @imagecreatefrompng($source_filepath);
            break;
        case 'image/webp':
            if (function_exists('imagecreatefromwebp')) $image = @imagecreatefromwebp($source_filepath);
            break;
    }

    if ($image) {
        $canvas = imagecreatetruecolor($new_width, $new_height);
        $bg = imagecolorallocate($canvas, 255, 255, 255);
        imagefill($canvas, 0, 0, $bg);
        imagecopyresampled($canvas, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
        @imagejpeg($canvas, $source_filepath, $quality);
        imagedestroy($image);
        imagedestroy($canvas);
    }
}

// 1. ACTION: Handle single product deletion
if ($current_tab === 'products' && isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $delete_id = trim($_GET['id']);
    if ($db) {
        try {
            // Fetch main image and gallery images to delete local upload files
            $img_stmt = $db->prepare("SELECT `image`, `gallery` FROM `oxo_products` WHERE `id` = ?");
            $img_stmt->execute([$delete_id]);
            $prod_data = $img_stmt->fetch(PDO::FETCH_ASSOC);

            // Delete product entry from DB
            $stmt = $db->prepare("DELETE FROM `oxo_products` WHERE `id` = ?");
            $stmt->execute([$delete_id]);

            if ($stmt->rowCount() > 0) {
                $files_removed = 0;
                if ($prod_data) {
                    $img_path = $prod_data['image'] ?? null;
                    if ($img_path && file_exists(__DIR__ . '/../' . $img_path) && strpos($img_path, 'uploads/') !== false) {
                        @unlink(__DIR__ . '/../' . $img_path);
                        $files_removed++;
                    }
                    if (!empty($prod_data['gallery'])) {
                        $gal_arr = json_decode($prod_data['gallery'], true);
                        if (is_array($gal_arr)) {
                            foreach ($gal_arr as $g_item) {
                                $g_path = is_array($g_item) ? ($g_item['path'] ?? '') : $g_item;
                                if ($g_path && file_exists(__DIR__ . '/../' . $g_path) && strpos($g_path, 'uploads/') !== false) {
                                    @unlink(__DIR__ . '/../' . $g_path);
                                    $files_removed++;
                                }
                            }
                        }
                    }
                }
                auto_sync_documentation();
                $message = "Creation '{$delete_id}' and all associated files were successfully deleted.";
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

// 1.5. ACTION: Handle bulk product deletion (multi-selection)
if ($current_tab === 'products' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_action']) && $_POST['form_action'] === 'bulk_delete_products' && $db) {
    $selected_ids = isset($_POST['selected_product_ids']) && is_array($_POST['selected_product_ids']) ? $_POST['selected_product_ids'] : [];

    if (!empty($selected_ids)) {
        $deleted_count = 0;
        $files_deleted_count = 0;

        foreach ($selected_ids as $p_id) {
            $p_id = trim($p_id);
            if (empty($p_id)) continue;

            try {
                $stmt = $db->prepare("SELECT `image`, `gallery` FROM `oxo_products` WHERE `id` = ?");
                $stmt->execute([$p_id]);
                $prod = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($prod) {
                    $img_path = $prod['image'] ?? '';
                    if ($img_path && strpos($img_path, 'uploads/') !== false) {
                        $full_main = __DIR__ . '/../' . $img_path;
                        if (file_exists($full_main)) {
                            @unlink($full_main);
                            $files_deleted_count++;
                        }
                    }

                    if (!empty($prod['gallery'])) {
                        $gal_arr = json_decode($prod['gallery'], true);
                        if (is_array($gal_arr)) {
                            foreach ($gal_arr as $g_item) {
                                $g_path = is_array($g_item) ? ($g_item['path'] ?? '') : $g_item;
                                if ($g_path && strpos($g_path, 'uploads/') !== false) {
                                    $full_gal = __DIR__ . '/../' . $g_path;
                                    if (file_exists($full_gal)) {
                                        @unlink($full_gal);
                                        $files_deleted_count++;
                                    }
                                }
                            }
                        }
                    }

                    $del_stmt = $db->prepare("DELETE FROM `oxo_products` WHERE `id` = ?");
                    $del_stmt->execute([$p_id]);
                    if ($del_stmt->rowCount() > 0) {
                        $deleted_count++;
                    }
                }
            } catch (\Exception $e) {
                error_log("Failed to bulk delete product {$p_id}: " . $e->getMessage());
            }
        }

        if ($deleted_count > 0) {
            auto_sync_documentation();
            $message = "Successfully deleted {$deleted_count} selected creation(s) from database and removed {$files_deleted_count} associated image file(s) from server disk.";
            $message_type = 'success';
        } else {
            $message = "No items were deleted.";
            $message_type = 'danger';
        }
    } else {
        $message = "Please select at least one creation to delete.";
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

// 3.5 ACTION: Handle WhatsApp Update
if ($current_tab === 'settings' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_action']) && $_POST['form_action'] === 'update_whatsapp') {
    $whatsapp_num = isset($_POST['whatsapp']) ? trim($_POST['whatsapp']) : '';
    if ($db) {
        try {
            $stmt = $db->prepare("UPDATE `oxo_admins` SET `whatsapp` = ? WHERE `username` = ?");
            $stmt->execute([$whatsapp_num, $_SESSION['admin_username']]);
            $message = "WhatsApp contact number successfully updated.";
            $message_type = 'success';
        } catch (\Exception $e) {
            $message = "Failed to update WhatsApp contact: " . $e->getMessage();
            $message_type = 'danger';
        }
    } else {
        $message = "Database offline. WhatsApp update disabled.";
        $message_type = 'danger';
    }
}

// 3.6 ACTION: Handle Site Static Content (CMS) Update
if ($current_tab === 'settings' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_action']) && $_POST['form_action'] === 'update_site_content') {
    if ($db) {
        try {
            $updated_count = 0;
            // Process posted text fields
            if (isset($_POST['content']) && is_array($_POST['content'])) {
                foreach ($_POST['content'] as $key => $value) {
                    $group = 'general';
                    if (strpos($key, 'hero_') === 0) $group = 'hero';
                    elseif (strpos($key, 'about_home_') === 0) $group = 'about_home';
                    elseif (strpos($key, 'about_page_') === 0 || strpos($key, 'about_card') === 0) $group = 'about_page';
                    elseif (strpos($key, 'contact_') === 0) $group = 'contact';
                    elseif (strpos($key, 'footer_') === 0) $group = 'footer';
                    
                    set_site_content($key, trim($value), $group);
                    $updated_count++;
                }
            }

            // Handle file uploads
            $cms_upload_dir = __DIR__ . '/../uploads/cms/';
            if (!file_exists($cms_upload_dir)) {
                @mkdir($cms_upload_dir, 0777, true);
            }

            // File mapping
            $file_fields = [
                'hero_media_file' => ['key' => 'hero_media_path', 'group' => 'hero'],
                'about_home_image_file' => ['key' => 'about_home_image', 'group' => 'about_home'],
                'about_page_heritage_img_file' => ['key' => 'about_page_heritage_img', 'group' => 'about_page'],
                'about_page_showroom_img_file' => ['key' => 'about_page_showroom_img', 'group' => 'about_page'],
                'about_page_shop_img_1_file' => ['key' => 'about_page_shop_img_1', 'group' => 'about_page'],
                'about_page_shop_img_2_file' => ['key' => 'about_page_shop_img_2', 'group' => 'about_page'],
                'about_page_shop_img_3_file' => ['key' => 'about_page_shop_img_3', 'group' => 'about_page'],
                'about_page_shop_img_4_file' => ['key' => 'about_page_shop_img_4', 'group' => 'about_page'],
                'about_page_shop_img_5_file' => ['key' => 'about_page_shop_img_5', 'group' => 'about_page'],
                'about_page_shop_img_6_file' => ['key' => 'about_page_shop_img_6', 'group' => 'about_page'],
            ];

            foreach ($file_fields as $field => $info) {
                if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
                    $ext = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
                    $filename = $info['key'] . '_' . time() . '.' . $ext;
                    $target = $cms_upload_dir . $filename;
                    if (move_uploaded_file($_FILES[$field]['tmp_name'], $target)) {
                        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                            compress_admin_uploaded_image($target);
                        }
                        $rel_path = 'uploads/cms/' . $filename;
                        set_site_content($info['key'], $rel_path, $info['group']);
                        $updated_count++;
                    }
                }
            }

            $message = "Site static content & CMS settings successfully saved!";
            $message_type = 'success';
        } catch (\Exception $e) {
            $message = "Failed to update site content: " . $e->getMessage();
            $message_type = 'danger';
        }
    } else {
        $message = "Database offline. Content update disabled.";
        $message_type = 'danger';
    }
}

// 3.7 ACTION: Handle Dynamic Shop Gallery Images CRUD
if ($current_tab === 'settings' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_action'])) {
    $fa = $_POST['form_action'];

    if (in_array($fa, ['add_shop_image', 'toggle_shop_image', 'delete_shop_image', 'edit_shop_image']) && $db) {
        try {
            // Ensure table exists & seeded
            get_shop_images(false);

            if ($fa === 'add_shop_image') {
                $title = trim($_POST['title'] ?? '');
                $caption = trim($_POST['caption'] ?? '');
                $sort_order = (int)($_POST['sort_order'] ?? 0);
                $is_active = isset($_POST['is_active']) ? 1 : 0;
                $image_path = trim($_POST['image_url'] ?? '');

                $cms_upload_dir = __DIR__ . '/../uploads/cms/';
                if (!file_exists($cms_upload_dir)) {
                    @mkdir($cms_upload_dir, 0777, true);
                }

                if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
                    $ext = strtolower(pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION));
                    $filename = 'shop_img_' . time() . '_' . rand(100, 999) . '.' . $ext;
                    $target = $cms_upload_dir . $filename;
                    if (move_uploaded_file($_FILES['image_file']['tmp_name'], $target)) {
                        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                            compress_admin_uploaded_image($target);
                        }
                        $image_path = 'uploads/cms/' . $filename;
                    }
                }

                if (!empty($image_path)) {
                    $stmt = $db->prepare("INSERT INTO `oxo_shop_images` (`title`, `caption`, `image_path`, `sort_order`, `is_active`) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$title ?: 'Showroom Image', $caption, $image_path, $sort_order, $is_active]);
                    $message = "Shop photo successfully added to gallery!";
                    $message_type = 'success';
                } else {
                    $message = "Please upload an image file or enter an image URL path.";
                    $message_type = 'danger';
                }
            } elseif ($fa === 'toggle_shop_image') {
                $img_id = (int)($_POST['shop_image_id'] ?? 0);
                $new_status = (int)($_POST['new_status'] ?? 0);
                $stmt = $db->prepare("UPDATE `oxo_shop_images` SET `is_active` = ? WHERE `id` = ?");
                $stmt->execute([$new_status, $img_id]);
                $message = "Shop photo visibility updated.";
                $message_type = 'success';
            } elseif ($fa === 'delete_shop_image') {
                $img_id = (int)($_POST['shop_image_id'] ?? 0);
                $stmt = $db->prepare("DELETE FROM `oxo_shop_images` WHERE `id` = ?");
                $stmt->execute([$img_id]);
                $message = "Shop photo deleted from gallery.";
                $message_type = 'success';
            } elseif ($fa === 'edit_shop_image') {
                $img_id = (int)($_POST['shop_image_id'] ?? 0);
                $title = trim($_POST['title'] ?? '');
                $caption = trim($_POST['caption'] ?? '');
                $sort_order = (int)($_POST['sort_order'] ?? 0);
                $image_path = trim($_POST['image_url'] ?? '');

                if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
                    $cms_upload_dir = __DIR__ . '/../uploads/cms/';
                    if (!file_exists($cms_upload_dir)) {
                        @mkdir($cms_upload_dir, 0777, true);
                    }
                    $ext = strtolower(pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION));
                    $filename = 'shop_img_' . time() . '_' . rand(100, 999) . '.' . $ext;
                    $target = $cms_upload_dir . $filename;
                    if (move_uploaded_file($_FILES['image_file']['tmp_name'], $target)) {
                        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                            compress_admin_uploaded_image($target);
                        }
                        $image_path = 'uploads/cms/' . $filename;
                    }
                }

                if (!empty($image_path)) {
                    $stmt = $db->prepare("UPDATE `oxo_shop_images` SET `title` = ?, `caption` = ?, `image_path` = ?, `sort_order` = ? WHERE `id` = ?");
                    $stmt->execute([$title, $caption, $image_path, $sort_order, $img_id]);
                } else {
                    $stmt = $db->prepare("UPDATE `oxo_shop_images` SET `title` = ?, `caption` = ?, `sort_order` = ? WHERE `id` = ?");
                    $stmt->execute([$title, $caption, $sort_order, $img_id]);
                }
                $message = "Shop photo updated successfully!";
                $message_type = 'success';
            }
        } catch (\Exception $e) {
            $message = "Shop image action failed: " . $e->getMessage();
            $message_type = 'danger';
        }
    }
}

// 4. ACTION: Handle Brand Deletion
if (($current_tab === 'collections' || $current_section === 'collections') && isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
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
if (($current_tab === 'collections' || $current_section === 'collections') && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_action']) && $_POST['form_action'] === 'add_brand') {
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
                            compress_admin_uploaded_image($target_file, 78);
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

// 5.5. ACTION: Handle Announcement Poster (Save, Toggle, Delete)
if (($current_tab === 'announcement' || $current_section === 'announcement') && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_action']) && $db) {
    $action = $_POST['form_action'];
    if ($action === 'save_announcement') {
        $title = isset($_POST['title']) ? trim($_POST['title']) : '';
        $subtitle = isset($_POST['subtitle']) ? trim($_POST['subtitle']) : '';
        $link_url = isset($_POST['link_url']) ? trim($_POST['link_url']) : '';
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $image_path = '';

        if (isset($_FILES['poster_file']) && $_FILES['poster_file']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . '/../assets/images/uploads/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            $file_name = basename($_FILES['poster_file']['name']);
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
            if (in_array($file_ext, $allowed)) {
                $new_name = 'announcement_' . time() . '_' . rand(100, 999) . '.' . $file_ext;
                $target = $upload_dir . $new_name;
                if (move_uploaded_file($_FILES['poster_file']['tmp_name'], $target)) {
                    compress_admin_uploaded_image($target, 85);
                    $image_path = 'assets/images/uploads/' . $new_name;
                }
            }
        } elseif (!empty($_POST['poster_url'])) {
            $image_path = trim($_POST['poster_url']);
        }

        if (!empty($image_path)) {
            try {
                if ($is_active === 1) {
                    $db->exec("UPDATE `oxo_announcements` SET `is_active` = 0");
                }
                $stmt = $db->prepare("INSERT INTO `oxo_announcements` (`title`, `subtitle`, `image_path`, `link_url`, `is_active`) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$title, $subtitle, $image_path, $link_url, $is_active]);

                $message = "Announcement Poster saved successfully and set to " . ($is_active ? "Active" : "Inactive") . ".";
                $message_type = 'success';
            } catch (\Exception $e) {
                $message = "Failed to save announcement poster: " . $e->getMessage();
                $message_type = 'danger';
            }
        } else {
            $message = "Please select a poster image file to upload or enter an image URL.";
            $message_type = 'danger';
        }
    } elseif ($action === 'toggle_announcement' && isset($_POST['announcement_id'])) {
        $ann_id = (int)$_POST['announcement_id'];
        $new_status = isset($_POST['new_status']) ? (int)$_POST['new_status'] : 0;
        try {
            if ($new_status === 1) {
                $db->exec("UPDATE `oxo_announcements` SET `is_active` = 0");
            }
            $stmt = $db->prepare("UPDATE `oxo_announcements` SET `is_active` = ? WHERE `id` = ?");
            $stmt->execute([$new_status, $ann_id]);
            $message = "Announcement Poster status updated successfully.";
            $message_type = 'success';
        } catch (\Exception $e) {
            $message = "Failed to update status: " . $e->getMessage();
            $message_type = 'danger';
        }
    } elseif ($action === 'delete_announcement' && isset($_POST['announcement_id'])) {
        $ann_id = (int)$_POST['announcement_id'];
        try {
            $stmt = $db->prepare("DELETE FROM `oxo_announcements` WHERE `id` = ?");
            $stmt->execute([$ann_id]);
            $message = "Announcement Poster removed successfully.";
            $message_type = 'success';
        } catch (\Exception $e) {
            $message = "Failed to delete poster: " . $e->getMessage();
            $message_type = 'danger';
        }
    }
}

// 6. ACTION: Handle Category Deletion
if (($current_tab === 'collections' || $current_section === 'collections') && isset($_GET['action']) && $_GET['action'] === 'delete_category' && isset($_GET['id'])) {
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
if (($current_tab === 'collections' || $current_section === 'collections') && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_action']) && $_POST['form_action'] === 'add_category') {
    $cat_name = isset($_POST['cat_name']) ? trim($_POST['cat_name']) : '';
    $cat_slug = isset($_POST['cat_slug']) ? trim($_POST['cat_slug']) : '';
    $cat_bg_color = isset($_POST['cat_bg_color']) ? trim($_POST['cat_bg_color']) : '';
    
    if (empty($cat_name) || empty($cat_slug)) {
        $message = "Category Name and Slug are required.";
        $message_type = 'danger';
    } else {
        // Enforce lowercase alphanumeric slug
        $cat_slug = preg_replace('/[^a-z0-9-]/', '', strtolower($cat_slug));
        
        if ($db) {
            try {
                $stmt = $db->prepare("INSERT INTO `oxo_categories` (`slug`, `name`, `bg_color`) VALUES (?, ?, ?)");
                $stmt->execute([$cat_slug, $cat_name, !empty($cat_bg_color) ? $cat_bg_color : null]);
                
                $message = "Category '{$cat_name}' added successfully.";
                $message_type = 'success';
            } catch (\Exception $e) {
                $message = "Failed to add category: " . $e->getMessage();
                $message_type = 'danger';
            }
        }
    }
}

// 7.5 ACTION: Handle Category Edit
if (($current_tab === 'collections' || $current_section === 'collections') && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_action']) && $_POST['form_action'] === 'edit_category') {
    $cat_id = (int)$_POST['cat_id'];
    $cat_name = isset($_POST['cat_name']) ? trim($_POST['cat_name']) : '';
    $cat_slug = isset($_POST['cat_slug']) ? trim($_POST['cat_slug']) : '';
    $cat_bg_color = isset($_POST['cat_bg_color']) ? trim($_POST['cat_bg_color']) : '';
    
    if (empty($cat_name) || empty($cat_slug)) {
        $message = "Category Name and Slug are required.";
        $message_type = 'danger';
    } else {
        $cat_slug = preg_replace('/[^a-z0-9-]/', '', strtolower($cat_slug));
        if ($db) {
            try {
                $stmt = $db->prepare("UPDATE `oxo_categories` SET `slug` = ?, `name` = ?, `bg_color` = ? WHERE `id` = ?");
                $stmt->execute([$cat_slug, $cat_name, !empty($cat_bg_color) ? $cat_bg_color : null, $cat_id]);
                $message = "Category '{$cat_name}' updated successfully.";
                $message_type = 'success';
            } catch (\Exception $e) {
                $message = "Failed to update category: " . $e->getMessage();
                $message_type = 'danger';
            }
        }
    }
}

// 7.6 ACTION: Handle Material Edit
if (($current_tab === 'collections' || $current_section === 'collections') && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_action']) && $_POST['form_action'] === 'edit_material') {
    $mat_id = (int)$_POST['mat_id'];
    $mat_name = isset($_POST['mat_name']) ? trim($_POST['mat_name']) : '';
    $mat_slug = isset($_POST['mat_slug']) ? trim($_POST['mat_slug']) : '';
    
    if (empty($mat_name) || empty($mat_slug)) {
        $message = "Material Name and Slug are required.";
        $message_type = 'danger';
    } else {
        $mat_slug = preg_replace('/[^a-z0-9-]/', '', strtolower($mat_slug));
        if ($db) {
            try {
                $stmt = $db->prepare("UPDATE `oxo_materials` SET `slug` = ?, `name` = ? WHERE `id` = ?");
                $stmt->execute([$mat_slug, $mat_name, $mat_id]);
                $message = "Material '{$mat_name}' updated successfully.";
                $message_type = 'success';
            } catch (\Exception $e) {
                $message = "Failed to update material: " . $e->getMessage();
                $message_type = 'danger';
            }
        }
    }
}

// 7.7 ACTION: Handle Color Edit
if (($current_tab === 'collections' || $current_section === 'collections') && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_action']) && $_POST['form_action'] === 'edit_color') {
    $color_id = (int)$_POST['color_id'];
    $color_name = isset($_POST['color_name']) ? trim($_POST['color_name']) : '';
    $color_hex = isset($_POST['color_hex']) ? trim($_POST['color_hex']) : '';
    
    if (empty($color_name) || empty($color_hex)) {
        $message = "Color Name and HEX Value are required.";
        $message_type = 'danger';
    } else {
        if ($db) {
            try {
                $stmt = $db->prepare("UPDATE `oxo_colors` SET `name` = ?, `hex` = ? WHERE `id` = ?");
                $stmt->execute([$color_name, $color_hex, $color_id]);
                $message = "Color '{$color_name}' updated successfully.";
                $message_type = 'success';
            } catch (\Exception $e) {
                $message = "Failed to update color: " . $e->getMessage();
                $message_type = 'danger';
            }
        }
    }
}

// 7.8 ACTION: Handle Brand Edit
if (($current_tab === 'collections' || $current_section === 'collections') && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_action']) && $_POST['form_action'] === 'edit_brand') {
    $brand_id = (int)$_POST['brand_id'];
    $brand_name = isset($_POST['brand_name']) ? trim($_POST['brand_name']) : '';
    $logo_url = isset($_POST['logo_url']) ? trim($_POST['logo_url']) : '';
    
    if (empty($brand_name)) {
        $message = "Brand Name is required.";
        $message_type = 'danger';
    } else {
        if ($db) {
            try {
                $logo_path = null;
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
                        if (move_uploaded_file($_FILES['logo_file']['tmp_name'], $upload_dir . $new_file_name)) {
                            $logo_path = 'assets/images/uploads/' . $new_file_name;
                        }
                    }
                } elseif (!empty($logo_url)) {
                    $logo_path = $logo_url;
                }
                
                if ($logo_path !== null) {
                    $stmt = $db->prepare("UPDATE `oxo_brands` SET `name` = ?, `logo_path` = ? WHERE `id` = ?");
                    $stmt->execute([$brand_name, $logo_path, $brand_id]);
                } else {
                    $stmt = $db->prepare("UPDATE `oxo_brands` SET `name` = ? WHERE `id` = ?");
                    $stmt->execute([$brand_name, $brand_id]);
                }
                
                $message = "Brand '{$brand_name}' updated successfully.";
                $message_type = 'success';
            } catch (\Exception $e) {
                $message = "Failed to update brand: " . $e->getMessage();
                $message_type = 'danger';
            }
        }
    }
}

// 8. ACTION: Handle Material Deletion
if (($current_tab === 'collections' || $current_section === 'collections') && isset($_GET['action']) && $_GET['action'] === 'delete_material' && isset($_GET['id'])) {
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
if (($current_tab === 'collections' || $current_section === 'collections') && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_action']) && $_POST['form_action'] === 'add_material') {
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
if (($current_tab === 'collections' || $current_section === 'collections') && isset($_GET['action']) && $_GET['action'] === 'delete_color' && isset($_GET['id'])) {
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
if (($current_tab === 'collections' || $current_section === 'collections') && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_action']) && $_POST['form_action'] === 'add_color') {
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
    $clean_words = array_filter(explode('-', preg_replace('/[^a-z0-9\-]/', '', strtolower($prod_title))));
    $stop_words = ['with', 'set', 'for', 'and', 'the', 'in', 'of', 'by', 'furniture'];
    $filtered = array_filter($clean_words, function($w) use ($stop_words) { return !in_array($w, $stop_words) && strlen($w) > 1; });
    $short_slug = implode('-', array_slice(!empty($filtered) ? $filtered : $clean_words, 0, 3));
    $prod_id = 'nk-' . ($short_slug ?: 'item-' . rand(100, 999));
    if (strlen($prod_id) > 25) $prod_id = rtrim(substr($prod_id, 0, 25), '-');
    
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
                        compress_admin_uploaded_image($target_file, 78);
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
        $total_catalog_value = 0;
        $used_categories = [];
        if ($total_products > 0) {
            $prices = array_column($products, 'price');
            $total_catalog_value = array_sum($prices);
            $average_price = $total_catalog_value / $total_products;
            $used_categories = array_unique(array_column($products, 'category'));
        }
        
        // Fetch inquiries
        $stmt = $db->query("SELECT * FROM `oxo_consultations` ORDER BY `created_at` DESC");
        $inquiries = $stmt->fetchAll();
        $total_inquiries = count($inquiries);
        $pending_inquiries = 0;
        $addressed_inquiries = 0;
        foreach ($inquiries as $inq) {
            if ($inq['status'] === 'Pending') {
                $pending_inquiries++;
            } else {
                $addressed_inquiries++;
            }
        }
        $inquiry_response_rate = $total_inquiries > 0 ? round(($addressed_inquiries / $total_inquiries) * 100, 1) : 0;
        
        // Fetch brands
        $stmt = $db->query("SELECT * FROM `oxo_brands` ORDER BY `created_at` DESC");
        $brands = $stmt->fetchAll();
        
        // Fetch categories list
        $categories_list = $db->query("SELECT * FROM `oxo_categories` ORDER BY `name` ASC")->fetchAll();
        $categories_count = !empty($categories_list) ? count($categories_list) : count($used_categories);

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
}

// Catalog Filtering & View Slicing (Default: Show Last 5 Recently Added)
$filter_category = isset($_GET['filter_category']) ? trim($_GET['filter_category']) : '';
$filter_material = isset($_GET['filter_material']) ? trim($_GET['filter_material']) : '';
$filter_brand = isset($_GET['filter_brand']) ? trim($_GET['filter_brand']) : '';
$filter_search = isset($_GET['filter_search']) ? trim($_GET['filter_search']) : '';
$show_all = isset($_GET['show_all']) && $_GET['show_all'] === '1';

$has_active_filter = !empty($filter_category) || !empty($filter_material) || !empty($filter_brand) || !empty($filter_search) || $show_all;

$filtered_products = $products;

if (!empty($filter_category)) {
    $filtered_products = array_filter($filtered_products, function($p) use ($filter_category) {
        return strtolower($p['category']) === strtolower($filter_category);
    });
}

if (!empty($filter_material)) {
    $filtered_products = array_filter($filtered_products, function($p) use ($filter_material) {
        return isset($p['material_slug']) && strtolower($p['material_slug']) === strtolower($filter_material);
    });
}

if (!empty($filter_brand)) {
    $filtered_products = array_filter($filtered_products, function($p) use ($filter_brand) {
        return isset($p['brand_id']) && (string)$p['brand_id'] === (string)$filter_brand;
    });
}

if (!empty($filter_search)) {
    $filtered_products = array_filter($filtered_products, function($p) use ($filter_search) {
        $q = strtolower($filter_search);
        return strpos(strtolower($p['title']), $q) !== false || strpos(strtolower($p['id']), $q) !== false;
    });
}

$filtered_products = array_values($filtered_products);

// Default view mode: If no filter is active, only show the last 5 recently added products!
if (!$has_active_filter) {
    $displayed_products = array_slice($filtered_products, 0, 5);
} else {
    $displayed_products = $filtered_products;
}

// --- DYNAMIC ANALYTICS & INSIGHTS COMPUTATIONS ---
// 1. Category Distribution
$cat_distribution = [];
foreach ($products as $p) {
    $c_slug = strtolower($p['category']);
    $c_name = ucfirst($p['category']);
    foreach ($categories_list as $c) {
        if (strtolower($c['slug']) === $c_slug) {
            $c_name = $c['name'];
            break;
        }
    }
    if (!isset($cat_distribution[$c_name])) {
        $cat_distribution[$c_name] = 0;
    }
    $cat_distribution[$c_name]++;
}
$analytics_cat_labels = array_keys($cat_distribution);
$analytics_cat_values = array_values($cat_distribution);

// 2. Material Breakdown
$mat_distribution = [];
foreach ($products as $p) {
    $m_slug = !empty($p['material_slug']) ? ucfirst($p['material_slug']) : 'Wood';
    if (!isset($mat_distribution[$m_slug])) {
        $mat_distribution[$m_slug] = 0;
    }
    $mat_distribution[$m_slug]++;
}
$analytics_mat_labels = array_keys($mat_distribution);
$analytics_mat_values = array_values($mat_distribution);

// 3. Price Tiers (Entry < 5k, Mid 5k-20k, Luxury > 20k)
$tier_entry = 0;
$tier_mid = 0;
$tier_luxury = 0;
$highest_price_item = null;
$lowest_price_item = null;

foreach ($products as $p) {
    $pr = (float)$p['price'];
    if ($pr < 5000) $tier_entry++;
    elseif ($pr <= 20000) $tier_mid++;
    else $tier_luxury++;

    if ($highest_price_item === null || $pr > (float)$highest_price_item['price']) {
        $highest_price_item = $p;
    }
    if ($lowest_price_item === null || $pr < (float)$lowest_price_item['price']) {
        $lowest_price_item = $p;
    }
}

// 4. Top Inquired Products Leaderboard
$top_inquiries_map = [];
foreach ($inquiries as $inq) {
    $p_name = trim($inq['product_title']);
    if (empty($p_name) || strtolower($p_name) === 'general contact') continue;
    if (!isset($top_inquiries_map[$p_name])) {
        $top_inquiries_map[$p_name] = 0;
    }
    $top_inquiries_map[$p_name]++;
}
arsort($top_inquiries_map);
$top_inquiries_list = array_slice($top_inquiries_map, 0, 5, true);

// 5. Catalog Health Score (% of products with gallery photos, dimensions, and brand)
$complete_count = 0;
foreach ($products as $p) {
    $has_gallery = !empty($p['gallery']) && $p['gallery'] !== '[]';
    $has_brand = !empty($p['brand_id']);
    $has_dims = !empty($p['height_cm']) || !empty($p['width_cm']);
    if ($has_gallery && $has_brand && $has_dims) {
        $complete_count++;
    }
}
$catalog_health_score = $total_products > 0 ? round(($complete_count / $total_products) * 100) : 100;

if (!$db) {
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

    <!-- Sidebar Backdrop for Mobile -->
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    <div class="admin-container">
        <!-- Sidebar Navigation -->
        <aside class="admin-sidebar">
            <div class="sidebar-header">
                <a href="index.php" style="display: flex; align-items: center; gap: 10px; text-decoration: none;">
                    <img src="../assets/images/logo.png" alt="OXO Premium Furniture" class="admin-logo-img">
                    <h1 class="admin-logo-text">OXO <span>Studio</span></h1>
                </a>
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
        
        <!-- Mobile Navigation Header Bar -->
        <div class="admin-mobile-header">
            <div class="mobile-logo-brand">
                <img src="../assets/images/logo.png" alt="OXO Logo" class="admin-logo-img">
                <span class="admin-logo-text">OXO <span>Studio</span></span>
            </div>
            <button type="button" class="mobile-hamburger-btn" id="mobileNavToggle" aria-label="Open Navigation Menu">
                <i class="fa-solid fa-bars"></i>
            </button>
        </div>
        
        <!-- Tab Content Header -->
        <div class="page-header">
            <div>
                <?php if ($current_tab === 'products'): ?>
                    <h2 class="page-title">Curated <span>Creations</span></h2>
                    <p style="color: var(--color-gray); font-size: 0.95rem; margin-top: 5px;">Add, modify, and manage items in the luxury furniture catalog.</p>
                <?php elseif ($current_tab === 'analytics'): ?>
                    <h2 class="page-title">Analytics & <span>Inquiries</span></h2>
                    <p style="color: var(--color-gray); font-size: 0.95rem; margin-top: 5px;">Analyze sales metrics, view trends, and answer client consultation inquiries.</p>
                <?php elseif ($current_tab === 'add_product'): ?>
                    <h2 class="page-title">Add New <span>Creation</span></h2>
                    <p style="color: var(--color-gray); font-size: 0.95rem; margin-top: 5px;">Register a new luxury furniture design to be displayed in the catalog.</p>
                <?php else: ?>
                    <?php if ($current_section === 'collections'): ?>
                        <h2 class="page-title">Collections & <span>Brands</span></h2>
                        <p style="color: var(--color-gray); font-size: 0.95rem; margin-top: 5px;">Manage furniture categories, material types, color palettes and partner logos.</p>
                    <?php elseif ($current_section === 'announcement'): ?>
                        <h2 class="page-title">Announcement <span>Poster</span></h2>
                        <p style="color: var(--color-gray); font-size: 0.95rem; margin-top: 5px;">Post pop-up announcement banners that display automatically when the store loads.</p>
                    <?php elseif ($current_section === 'security'): ?>
                        <h2 class="page-title">Security & <span>Credentials</span></h2>
                        <p style="color: var(--color-gray); font-size: 0.95rem; margin-top: 5px;">Update administrator account credentials and master login password.</p>
                    <?php elseif ($current_section === 'whatsapp'): ?>
                        <h2 class="page-title">WhatsApp <span>Configuration</span></h2>
                        <p style="color: var(--color-gray); font-size: 0.95rem; margin-top: 5px;">Configure administrator contact phone number for automated client response chats.</p>
                    <?php else: ?>
                        <h2 class="page-title">Studio <span>Settings</span></h2>
                        <p style="color: var(--color-gray); font-size: 0.95rem; margin-top: 5px;">Configure application identity, database backup tools, importers, and store controls.</p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            
            <?php if ($current_tab === 'products' && $db): ?>
                <div style="display: flex; gap: 10px;">
                    <a href="product-editor.php" class="action-btn">
                        <i class="fa-solid fa-plus"></i> Add New Creation
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- System Alerts -->
        <?php 
        $message = '';
        $message_type = '';
        if (isset($_GET['backup']) && $_GET['backup'] === 'success') {
            $message = "Database backup created successfully! Downloaded oxo_db.sql";
            $message_type = 'success';
        } elseif (isset($_GET['import'])) {
            if ($_GET['import'] === 'success') {
                $message = "Database restored successfully from uploaded SQL backup file!";
                $message_type = 'success';
            } else {
                $message = "Database import failed: " . htmlspecialchars($_GET['message'] ?? 'Unknown error');
                $message_type = 'error';
            }
        } elseif (isset($_GET['sync'])) {
            $message = htmlspecialchars($_GET['message'] ?? 'Price sync completed.');
            $message_type = $_GET['sync'] === 'error' ? 'error' : 'success';
        }
        ?>
        <?php if (!empty($message)): ?>
            <div class="alert <?php echo $message_type === 'success' ? 'alert-success' : 'alert-danger'; ?>" style="margin-bottom: 30px; <?php echo $message_type === 'error' ? 'background: rgba(231, 76, 60, 0.15); color: #e74c3c; border: 1px solid #e74c3c; padding: 12px 18px; border-radius: 8px;' : ''; ?>">
                <i class="fa-solid <?php echo $message_type === 'success' ? 'fa-circle-check' : 'fa-triangle-exclamation'; ?>" style="margin-right: 8px;"></i>
                <?php echo $message; ?>
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
                        <div class="stat-value"><?php echo $total_products; ?> <span style="font-size: 0.72rem; color: var(--color-gray); font-weight: 600;">Items</span></div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon"><i class="fa-solid fa-tags"></i></div>
                    <div class="stat-info">
                        <h3>Active Categories</h3>
                        <div class="stat-value"><?php echo $categories_count; ?> <span style="font-size: 0.72rem; color: var(--color-gray); font-weight: 600;">Registered</span></div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon"><i class="fa-solid fa-vault"></i></div>
                    <div class="stat-info">
                        <h3>Total Catalog Value</h3>
                        <div class="stat-value"><?php echo format_inr_admin($total_catalog_value ?? 0); ?></div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon"><i class="fa-solid fa-scale-balanced"></i></div>
                    <div class="stat-info">
                        <h3>Average Item Price</h3>
                        <div class="stat-value"><?php echo format_inr_admin($average_price ?? 0); ?></div>
                    </div>
                </div>
            </section>

            <!-- Catalog Search & Filter Controls -->
            <div class="catalog-filter-bar" style="background: var(--color-panel-dark); padding: 16px 20px; border-radius: 12px; margin-bottom: 20px; border: 1px solid var(--color-panel-border); display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 15px;">
                <form action="index.php" method="GET" style="display: flex; flex-wrap: wrap; align-items: center; gap: 12px; flex: 1;">
                    <input type="hidden" name="tab" value="products">
                    
                    <!-- Search Input -->
                    <div style="position: relative; flex: 1; min-width: 220px;">
                        <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--color-gray); font-size: 0.85rem;"></i>
                        <input type="text" name="filter_search" placeholder="Search creations by title or ID..." value="<?php echo htmlspecialchars($filter_search); ?>" class="input-control" style="padding-left: 35px; padding-top: 8px; padding-bottom: 8px; font-size: 0.85rem;">
                    </div>

                    <!-- Filter Category -->
                    <select name="filter_category" class="input-control" style="width: auto; min-width: 150px; padding-top: 8px; padding-bottom: 8px; font-size: 0.85rem;" onchange="this.form.submit()">
                        <option value="">All Categories</option>
                        <?php foreach ($categories_list as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat['slug']); ?>" <?php echo strtolower($filter_category) === strtolower($cat['slug']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <!-- Filter Material -->
                    <select name="filter_material" class="input-control" style="width: auto; min-width: 140px; padding-top: 8px; padding-bottom: 8px; font-size: 0.85rem;" onchange="this.form.submit()">
                        <option value="">All Materials</option>
                        <?php foreach ($materials_list as $mat): ?>
                            <option value="<?php echo htmlspecialchars($mat['slug']); ?>" <?php echo strtolower($filter_material) === strtolower($mat['slug']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($mat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <!-- Filter Brand -->
                    <select name="filter_brand" class="input-control" style="width: auto; min-width: 140px; padding-top: 8px; padding-bottom: 8px; font-size: 0.85rem;" onchange="this.form.submit()">
                        <option value="">All Brands</option>
                        <?php foreach ($brands as $brd): ?>
                            <option value="<?php echo htmlspecialchars($brd['id']); ?>" <?php echo (string)$filter_brand === (string)$brd['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($brd['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <button type="submit" class="action-btn" style="padding: 8px 16px; font-size: 0.8rem;">
                        <i class="fa-solid fa-filter"></i> Apply
                    </button>

                    <?php if ($has_active_filter): ?>
                        <a href="index.php?tab=products" class="action-btn secondary" style="padding: 8px 14px; font-size: 0.8rem;" title="Clear all filters">
                            <i class="fa-solid fa-xmark"></i> Clear Filters
                        </a>
                    <?php endif; ?>
                </form>

                <!-- View All toggle button -->
                <div>
                    <?php if (!$has_active_filter): ?>
                        <a href="index.php?tab=products&show_all=1" class="action-btn secondary" style="padding: 8px 16px; font-size: 0.8rem; border-color: var(--color-accent); color: var(--color-accent);">
                            <i class="fa-solid fa-eye"></i> View All (<?php echo $total_products; ?>)
                        </a>
                    <?php else: ?>
                        <span style="font-size: 0.85rem; font-weight: 700; color: var(--color-accent);">
                            Showing <?php echo count($displayed_products); ?> result(s)
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!$has_active_filter): ?>
                <div style="background: rgba(217, 119, 6, 0.08); border: 1px solid rgba(217, 119, 6, 0.25); color: var(--color-accent); padding: 10px 16px; border-radius: 8px; margin-bottom: 15px; font-size: 0.85rem; display: flex; align-items: center; justify-content: space-between;">
                    <span><i class="fa-solid fa-clock-rotate-left" style="margin-right: 6px;"></i> Showing <strong>Last 5 Recently Added Creations</strong> out of <?php echo $total_products; ?> catalog items. Select a category or material above to view more.</span>
                    <a href="index.php?tab=products&show_all=1" style="font-weight: 700; text-decoration: underline; color: var(--color-accent);">View All Creations &rarr;</a>
                </div>
            <?php endif; ?>

            <!-- Bulk Delete Action Form Wrapper -->
            <form id="bulkDeleteForm" action="index.php?tab=products" method="POST">
                <input type="hidden" name="form_action" value="bulk_delete_products">

                <div style="display: flex; align-items: center; justify-content: space-between; background: var(--color-gray-dark); padding: 12px 20px; border-radius: 8px; margin-bottom: 15px; border: 1px solid var(--color-panel-border);">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <input type="checkbox" id="selectAllProducts" onclick="toggleSelectAllProducts(this)" style="width: 18px; height: 18px; cursor: pointer; accent-color: #e74c3c;">
                        <label for="selectAllProducts" style="font-weight: 600; cursor: pointer; color: var(--color-primary); font-size: 0.9rem;">Select All Displayed</label>
                        <span id="selectedCountBadge" style="background: rgba(231, 76, 60, 0.15); color: #e74c3c; padding: 2px 10px; border-radius: 12px; font-size: 0.8rem; font-weight: 700;">0 selected</span>
                    </div>
                    <div>
                        <button type="submit" id="bulkDeleteBtn" class="action-btn" style="background: rgba(231, 76, 60, 0.15); color: #e74c3c; border: 1px solid #e74c3c; display: none; padding: 8px 16px; font-weight: 700; cursor: pointer;" onclick="return confirmBulkDelete();">
                            <i class="fa-solid fa-trash-can" style="margin-right: 6px;"></i> Delete Selected Creations (<span id="btnSelectedCount">0</span>)
                        </button>
                    </div>
                </div>

                <!-- Table -->
                <div class="table-card">
                    <div class="table-responsive">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th style="width: 40px; text-align: center;">
                                        <input type="checkbox" id="headerSelectAll" onclick="toggleSelectAllProducts(this)" style="width: 16px; height: 16px; cursor: pointer; accent-color: #e74c3c;" title="Select / Deselect All">
                                    </th>
                                    <th style="width: 80px;">Design</th>
                                    <th style="width: 150px;">Product ID</th>
                                    <th>Title</th>
                                    <th>Category</th>
                                    <th>Price</th>
                                    <th style="width: 120px; text-align: right;">Operations</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($displayed_products)): ?>
                                    <tr>
                                        <td colspan="7" style="text-align: center; padding: 40px; color: var(--color-gray);">
                                            No creations found matching the selected filter criteria. <a href="index.php?tab=products" style="color: var(--color-accent); font-weight: 700;">Clear Filters</a>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($displayed_products as $p): ?>
                                        <tr>
                                            <td style="text-align: center;">
                                                <input type="checkbox" name="selected_product_ids[]" value="<?php echo htmlspecialchars($p['id']); ?>" class="product-select-chk" onchange="updateBulkDeleteState()" style="width: 16px; height: 16px; cursor: pointer; accent-color: #e74c3c;">
                                            </td>
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
            </form>
        </div>

        <!-- TAB B: ANALYTICS & INQUIRIES TAB -->
        <div class="tab-container <?php echo $current_tab === 'analytics' ? 'active' : ''; ?>">
            <?php
            // Group inquiries by status for CRM Kanban Board
            $cols = [
                'Pending' => [],
                'Contacted' => [],
                'Quoted' => [],
                'Addressed' => []
            ];
            if (!empty($inquiries)) {
                foreach ($inquiries as $inq) {
                    $status = $inq['status'];
                    if (!isset($cols[$status])) {
                        $status = 'Pending';
                    }
                    $cols[$status][] = $inq;
                }
            }
            ?>
            
            <!-- Analytics Cards -->
            <section class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fa-solid fa-vault"></i></div>
                    <div class="stat-info">
                        <h3>Total Catalog Value</h3>
                        <div class="stat-value"><?php echo format_inr_admin($total_catalog_value ?? 0); ?></div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon"><i class="fa-solid fa-inbox"></i></div>
                    <div class="stat-info">
                        <h3>Consultations</h3>
                        <div class="stat-value"><?php echo $total_inquiries; ?> <span style="font-size: 0.72rem; font-weight: 600; color: var(--color-gray);">Total</span></div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon"><i class="fa-solid fa-circle-check"></i></div>
                    <div class="stat-info">
                        <h3>Response Rate</h3>
                        <div class="stat-value" style="color: var(--color-success);"><?php echo $inquiry_response_rate; ?>%</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon"><i class="fa-solid fa-heart-pulse"></i></div>
                    <div class="stat-info">
                        <h3>Catalog Health Score</h3>
                        <div class="stat-value" style="color: <?php echo $catalog_health_score >= 80 ? 'var(--color-success)' : 'var(--color-accent)'; ?>;"><?php echo $catalog_health_score; ?>% <span style="font-size: 0.72rem; font-weight: 600; color: var(--color-gray);">Complete</span></div>
                    </div>
                </div>
            </section>

            <!-- Real Chart.js Graphics Grid -->
            <div class="charts-grid">
                <!-- Category Doughnut Chart Card -->
                <div class="chart-box">
                    <h3 class="chart-title">Collection Distribution <span style="font-size: 0.75rem; text-transform: none; color: var(--color-gray);">(Active Categories)</span></h3>
                    <div style="position: relative; height: 280px; width: 100%;">
                        <canvas id="categoryChart"></canvas>
                    </div>
                </div>
                
                <!-- Material Bar Chart Card -->
                <div class="chart-box">
                    <h3 class="chart-title">Material Portfolio Breakdown <span style="font-size: 0.75rem; text-transform: none; color: var(--color-gray);">(Items per Material)</span></h3>
                    <div style="position: relative; height: 280px; width: 100%;">
                        <canvas id="materialChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Insights & Leaderboard Grid -->
            <div class="charts-grid" style="margin-bottom: 40px;">
                <!-- Top Inquired Products Leaderboard -->
                <div class="chart-box">
                    <h3 class="chart-title"><i class="fa-solid fa-fire" style="color: #e74c3c; margin-right: 8px;"></i> Most Inquired Products <span style="font-size: 0.75rem; text-transform: none; color: var(--color-gray);">(Client Demand)</span></h3>
                    <?php if (empty($top_inquiries_list)): ?>
                        <p style="color: var(--color-gray); font-size: 0.9rem; font-style: italic; padding: 20px 0;">No client product inquiries recorded yet.</p>
                    <?php else: ?>
                        <div style="display: flex; flex-direction: column; gap: 12px; margin-top: 15px;">
                            <?php 
                            $rank = 1;
                            foreach ($top_inquiries_list as $prod_title => $inq_count): 
                            ?>
                                <div style="display: flex; align-items: center; justify-content: space-between; background: var(--color-gray-dark); padding: 12px 16px; border-radius: 8px; border: 1px solid var(--color-panel-border);">
                                    <div style="display: flex; align-items: center; gap: 12px;">
                                        <span style="width: 26px; height: 26px; border-radius: 50%; background: var(--color-accent); color: var(--color-white); font-weight: 800; font-size: 0.75rem; display: flex; align-items: center; justify-content: center; font-family: var(--font-numeric);">
                                            #<?php echo $rank++; ?>
                                        </span>
                                        <strong style="color: var(--color-primary); font-size: 0.9rem;"><?php echo htmlspecialchars($prod_title); ?></strong>
                                    </div>
                                    <span class="badge pending" style="background: rgba(82, 122, 99, 0.15); color: var(--color-primary); border-color: var(--color-panel-border);">
                                        <?php echo $inq_count; ?> <?php echo $inq_count === 1 ? 'inquiry' : 'inquiries'; ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Price Tiers & Valuation Card -->
                <div class="chart-box">
                    <h3 class="chart-title"><i class="fa-solid fa-coins" style="color: var(--color-accent); margin-right: 8px;"></i> Inventory Price Tiers</h3>
                    <div style="display: flex; flex-direction: column; gap: 15px; margin-top: 15px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 14px; background: var(--color-gray-dark); border-radius: 8px;">
                            <span style="font-size: 0.85rem; color: var(--color-gray); font-weight: 600;">Entry Level (&lt; ₹5,000)</span>
                            <strong style="font-size: 1.1rem; color: var(--color-primary); font-family: var(--font-numeric);"><?php echo $tier_entry; ?> items</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 14px; background: var(--color-gray-dark); border-radius: 8px;">
                            <span style="font-size: 0.85rem; color: var(--color-gray); font-weight: 600;">Mid Range (₹5,000 – ₹20,000)</span>
                            <strong style="font-size: 1.1rem; color: var(--color-accent); font-family: var(--font-numeric);"><?php echo $tier_mid; ?> items</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 14px; background: var(--color-gray-dark); border-radius: 8px;">
                            <span style="font-size: 0.85rem; color: var(--color-gray); font-weight: 600;">Luxury &amp; Bespoke (&gt; ₹20,000)</span>
                            <strong style="font-size: 1.1rem; color: var(--color-success); font-family: var(--font-numeric);"><?php echo $tier_luxury; ?> items</strong>
                        </div>

                        <?php if ($highest_price_item): ?>
                            <div style="margin-top: 5px; padding: 10px 14px; background: rgba(82, 122, 99, 0.06); border-radius: 8px; border: 1px solid var(--color-panel-border);">
                                <span style="font-size: 0.75rem; text-transform: uppercase; color: var(--color-gray); letter-spacing: 0.05em; font-weight: 700; display: block; margin-bottom: 2px;">Highest Valued Creation</span>
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <strong style="font-size: 0.85rem; color: var(--color-primary);"><?php echo htmlspecialchars($highest_price_item['title']); ?></strong>
                                    <span style="font-weight: 800; color: var(--color-accent); font-family: var(--font-numeric); font-size: 0.95rem;"><?php echo format_inr_admin($highest_price_item['price']); ?></span>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Client Inquiries List -->
            <div class="page-header" style="margin-bottom: 25px;">
                <div>
                    <h3 class="page-title" style="font-size: 1.4rem;">Bespoke Client <span>Inquiries</span></h3>
                    <p style="color: var(--color-gray); font-size: 0.85rem; margin-top: 5px;">Client consultation requests submitted from product details page.</p>
                </div>
            </div>

            <!-- Premium CRM Kanban Board -->
            <style>
                .kanban-board {
                    display: grid;
                    grid-template-columns: repeat(4, 1fr);
                    gap: 20px;
                    margin-top: 15px;
                    width: 100%;
                }
                .kanban-column {
                    background: rgba(10, 46, 36, 0.015);
                    border: 1px solid rgba(10, 46, 36, 0.05);
                    border-radius: 12px;
                    padding: 15px;
                    display: flex;
                    flex-direction: column;
                    gap: 15px;
                    min-height: 520px;
                    transition: background-color 0.3s, border-color 0.3s;
                }
                .kanban-column.drag-over {
                    background: rgba(200, 162, 118, 0.04);
                    border-color: var(--color-accent);
                }
                .kanban-column-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    border-bottom: 2px solid rgba(10, 46, 36, 0.05);
                    padding-bottom: 12px;
                    margin-bottom: 5px;
                }
                .kanban-column-title {
                    font-family: var(--font-title);
                    font-size: 0.8rem;
                    font-weight: 800;
                    text-transform: uppercase;
                    letter-spacing: 1px;
                    color: var(--color-primary);
                }
                .kanban-column-count {
                    font-family: var(--font-numeric);
                    font-size: 0.7rem;
                    font-weight: 700;
                    background: var(--color-primary);
                    color: var(--color-white);
                    padding: 2px 8px;
                    border-radius: 10px;
                }
                .kanban-cards-container {
                    flex-grow: 1;
                    display: flex;
                    flex-direction: column;
                    gap: 12px;
                    min-height: 420px;
                }
                .kanban-card {
                    background: #ffffff;
                    border: 1px solid rgba(10, 46, 36, 0.05);
                    border-radius: 8px;
                    padding: 16px;
                    box-shadow: 0 4px 10px rgba(10, 46, 36, 0.02);
                    cursor: grab;
                    transition: all 0.3s cubic-bezier(0.25, 1, 0.5, 1);
                    user-select: none;
                    position: relative;
                }
                .kanban-card:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 8px 18px rgba(10, 46, 36, 0.04);
                    border-color: rgba(200, 162, 118, 0.3);
                }
                .kanban-card:active {
                    cursor: grabbing;
                }
                .kanban-card.dragging {
                    opacity: 0.4;
                    transform: scale(0.98);
                }
                .kanban-card-client {
                    font-size: 0.82rem;
                    font-weight: 700;
                    color: var(--color-primary);
                    margin-bottom: 6px;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                }
                .kanban-card-date {
                    font-family: var(--font-numeric);
                    font-size: 0.68rem;
                    color: var(--color-gray);
                }
                .kanban-card-product {
                    font-family: var(--font-title);
                    font-size: 0.72rem;
                    font-weight: 700;
                    color: var(--color-accent);
                    margin-bottom: 8px;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                }
                .kanban-card-message {
                    font-size: 0.78rem;
                    line-height: 1.45;
                    color: #4A564E;
                    margin-bottom: 12px;
                    display: -webkit-box;
                    -webkit-line-clamp: 3;
                    -webkit-box-orient: vertical;
                    overflow: hidden;
                    text-overflow: ellipsis;
                    max-height: 52px;
                }
                .kanban-card-footer {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    border-top: 1px solid rgba(10, 46, 36, 0.05);
                    padding-top: 10px;
                    margin-top: 5px;
                }
                .kanban-card-links {
                    display: flex;
                    gap: 12px;
                }
                .kanban-card-link {
                    font-size: 0.8rem;
                    color: var(--color-gray);
                    transition: color 0.2s;
                }
                .kanban-card-link.email:hover {
                    color: var(--color-accent-green);
                }
                .kanban-card-link.whatsapp:hover {
                    color: #25D366;
                }
                .kanban-reply-btn {
                    padding: 6px 12px;
                    border-radius: 4px;
                    background: rgba(10, 46, 36, 0.03);
                    color: var(--color-primary);
                    font-size: 0.68rem;
                    font-weight: 700;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                    cursor: pointer;
                    display: inline-flex;
                    align-items: center;
                    gap: 6px;
                    transition: all 0.2s;
                    border: 1px solid rgba(10, 46, 36, 0.08);
                }
                .kanban-reply-btn:hover {
                    background: var(--color-primary);
                    color: var(--color-white);
                    border-color: var(--color-primary);
                }
                .kanban-reply-btn img {
                    width: 12px;
                    height: 12px;
                    object-fit: contain;
                    filter: brightness(0.2);
                    transition: filter 0.2s;
                }
                .kanban-reply-btn:hover img {
                    filter: brightness(1) invert(1);
                }
                @media screen and (max-width: 1024px) {
                    .kanban-board {
                        grid-template-columns: repeat(2, 1fr);
                    }
                }
                @media screen and (max-width: 640px) {
                    .kanban-board {
                        grid-template-columns: 1fr;
                    }
                }
            </style>

            <div class="kanban-board">
                <?php 
                $col_configs = [
                    'Pending' => ['title' => 'New Leads', 'class' => 'pending'],
                    'Contacted' => ['title' => 'Contacted', 'class' => 'contacted'],
                    'Quoted' => ['title' => 'Quoted', 'class' => 'quoted'],
                    'Addressed' => ['title' => 'Closed / Won', 'class' => 'addressed']
                ];
                foreach ($col_configs as $status_key => $config):
                    $col_inquiries = isset($cols[$status_key]) ? $cols[$status_key] : [];
                ?>
                    <div class="kanban-column" data-status="<?php echo $status_key; ?>">
                        <div class="kanban-column-header">
                            <h4 class="kanban-column-title"><?php echo htmlspecialchars($config['title']); ?></h4>
                            <span class="kanban-column-count"><?php echo count($col_inquiries); ?></span>
                        </div>
                        
                        <div class="kanban-cards-container" data-status="<?php echo $status_key; ?>">
                            <?php foreach ($col_inquiries as $inq): ?>
                                <div class="kanban-card" draggable="true" data-id="<?php echo $inq['id']; ?>">
                                    <div class="kanban-card-client">
                                        <strong><?php echo htmlspecialchars($inq['name']); ?></strong>
                                        <span class="kanban-card-date"><?php echo date('M d', strtotime($inq['created_at'])); ?></span>
                                    </div>
                                    <div class="kanban-card-product"><?php echo htmlspecialchars($inq['product_title']); ?></div>
                                    <div class="kanban-card-message" title="<?php echo htmlspecialchars($inq['message']); ?>"><?php echo htmlspecialchars($inq['message']); ?></div>
                                    
                                    <div class="kanban-card-footer">
                                        <div class="kanban-card-links">
                                            <a href="mailto:<?php echo htmlspecialchars($inq['email']); ?>" class="kanban-card-link email" title="Send Email">
                                                <i class="fa-regular fa-envelope"></i>
                                            </a>
                                            <?php if (!empty($inq['whatsapp'])): 
                                                $clean_inq_wa = preg_replace('/[^0-9]/', '', $inq['whatsapp']);
                                                if (strlen($clean_inq_wa) === 10) {
                                                    $clean_inq_wa = '91' . $clean_inq_wa;
                                                }
                                            ?>
                                                <a href="https://wa.me/<?php echo $clean_inq_wa; ?>" target="_blank" class="kanban-card-link whatsapp" title="Chat on WhatsApp">
                                                    <i class="fa-brands fa-whatsapp"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <?php if (!empty($inq['whatsapp'])): ?>
                                            <button onclick="openReplyModal(event, <?php echo $inq['id']; ?>, '<?php echo addslashes($inq['name']); ?>', '<?php echo addslashes($inq['whatsapp']); ?>', '<?php echo addslashes($inq['product_title']); ?>', '<?php echo addslashes(str_replace(array("\r", "\n"), ' ', $inq['message'])); ?>')"
                                                    class="kanban-reply-btn" 
                                                    title="Send WhatsApp Response Dialog">
                                                <img src="../assets/images/logo.png" alt="Logo"> Reply
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- TAB C: SETTINGS TAB -->
        <div class="tab-container <?php echo $current_tab === 'settings' ? 'active' : ''; ?>">
            
            <?php if ($current_section !== 'overview'): ?>
                <div style="margin-bottom: 25px;">
                    <a href="index.php?tab=settings" class="action-btn" style="display: inline-flex; align-items: center; gap: 8px; background: #ffffff; color: var(--color-primary); border: 1px solid var(--color-panel-border); text-decoration: none; padding: 10px 18px; border-radius: 10px; font-weight: 600; font-size: 0.88rem; box-shadow: 0 2px 5px rgba(0,0,0,0.03);">
                        <i class="fa-solid fa-arrow-left"></i> Back to Settings Overview
                    </a>
                </div>
            <?php endif; ?>

            <?php if ($current_section === 'overview'): ?>
                <!-- Settings Overview Grid Cards (Matching Photo 2 Enteangadi Style) -->
                <div class="settings-overview-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(210px, 1fr)); gap: 20px; width: 100%; margin-bottom: 35px; box-sizing: border-box;">
                    <!-- 1. Sync Live Prices -->
                    <a href="sync-prices.php?action=sync_all" class="settings-card-item" style="display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; background: #ffffff; border: 1px solid rgba(10, 46, 36, 0.09); border-radius: 20px; padding: 24px 16px; text-decoration: none; color: inherit; box-shadow: 0 4px 15px rgba(0,0,0,0.02); min-height: 165px; box-sizing: border-box;" title="Re-scrape source URLs and update product prices automatically">
                        <div class="settings-icon-wrapper" style="width: 54px; height: 54px; border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; margin: 0 auto 16px auto; flex-shrink: 0; background: rgba(155, 89, 182, 0.12); color: #9b59b6;">
                            <i class="fa-solid fa-rotate"></i>
                        </div>
                        <div class="settings-card-title" style="font-size: 0.98rem; font-weight: 700; color: #0a2e24; margin: 0 0 6px 0; line-height: 1.3;">Sync Live Prices</div>
                        <p class="settings-card-desc" style="font-size: 0.78rem; color: #64748b; line-height: 1.4; margin: 0; font-weight: 400;">Re-scrape source URLs and update product prices automatically.</p>
                    </a>

                    <!-- 2. Backup Database -->
                    <a href="export-db.php" class="settings-card-item" style="display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; background: #ffffff; border: 1px solid rgba(10, 46, 36, 0.09); border-radius: 20px; padding: 24px 16px; text-decoration: none; color: inherit; box-shadow: 0 4px 15px rgba(0,0,0,0.02); min-height: 165px; box-sizing: border-box;" title="Download current database backup SQL file">
                        <div class="settings-icon-wrapper" style="width: 54px; height: 54px; border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; margin: 0 auto 16px auto; flex-shrink: 0; background: rgba(46, 204, 113, 0.12); color: #2ecc71;">
                            <i class="fa-solid fa-download"></i>
                        </div>
                        <div class="settings-card-title" style="font-size: 0.98rem; font-weight: 700; color: #0a2e24; margin: 0 0 6px 0; line-height: 1.3;">Backup Database</div>
                        <p class="settings-card-desc" style="font-size: 0.78rem; color: #64748b; line-height: 1.4; margin: 0; font-weight: 400;">Download full SQL database backup file for catalog & system state.</p>
                    </a>

                    <!-- 3. Import Database -->
                    <div onclick="document.getElementById('importDbModal').style.display='flex'" class="settings-card-item" style="display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; background: #ffffff; border: 1px solid rgba(10, 46, 36, 0.09); border-radius: 20px; padding: 24px 16px; text-decoration: none; color: inherit; box-shadow: 0 4px 15px rgba(0,0,0,0.02); min-height: 165px; box-sizing: border-box; cursor: pointer;" title="Upload and restore SQL database backup">
                        <div class="settings-icon-wrapper" style="width: 54px; height: 54px; border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; margin: 0 auto 16px auto; flex-shrink: 0; background: rgba(52, 152, 219, 0.12); color: #3498db;">
                            <i class="fa-solid fa-file-import"></i>
                        </div>
                        <div class="settings-card-title" style="font-size: 0.98rem; font-weight: 700; color: #0a2e24; margin: 0 0 6px 0; line-height: 1.3;">Import Database</div>
                        <p class="settings-card-desc" style="font-size: 0.78rem; color: #64748b; line-height: 1.4; margin: 0; font-weight: 400;">Upload and restore database tables from a saved .SQL backup file.</p>
                    </div>

                    <!-- 4. Universal Importer -->
                    <a href="import-universal.php" class="settings-card-item" style="display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; background: #ffffff; border: 1px solid rgba(10, 46, 36, 0.09); border-radius: 20px; padding: 24px 16px; text-decoration: none; color: inherit; box-shadow: 0 4px 15px rgba(0,0,0,0.02); min-height: 165px; box-sizing: border-box;" title="Universal Product Importer from web links">
                        <div class="settings-icon-wrapper" style="width: 54px; height: 54px; border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; margin: 0 auto 16px auto; flex-shrink: 0; background: rgba(99, 102, 241, 0.12); color: #6366f1;">
                            <i class="fa-solid fa-cloud-arrow-down"></i>
                        </div>
                        <div class="settings-card-title" style="font-size: 0.98rem; font-weight: 700; color: #0a2e24; margin: 0 0 6px 0; line-height: 1.3;">Universal Importer</div>
                        <p class="settings-card-desc" style="font-size: 0.78rem; color: #64748b; line-height: 1.4; margin: 0; font-weight: 400;">Scrape, parse and import luxury furniture directly from external web links.</p>
                    </a>

                    <!-- 5. Collections & Brands -->
                    <a href="index.php?tab=settings&section=collections" class="settings-card-item" style="display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; background: #ffffff; border: 1px solid rgba(10, 46, 36, 0.09); border-radius: 20px; padding: 24px 16px; text-decoration: none; color: inherit; box-shadow: 0 4px 15px rgba(0,0,0,0.02); min-height: 165px; box-sizing: border-box;" title="Manage Categories, Materials, Colors and Brand Logos">
                        <div class="settings-icon-wrapper" style="width: 54px; height: 54px; border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; margin: 0 auto 16px auto; flex-shrink: 0; background: rgba(245, 158, 11, 0.12); color: #f59e0b;">
                            <i class="fa-solid fa-shapes"></i>
                        </div>
                        <div class="settings-card-title" style="font-size: 0.98rem; font-weight: 700; color: #0a2e24; margin: 0 0 6px 0; line-height: 1.3;">Collections & Brands</div>
                        <p class="settings-card-desc" style="font-size: 0.78rem; color: #64748b; line-height: 1.4; margin: 0; font-weight: 400;">Manage furniture categories, material types, color palettes and partner logos.</p>
                    </a>

                    <!-- 6. Announcement Poster -->
                    <a href="index.php?tab=settings&section=announcement" class="settings-card-item" style="display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; background: #ffffff; border: 1px solid rgba(10, 46, 36, 0.09); border-radius: 20px; padding: 24px 16px; text-decoration: none; color: inherit; box-shadow: 0 4px 15px rgba(0,0,0,0.02); min-height: 165px; box-sizing: border-box;" title="Manage homepage popup announcements">
                        <div class="settings-icon-wrapper" style="width: 54px; height: 54px; border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; margin: 0 auto 16px auto; flex-shrink: 0; background: rgba(234, 88, 12, 0.12); color: #ea580c;">
                            <i class="fa-solid fa-bullhorn"></i>
                        </div>
                        <div class="settings-card-title" style="font-size: 0.98rem; font-weight: 700; color: #0a2e24; margin: 0 0 6px 0; line-height: 1.3;">Announcement Poster</div>
                        <p class="settings-card-desc" style="font-size: 0.78rem; color: #64748b; line-height: 1.4; margin: 0; font-weight: 400;">Post pop-up announcement banners displaying automatically on store load.</p>
                    </a>

                    <!-- 7. System Documentation -->
                    <div onclick="document.getElementById('docsModal').style.display='flex'" class="settings-card-item" style="display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; background: #ffffff; border: 1px solid rgba(10, 46, 36, 0.09); border-radius: 20px; padding: 24px 16px; text-decoration: none; color: inherit; box-shadow: 0 4px 15px rgba(0,0,0,0.02); min-height: 165px; box-sizing: border-box; cursor: pointer;" title="Generate PDF developer specs or user guide">
                        <div class="settings-icon-wrapper" style="width: 54px; height: 54px; border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; margin: 0 auto 16px auto; flex-shrink: 0; background: rgba(236, 72, 153, 0.12); color: #ec4899;">
                            <i class="fa-solid fa-file-pdf"></i>
                        </div>
                        <div class="settings-card-title" style="font-size: 0.98rem; font-weight: 700; color: #0a2e24; margin: 0 0 6px 0; line-height: 1.3;">System Documentation</div>
                        <p class="settings-card-desc" style="font-size: 0.78rem; color: #64748b; line-height: 1.4; margin: 0; font-weight: 400;">Generate PDF developer specs, data flow diagrams & admin user guide.</p>
                    </div>

                    <!-- 8. Admin Security & Password -->
                    <a href="index.php?tab=settings&section=security" class="settings-card-item" style="display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; background: #ffffff; border: 1px solid rgba(10, 46, 36, 0.09); border-radius: 20px; padding: 24px 16px; text-decoration: none; color: inherit; box-shadow: 0 4px 15px rgba(0,0,0,0.02); min-height: 165px; box-sizing: border-box;" title="Change Admin Password and login credentials">
                        <div class="settings-icon-wrapper" style="width: 54px; height: 54px; border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; margin: 0 auto 16px auto; flex-shrink: 0; background: rgba(20, 184, 166, 0.12); color: #14b8a6;">
                            <i class="fa-solid fa-shield-halved"></i>
                        </div>
                        <div class="settings-card-title" style="font-size: 0.98rem; font-weight: 700; color: #0a2e24; margin: 0 0 6px 0; line-height: 1.3;">Security & Password</div>
                        <p class="settings-card-desc" style="font-size: 0.78rem; color: #64748b; line-height: 1.4; margin: 0; font-weight: 400;">Update administrator account credentials, security tokens & password.</p>
                    </a>

                    <!-- 9. WhatsApp Configuration -->
                    <a href="index.php?tab=settings&section=whatsapp" class="settings-card-item" style="display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; background: #ffffff; border: 1px solid rgba(10, 46, 36, 0.09); border-radius: 20px; padding: 24px 16px; text-decoration: none; color: inherit; box-shadow: 0 4px 15px rgba(0,0,0,0.02); min-height: 165px; box-sizing: border-box;" title="Configure admin WhatsApp contact number">
                        <div class="settings-icon-wrapper" style="width: 54px; height: 54px; border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; margin: 0 auto 16px auto; flex-shrink: 0; background: rgba(37, 211, 102, 0.12); color: #25D366;">
                            <i class="fa-brands fa-whatsapp"></i>
                        </div>
                        <div class="settings-card-title" style="font-size: 0.98rem; font-weight: 700; color: #0a2e24; margin: 0 0 6px 0; line-height: 1.3;">WhatsApp Config</div>
                        <p class="settings-card-desc" style="font-size: 0.78rem; color: #64748b; line-height: 1.4; margin: 0; font-weight: 400;">Set up admin phone number for direct client consultation WhatsApp responses.</p>
                    </a>

                    <!-- 10. Static Content & CMS -->
                    <a href="index.php?tab=settings&section=site_content" class="settings-card-item" style="display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; background: #ffffff; border: 1px solid rgba(10, 46, 36, 0.09); border-radius: 20px; padding: 24px 16px; text-decoration: none; color: inherit; box-shadow: 0 4px 15px rgba(0,0,0,0.02); min-height: 165px; box-sizing: border-box;" title="Manage and edit static site copy, hero video/images, about story, contact info & footer text">
                        <div class="settings-icon-wrapper" style="width: 54px; height: 54px; border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; margin: 0 auto 16px auto; flex-shrink: 0; background: rgba(217, 119, 6, 0.12); color: #d97706;">
                            <i class="fa-solid fa-pen-ruler"></i>
                        </div>
                        <div class="settings-card-title" style="font-size: 0.98rem; font-weight: 700; color: #0a2e24; margin: 0 0 6px 0; line-height: 1.3;">Static Content & CMS</div>
                        <p class="settings-card-desc" style="font-size: 0.78rem; color: #64748b; line-height: 1.4; margin: 0; font-weight: 400;">Manage and edit all static site copy, hero media, about story, contact details & footer text.</p>
                    </a>
                </div>

            <?php elseif ($current_section === 'site_content'): ?>
                <?php $sc = get_site_content(); ?>
                <div class="editor-card" style="max-width: 1000px; margin: 0 auto; background: #ffffff; padding: 35px; border-radius: 20px; border: 1px solid rgba(10, 46, 36, 0.09); box-shadow: 0 4px 20px rgba(0,0,0,0.03);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; border-bottom: 1px solid var(--color-panel-border); padding-bottom: 15px;">
                        <h3 class="editor-card-title" style="font-family: var(--font-title); font-size: 1.4rem; color: var(--color-primary); margin: 0; display: flex; align-items: center; gap: 10px;">
                            <i class="fa-solid fa-pen-ruler" style="color: #d97706;"></i> Dynamic Site Content & CMS Manager
                        </h3>
                        <span style="font-size: 0.78rem; background: rgba(217, 119, 6, 0.1); color: #d97706; padding: 4px 12px; border-radius: 20px; font-weight: 700;">Live Customizer</span>
                    </div>

                    <form action="index.php?tab=settings&section=site_content" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="form_action" value="update_site_content">

                        <!-- Sub-nav tabs for CMS sections -->
                        <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 30px; border-bottom: 1px solid var(--color-panel-border); padding-bottom: 12px;" id="cms-tab-nav">
                            <button type="button" class="cms-tab-btn active" data-tab="tab-hero" style="padding: 10px 18px; border-radius: 8px; border: 1px solid #d97706; background: #d97706; color: #ffffff; font-weight: 700; font-size: 0.82rem; cursor: pointer; transition: all 0.2s;"><i class="fa-solid fa-play"></i> Hero Banner</button>
                            <button type="button" class="cms-tab-btn" data-tab="tab-about-home" style="padding: 10px 18px; border-radius: 8px; border: 1px solid var(--color-panel-border); background: #ffffff; color: var(--color-primary); font-weight: 600; font-size: 0.82rem; cursor: pointer; transition: all 0.2s;"><i class="fa-solid fa-house"></i> Home About</button>
                            <button type="button" class="cms-tab-btn" data-tab="tab-about-page" style="padding: 10px 18px; border-radius: 8px; border: 1px solid var(--color-panel-border); background: #ffffff; color: var(--color-primary); font-weight: 600; font-size: 0.82rem; cursor: pointer; transition: all 0.2s;"><i class="fa-solid fa-book-open"></i> About Us Page</button>
                            <button type="button" class="cms-tab-btn" data-tab="tab-contact" style="padding: 10px 18px; border-radius: 8px; border: 1px solid var(--color-panel-border); background: #ffffff; color: var(--color-primary); font-weight: 600; font-size: 0.82rem; cursor: pointer; transition: all 0.2s;"><i class="fa-solid fa-headset"></i> Contact & Info</button>
                            <button type="button" class="cms-tab-btn" data-tab="tab-footer" style="padding: 10px 18px; border-radius: 8px; border: 1px solid var(--color-panel-border); background: #ffffff; color: var(--color-primary); font-weight: 600; font-size: 0.82rem; cursor: pointer; transition: all 0.2s;"><i class="fa-solid fa-square-parking"></i> Footer & Meta</button>
                        </div>

                        <!-- Tab 1: Hero Banner -->
                        <div class="cms-tab-pane" id="tab-hero" style="display: block;">
                            <h4 style="font-size: 1.1rem; color: var(--color-primary); margin-bottom: 20px; font-weight: 700;"><i class="fa-solid fa-film" style="color: #d97706; margin-right: 8px;"></i> Homepage Hero Banner Settings</h4>
                            
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                                <div class="form-group">
                                    <label style="font-weight: 700;">Hero Collection Tag</label>
                                    <input type="text" name="content[hero_tag]" class="input-control" value="<?php echo htmlspecialchars($sc['hero_tag'] ?? 'Collection 2026'); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label style="font-weight: 700;">Hero Title Line 1</label>
                                    <input type="text" name="content[hero_title_1]" class="input-control" value="<?php echo htmlspecialchars($sc['hero_title_1'] ?? 'Silent Luxury'); ?>" required>
                                </div>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 15px;">
                                <div class="form-group">
                                    <label style="font-weight: 700;">Hero Title Line 2</label>
                                    <input type="text" name="content[hero_title_2]" class="input-control" value="<?php echo htmlspecialchars($sc['hero_title_2'] ?? 'For Modern Spaces'); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label style="font-weight: 700;">Hero Primary Button Text & Link</label>
                                    <div style="display: flex; gap: 10px;">
                                        <input type="text" name="content[hero_btn_primary_text]" class="input-control" placeholder="Button Label" value="<?php echo htmlspecialchars($sc['hero_btn_primary_text'] ?? 'Explore Catalog'); ?>">
                                        <input type="text" name="content[hero_btn_primary_link]" class="input-control" placeholder="shop.php" value="<?php echo htmlspecialchars($sc['hero_btn_primary_link'] ?? 'shop.php'); ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="form-group" style="margin-top: 15px;">
                                <label style="font-weight: 700;">Hero Main Description</label>
                                <textarea name="content[hero_desc]" class="input-control" rows="3" required><?php echo htmlspecialchars($sc['hero_desc'] ?? ''); ?></textarea>
                            </div>

                            <div style="margin-top: 20px; padding: 20px; background: #fafafa; border-radius: 12px; border: 1px solid var(--color-panel-border);">
                                <label style="font-weight: 700; display: block; margin-bottom: 8px;">Hero Background Media (Video MP4 or Image JPG/PNG)</label>
                                <div style="display: flex; gap: 15px; align-items: center; flex-wrap: wrap;">
                                    <input type="text" name="content[hero_media_path]" class="input-control" style="flex: 1; min-width: 250px;" value="<?php echo htmlspecialchars($sc['hero_media_path'] ?? 'assets/images/HERO.mp4'); ?>" placeholder="assets/images/HERO.mp4">
                                    <input type="file" name="hero_media_file" accept="video/mp4,image/*" class="input-control" style="width: auto;">
                                </div>
                                <p style="font-size: 0.75rem; color: var(--color-gray); margin-top: 6px;">Upload a new background video/photo or specify a relative file path.</p>
                            </div>
                        </div>

                        <!-- Tab 2: Homepage About -->
                        <div class="cms-tab-pane" id="tab-about-home" style="display: none;">
                            <h4 style="font-size: 1.1rem; color: var(--color-primary); margin-bottom: 20px; font-weight: 700;"><i class="fa-solid fa-compass" style="color: #d97706; margin-right: 8px;"></i> Homepage About / Story Section</h4>
                            
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                                <div class="form-group">
                                    <label style="font-weight: 700;">Section Badge Tag</label>
                                    <input type="text" name="content[about_home_tag]" class="input-control" value="<?php echo htmlspecialchars($sc['about_home_tag'] ?? 'Our Core Philosophy'); ?>">
                                </div>
                                <div class="form-group">
                                    <label style="font-weight: 700;">Section Main Heading</label>
                                    <input type="text" name="content[about_home_title]" class="input-control" value="<?php echo htmlspecialchars($sc['about_home_title'] ?? 'Architecting Silent Luxury'); ?>">
                                </div>
                            </div>

                            <div class="form-group" style="margin-top: 15px;">
                                <label style="font-weight: 700;">Paragraph 1 (Primary Highlight)</label>
                                <textarea name="content[about_home_p1]" class="input-control" rows="3"><?php echo htmlspecialchars($sc['about_home_p1'] ?? ''); ?></textarea>
                            </div>

                            <div class="form-group" style="margin-top: 15px;">
                                <label style="font-weight: 700;">Paragraph 2 (Secondary Craftsmanship Story)</label>
                                <textarea name="content[about_home_p2]" class="input-control" rows="3"><?php echo htmlspecialchars($sc['about_home_p2'] ?? ''); ?></textarea>
                            </div>

                            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-top: 20px; background: #fafafa; padding: 20px; border-radius: 12px; border: 1px solid var(--color-panel-border);">
                                <div>
                                    <label style="font-weight: 700; font-size: 0.8rem;">Bento Stat 1 Value & Label</label>
                                    <input type="text" name="content[about_home_bento1_val]" class="input-control" style="margin-bottom: 6px;" value="<?php echo htmlspecialchars($sc['about_home_bento1_val'] ?? '15+'); ?>">
                                    <input type="text" name="content[about_home_bento1_label]" class="input-control" value="<?php echo htmlspecialchars($sc['about_home_bento1_label'] ?? 'Years Legacy'); ?>">
                                </div>
                                <div>
                                    <label style="font-weight: 700; font-size: 0.8rem;">Bento Stat 2 Value & Label</label>
                                    <input type="text" name="content[about_home_bento2_val]" class="input-control" style="margin-bottom: 6px;" value="<?php echo htmlspecialchars($sc['about_home_bento2_val'] ?? '100%'); ?>">
                                    <input type="text" name="content[about_home_bento2_label]" class="input-control" value="<?php echo htmlspecialchars($sc['about_home_bento2_label'] ?? 'Bespoke Design'); ?>">
                                </div>
                                <div>
                                    <label style="font-weight: 700; font-size: 0.8rem;">Bento Stat 3 Value & Label</label>
                                    <input type="text" name="content[about_home_bento3_val]" class="input-control" style="margin-bottom: 6px;" value="<?php echo htmlspecialchars($sc['about_home_bento3_val'] ?? '8,000+'); ?>">
                                    <input type="text" name="content[about_home_bento3_label]" class="input-control" value="<?php echo htmlspecialchars($sc['about_home_bento3_label'] ?? 'Elite Residences'); ?>">
                                </div>
                            </div>

                            <div style="margin-top: 20px; padding: 20px; background: #fafafa; border-radius: 12px; border: 1px solid var(--color-panel-border);">
                                <label style="font-weight: 700; display: block; margin-bottom: 8px;">About Section Visual Card Image & Badge</label>
                                <div style="display: flex; gap: 15px; align-items: center; flex-wrap: wrap; margin-bottom: 12px;">
                                    <input type="text" name="content[about_home_image]" class="input-control" style="flex: 1; min-width: 250px;" value="<?php echo htmlspecialchars($sc['about_home_image'] ?? 'assets/images/sofa_1.png'); ?>">
                                    <input type="file" name="about_home_image_file" accept="image/*" class="input-control" style="width: auto;">
                                </div>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 12px;">
                                    <input type="text" name="content[about_home_stat_val]" class="input-control" placeholder="Overlay Stat Val (e.g. 15+ Years)" value="<?php echo htmlspecialchars($sc['about_home_stat_val'] ?? '15+ Years'); ?>">
                                    <input type="text" name="content[about_home_stat_label]" class="input-control" placeholder="Overlay Stat Label" value="<?php echo htmlspecialchars($sc['about_home_stat_label'] ?? 'Master Italian Joinery'); ?>">
                                </div>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                    <div>
                                        <label style="font-weight: 700; font-size: 0.8rem;">Story Button Text</label>
                                        <input type="text" name="content[about_home_btn_text]" class="input-control" value="<?php echo htmlspecialchars($sc['about_home_btn_text'] ?? 'Read Our Full Story'); ?>">
                                    </div>
                                    <div>
                                        <label style="font-weight: 700; font-size: 0.8rem;">Story Button Link</label>
                                        <input type="text" name="content[about_home_btn_link]" class="input-control" value="<?php echo htmlspecialchars($sc['about_home_btn_link'] ?? 'about.php'); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tab 3: Dedicated About Page -->
                        <div class="cms-tab-pane" id="tab-about-page" style="display: none;">
                            <h4 style="font-size: 1.1rem; color: var(--color-primary); margin-bottom: 20px; font-weight: 700;"><i class="fa-solid fa-award" style="color: #d97706; margin-right: 8px;"></i> Dedicated About Us Page Content</h4>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                                <div class="form-group">
                                    <label style="font-weight: 700;">About Page Hero Title</label>
                                    <input type="text" name="content[about_page_hero_title]" class="input-control" value="<?php echo htmlspecialchars($sc['about_page_hero_title'] ?? 'Crafting Timeless Elegance'); ?>">
                                </div>
                                <div class="form-group">
                                    <label style="font-weight: 700;">About Page Hero Subtitle</label>
                                    <input type="text" name="content[about_page_hero_subtitle]" class="input-control" value="<?php echo htmlspecialchars($sc['about_page_hero_subtitle'] ?? ''); ?>">
                                </div>
                            </div>

                            <div style="margin-top: 20px; padding: 20px; background: #fafafa; border-radius: 12px; border: 1px solid var(--color-panel-border);">
                                <h5 style="font-weight: 700; margin-bottom: 12px; color: var(--color-primary);">Heritage & Craftsmanship Block</h5>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 12px;">
                                    <input type="text" name="content[about_page_heritage_tag]" class="input-control" placeholder="Tag" value="<?php echo htmlspecialchars($sc['about_page_heritage_tag'] ?? 'Heritage & Craftsmanship'); ?>">
                                    <input type="text" name="content[about_page_heritage_title]" class="input-control" placeholder="Heading" value="<?php echo htmlspecialchars($sc['about_page_heritage_title'] ?? 'Born in Milan, Crafted for the World'); ?>">
                                </div>
                                <textarea name="content[about_page_heritage_p1]" class="input-control" rows="2" style="margin-bottom: 10px;" placeholder="Paragraph 1"><?php echo htmlspecialchars($sc['about_page_heritage_p1'] ?? ''); ?></textarea>
                                <textarea name="content[about_page_heritage_p2]" class="input-control" rows="2" style="margin-bottom: 12px;" placeholder="Paragraph 2"><?php echo htmlspecialchars($sc['about_page_heritage_p2'] ?? ''); ?></textarea>
                                <div style="display: flex; gap: 15px; align-items: center; flex-wrap: wrap;">
                                    <input type="text" name="content[about_page_heritage_img]" class="input-control" style="flex: 1; min-width: 250px;" value="<?php echo htmlspecialchars($sc['about_page_heritage_img'] ?? 'assets/images/about-craftsman.png'); ?>">
                                    <input type="file" name="about_page_heritage_img_file" accept="image/*" class="input-control" style="width: auto;">
                                </div>
                            </div>

                            <div style="margin-top: 20px; padding: 20px; background: #fafafa; border-radius: 12px; border: 1px solid var(--color-panel-border);">
                                <h5 style="font-weight: 700; margin-bottom: 12px; color: var(--color-primary);">Showroom Sanctuary Block</h5>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 12px;">
                                    <input type="text" name="content[about_page_showroom_tag]" class="input-control" placeholder="Tag" value="<?php echo htmlspecialchars($sc['about_page_showroom_tag'] ?? 'Flagship Sanctuary'); ?>">
                                    <input type="text" name="content[about_page_showroom_title]" class="input-control" placeholder="Heading" value="<?php echo htmlspecialchars($sc['about_page_showroom_title'] ?? 'Experience OXO in Person'); ?>">
                                </div>
                                <textarea name="content[about_page_showroom_p1]" class="input-control" rows="2" style="margin-bottom: 10px;" placeholder="Paragraph 1"><?php echo htmlspecialchars($sc['about_page_showroom_p1'] ?? ''); ?></textarea>
                                <textarea name="content[about_page_showroom_p2]" class="input-control" rows="2" style="margin-bottom: 12px;" placeholder="Paragraph 2"><?php echo htmlspecialchars($sc['about_page_showroom_p2'] ?? ''); ?></textarea>
                                <div style="display: flex; gap: 15px; align-items: center; flex-wrap: wrap;">
                                    <input type="text" name="content[about_page_showroom_img]" class="input-control" style="flex: 1; min-width: 250px;" value="<?php echo htmlspecialchars($sc['about_page_showroom_img'] ?? 'assets/images/flagship-facade.jpg'); ?>">
                                    <input type="file" name="about_page_showroom_img_file" accept="image/*" class="input-control" style="width: auto;">
                                </div>
                            </div>

                            <div style="margin-top: 25px; padding: 20px; background: #fafafa; border-radius: 12px; border: 1px solid var(--color-panel-border);">
                                <h5 style="font-weight: 700; margin-bottom: 6px; color: var(--color-primary); display: flex; align-items: center; gap: 8px;">
                                    <i class="fa-solid fa-store" style="color: #d97706;"></i> Shop Gallery Headers
                                </h5>
                                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px;">
                                    <div>
                                        <label style="font-weight: 700; font-size: 0.8rem;">Gallery Section Tag</label>
                                        <input type="text" name="content[about_page_shop_gallery_tag]" class="input-control" value="<?php echo htmlspecialchars($sc['about_page_shop_gallery_tag'] ?? 'Atmosphere & Space'); ?>">
                                    </div>
                                    <div>
                                        <label style="font-weight: 700; font-size: 0.8rem;">Gallery Section Title</label>
                                        <input type="text" name="content[about_page_shop_gallery_title]" class="input-control" value="<?php echo htmlspecialchars($sc['about_page_shop_gallery_title'] ?? 'Inside Our Flagship Store'); ?>">
                                    </div>
                                    <div>
                                        <label style="font-weight: 700; font-size: 0.8rem;">Gallery Subtitle</label>
                                        <input type="text" name="content[about_page_shop_gallery_sub]" class="input-control" value="<?php echo htmlspecialchars($sc['about_page_shop_gallery_sub'] ?? 'Experience our physical sanctuary, bespoke displays, and spatial architecture.'); ?>">
                                    </div>
                                </div>
                            </div>

                            <!-- Dynamic Shop Images List & Upload Panel -->
                            <div style="margin-top: 25px; border-top: 1px solid var(--color-panel-border); padding-top: 20px;">
                                <h5 style="font-weight: 700; margin-bottom: 15px; color: var(--color-primary); display: flex; align-items: center; gap: 8px;">
                                    <i class="fa-solid fa-images" style="color: #d97706;"></i> Dynamic Shop Showcase Photos
                                </h5>

                                <?php
                                $all_shop_photos = get_shop_images(false);
                                ?>

                                <!-- Grid layout: Upload New Photo (Left) + List of Photos (Right) -->
                                <div style="display: grid; grid-template-columns: 1fr 1.5fr; gap: 25px;">
                                    
                                    <!-- Add Shop Image Form -->
                                    <div style="background: #ffffff; border: 1px solid var(--color-panel-border); border-radius: 12px; padding: 20px;">
                                        <h6 style="font-weight: 700; color: var(--color-primary); margin-bottom: 12px; font-size: 0.95rem;">
                                            <i class="fa-solid fa-plus-circle" style="color: var(--color-accent);"></i> Add New Shop Photo
                                        </h6>

                                        <div class="form-group" style="margin-bottom: 12px;">
                                            <label style="font-size: 0.78rem; font-weight: 700;">Photo Title / Slot Tag</label>
                                            <input type="text" id="add_shop_title" name="title" class="input-control" placeholder="e.g. Flagship Storefront Facade" form="add_shop_photo_form" required>
                                        </div>

                                        <div class="form-group" style="margin-bottom: 12px;">
                                            <label style="font-size: 0.78rem; font-weight: 700;">Caption / Subtitle</label>
                                            <textarea id="add_shop_caption" name="caption" class="input-control" rows="2" placeholder="e.g. Corrugated dark cladding with signature orange framing." form="add_shop_photo_form"></textarea>
                                        </div>

                                        <div class="form-group" style="margin-bottom: 12px;">
                                            <label style="font-size: 0.78rem; font-weight: 700;">Upload Image File</label>
                                            <input type="file" id="add_shop_file" name="image_file" accept="image/*" class="input-control" style="font-size: 0.78rem;" form="add_shop_photo_form">
                                        </div>

                                        <div class="form-group" style="margin-bottom: 12px;">
                                            <label style="font-size: 0.78rem; font-weight: 700;">Or Relative Image Path</label>
                                            <input type="text" id="add_shop_url" name="image_url" class="input-control" placeholder="assets/images/flagship-facade.jpg" form="add_shop_photo_form">
                                        </div>

                                        <div style="display: flex; gap: 15px; margin-bottom: 15px;">
                                            <div class="form-group" style="flex: 1;">
                                                <label style="font-size: 0.78rem; font-weight: 700;">Sort Order</label>
                                                <input type="number" name="sort_order" class="input-control" value="0" form="add_shop_photo_form">
                                            </div>
                                            <div class="form-group" style="flex: 1; display: flex; align-items: center; gap: 8px; margin-top: 20px;">
                                                <input type="checkbox" id="add_shop_active" name="is_active" value="1" checked form="add_shop_photo_form" style="width: 18px; height: 18px; accent-color: var(--color-primary);">
                                                <label for="add_shop_active" style="font-size: 0.78rem; font-weight: 700; cursor: pointer;">Set Active</label>
                                            </div>
                                        </div>

                                        <button type="submit" form="add_shop_photo_form" class="action-btn" style="width: 100%; justify-content: center; padding: 10px; font-size: 0.88rem;">
                                            <i class="fa-solid fa-cloud-arrow-up"></i> Upload & Add Shop Photo
                                        </button>
                                    </div>

                                    <!-- Existing Shop Photos List -->
                                    <div style="display: flex; flex-direction: column; gap: 15px; max-height: 520px; overflow-y: auto; padding-right: 5px;">
                                        <?php if (empty($all_shop_photos)): ?>
                                            <div style="text-align: center; padding: 40px 20px; background: #ffffff; border-radius: 12px; border: 1px dashed var(--color-panel-border);">
                                                <i class="fa-solid fa-store" style="font-size: 2rem; color: var(--color-gray); margin-bottom: 10px; display: block;"></i>
                                                <p style="color: var(--color-gray); font-size: 0.88rem; margin: 0;">No shop photos added yet.</p>
                                            </div>
                                        <?php else: ?>
                                            <?php foreach ($all_shop_photos as $sp): ?>
                                                <div style="background: #ffffff; border: 1px solid <?php echo $sp['is_active'] ? 'var(--color-panel-border)' : 'rgba(231, 76, 60, 0.3)'; ?>; border-radius: 10px; padding: 12px; display: flex; gap: 15px; align-items: center;">
                                                    <div style="width: 85px; height: 75px; border-radius: 8px; overflow: hidden; background: #000; flex-shrink: 0;">
                                                        <img src="../<?php echo htmlspecialchars($sp['image_path']); ?>" alt="Photo" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.parentElement.style.display='none';">
                                                    </div>
                                                    <div style="flex: 1; min-width: 0;">
                                                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                                                            <h6 style="font-family: var(--font-title); font-size: 0.9rem; color: var(--color-primary); margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo htmlspecialchars($sp['title']); ?></h6>
                                                            <span style="padding: 2px 8px; border-radius: 12px; font-size: 0.65rem; font-weight: 700; text-transform: uppercase; <?php echo $sp['is_active'] ? 'background: rgba(46, 204, 113, 0.15); color: #27ae60;' : 'background: rgba(231, 76, 60, 0.15); color: #e74c3c;'; ?>">
                                                                <?php echo $sp['is_active'] ? 'Active' : 'Inactive'; ?>
                                                            </span>
                                                        </div>
                                                        <p style="font-size: 0.78rem; color: var(--color-gray); margin: 0 0 6px 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo htmlspecialchars($sp['caption']); ?></p>

                                                        <div style="display: flex; gap: 8px; align-items: center;">
                                                            <!-- Toggle Visibility Form -->
                                                            <form action="index.php?tab=settings&section=cms" method="POST" style="display: inline;">
                                                                <input type="hidden" name="form_action" value="toggle_shop_image">
                                                                <input type="hidden" name="shop_image_id" value="<?php echo $sp['id']; ?>">
                                                                <input type="hidden" name="new_status" value="<?php echo $sp['is_active'] ? 0 : 1; ?>">
                                                                <button type="submit" class="action-btn" style="padding: 4px 10px; font-size: 0.72rem; <?php echo $sp['is_active'] ? 'background: rgba(230, 126, 34, 0.12); color: #d35400; border: 1px solid #e67e22;' : 'background: rgba(46, 204, 113, 0.12); color: #27ae60; border: 1px solid #2ecc71;'; ?>">
                                                                    <i class="fa-solid <?php echo $sp['is_active'] ? 'fa-eye-slash' : 'fa-eye'; ?>"></i> <?php echo $sp['is_active'] ? 'Hide' : 'Show'; ?>
                                                                </button>
                                                            </form>

                                                            <!-- Delete Form -->
                                                            <form action="index.php?tab=settings&section=cms" method="POST" style="display: inline;" onsubmit="return confirm('Delete this shop photo from gallery?');">
                                                                <input type="hidden" name="form_action" value="delete_shop_image">
                                                                <input type="hidden" name="shop_image_id" value="<?php echo $sp['id']; ?>">
                                                                <button type="submit" class="action-btn" style="padding: 4px 10px; font-size: 0.72rem; background: rgba(231, 76, 60, 0.12); color: #e74c3c; border: 1px solid #e74c3c;">
                                                                    <i class="fa-solid fa-trash-can"></i> Delete
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Form element for Add Shop Photo -->
                        <form id="add_shop_photo_form" action="index.php?tab=settings&section=cms" method="POST" enctype="multipart/form-data" style="display: none;">
                            <input type="hidden" name="form_action" value="add_shop_image">
                        </form>

                        <!-- Tab 4: Contact & Concierge -->
                        <div class="cms-tab-pane" id="tab-contact" style="display: none;">
                            <h4 style="font-size: 1.1rem; color: var(--color-primary); margin-bottom: 20px; font-weight: 700;"><i class="fa-solid fa-headset" style="color: #d97706; margin-right: 8px;"></i> Bespoke Concierge & Contact Details</h4>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                                <div class="form-group">
                                    <label style="font-weight: 700;">Contact Badge Tag</label>
                                    <input type="text" name="content[contact_tag]" class="input-control" value="<?php echo htmlspecialchars($sc['contact_tag'] ?? 'Bespoke Concierge'); ?>">
                                </div>
                                <div class="form-group">
                                    <label style="font-weight: 700;">Contact Heading Title</label>
                                    <input type="text" name="content[contact_title]" class="input-control" value="<?php echo htmlspecialchars($sc['contact_title'] ?? 'Connect With OXO Private Service'); ?>">
                                </div>
                            </div>

                            <div class="form-group" style="margin-top: 15px;">
                                <label style="font-weight: 700;">Contact Subtitle / Description</label>
                                <textarea name="content[contact_subtitle]" class="input-control" rows="2"><?php echo htmlspecialchars($sc['contact_subtitle'] ?? ''); ?></textarea>
                            </div>

                            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-top: 20px;">
                                <div class="form-group">
                                    <label style="font-weight: 700;">Showroom Address</label>
                                    <input type="text" name="content[contact_address]" class="input-control" value="<?php echo htmlspecialchars($sc['contact_address'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label style="font-weight: 700;">Private Inquiry Email</label>
                                    <input type="email" name="content[contact_email]" class="input-control" value="<?php echo htmlspecialchars($sc['contact_email'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label style="font-weight: 700;">Phone / WhatsApp Concierge</label>
                                    <input type="text" name="content[contact_phone]" class="input-control" value="<?php echo htmlspecialchars($sc['contact_phone'] ?? ''); ?>">
                                </div>
                            </div>

                            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-top: 15px;">
                                <div class="form-group">
                                    <label style="font-weight: 700;">Instagram URL</label>
                                    <input type="text" name="content[contact_instagram]" class="input-control" value="<?php echo htmlspecialchars($sc['contact_instagram'] ?? '#'); ?>">
                                </div>
                                <div class="form-group">
                                    <label style="font-weight: 700;">Facebook URL</label>
                                    <input type="text" name="content[contact_facebook]" class="input-control" value="<?php echo htmlspecialchars($sc['contact_facebook'] ?? '#'); ?>">
                                </div>
                                <div class="form-group">
                                    <label style="font-weight: 700;">Google Maps URL Link</label>
                                    <input type="text" name="content[contact_map]" class="input-control" value="<?php echo htmlspecialchars($sc['contact_map'] ?? 'https://maps.google.com'); ?>" placeholder="https://maps.google.com/...">
                                </div>
                            </div>
                        </div>

                        <!-- Tab 5: Footer & Meta -->
                        <div class="cms-tab-pane" id="tab-footer" style="display: none;">
                            <h4 style="font-size: 1.1rem; color: var(--color-primary); margin-bottom: 20px; font-weight: 700;"><i class="fa-solid fa-square-parking" style="color: #d97706; margin-right: 8px;"></i> Footer & Site Metadata</h4>

                            <div class="form-group">
                                <label style="font-weight: 700;">Global Site Title (SEO)</label>
                                <input type="text" name="content[site_title]" class="input-control" value="<?php echo htmlspecialchars($sc['site_title'] ?? 'OXO — Premium Furniture Store'); ?>">
                            </div>

                            <div class="form-group" style="margin-top: 15px;">
                                <label style="font-weight: 700;">Global Site Description (SEO)</label>
                                <textarea name="content[site_description]" class="input-control" rows="2"><?php echo htmlspecialchars($sc['site_description'] ?? ''); ?></textarea>
                            </div>

                            <div class="form-group" style="margin-top: 15px;">
                                <label style="font-weight: 700;">Footer Brand Manifesto Description</label>
                                <textarea name="content[footer_desc]" class="input-control" rows="3"><?php echo htmlspecialchars($sc['footer_desc'] ?? ''); ?></textarea>
                            </div>

                            <div class="form-group" style="margin-top: 15px;">
                                <label style="font-weight: 700;">Footer Copyright Notice Text</label>
                                <input type="text" name="content[footer_copyright]" class="input-control" value="<?php echo htmlspecialchars($sc['footer_copyright'] ?? 'OXO Furniture. All rights reserved.'); ?>">
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 15px;">
                                <div class="form-group">
                                    <label style="font-weight: 700;">Developer Credit Label</label>
                                    <input type="text" name="content[footer_dev_credit]" class="input-control" value="<?php echo htmlspecialchars($sc['footer_dev_credit'] ?? 'Designed and Developed by peru'); ?>">
                                </div>
                                <div class="form-group">
                                    <label style="font-weight: 700;">Developer Link URL</label>
                                    <input type="text" name="content[footer_dev_link]" class="input-control" value="<?php echo htmlspecialchars($sc['footer_dev_link'] ?? '#'); ?>" placeholder="https://peru.com">
                                </div>
                            </div>
                        </div>

                        <div style="margin-top: 35px; border-top: 1px solid var(--color-panel-border); padding-top: 20px; display: flex; justify-content: flex-end;">
                            <button type="submit" class="action-btn" style="background: #d97706; border-color: #b45309; color: #ffffff; padding: 14px 28px; font-size: 0.95rem;">
                                <i class="fa-solid fa-circle-check"></i> Save All Site Content Changes
                            </button>
                        </div>
                    </form>
                </div>

                <script>
                document.addEventListener('DOMContentLoaded', () => {
                    const tabBtns = document.querySelectorAll('.cms-tab-btn');
                    const tabPanes = document.querySelectorAll('.cms-tab-pane');

                    tabBtns.forEach(btn => {
                        btn.addEventListener('click', () => {
                            const targetTab = btn.getAttribute('data-tab');

                            tabBtns.forEach(b => {
                                b.style.background = '#ffffff';
                                b.style.color = 'var(--color-primary)';
                                b.style.borderColor = 'var(--color-panel-border)';
                                b.classList.remove('active');
                            });
                            btn.style.background = '#d97706';
                            btn.style.color = '#ffffff';
                            btn.style.borderColor = '#d97706';
                            btn.classList.add('active');

                            tabPanes.forEach(pane => {
                                pane.style.display = (pane.id === targetTab) ? 'block' : 'none';
                            });
                        });
                    });
                });
                </script>

            <?php elseif ($current_section === 'whatsapp'): ?>
                <div class="settings-card" style="max-width: 600px; margin: 0 auto; background: #ffffff; padding: 30px; border-radius: 20px; border: 1px solid rgba(10, 46, 36, 0.09); box-shadow: 0 4px 20px rgba(0,0,0,0.03);">
                    <h3 class="editor-card-title" style="font-family: var(--font-title); font-size: 1.2rem; color: var(--color-primary); margin-bottom: 20px;"><i class="fa-brands fa-whatsapp" style="margin-right: 8px; color: #25D366;"></i> WhatsApp Configuration</h3>
                    <?php
                    $current_whatsapp = '';
                    if ($db) {
                        try {
                            $w_stmt = $db->prepare("SELECT `whatsapp` FROM `oxo_admins` WHERE `username` = ?");
                            $w_stmt->execute([$_SESSION['admin_username']]);
                            $current_whatsapp = $w_stmt->fetchColumn();
                        } catch (\Exception $e) {}
                    }
                    ?>
                    <form action="index.php?tab=settings&section=whatsapp" method="POST">
                        <input type="hidden" name="form_action" value="update_whatsapp">
                        <div class="form-group" style="margin-bottom: 20px;">
                            <label for="whatsapp_sec" style="font-weight: 700;">Admin WhatsApp Contact Number</label>
                            <input type="text" id="whatsapp_sec" name="whatsapp" class="input-control" value="<?php echo htmlspecialchars($current_whatsapp ?? ''); ?>" placeholder="e.g. 919876543210 (include country code without + or spaces)" required>
                            <p style="font-size: 0.78rem; color: var(--color-gray); margin-top: 6px;">
                                Specify the WhatsApp contact number (with country code, e.g. 919876543210 for India) where client inquiries will be redirected.
                            </p>
                        </div>
                        <button type="submit" class="action-btn" style="width: 100%; justify-content: center; margin-top: 10px; background: #25D366; border-color: #20BA5A; color: #ffffff; padding: 12px 20px;">
                            <i class="fa-brands fa-whatsapp"></i> Update WhatsApp Contact
                        </button>
                    </form>
                </div>

            <?php elseif ($current_section === 'security'): ?>
                <div class="settings-card" style="max-width: 600px; margin: 0 auto; background: #ffffff; padding: 30px; border-radius: 20px; border: 1px solid rgba(10, 46, 36, 0.09); box-shadow: 0 4px 20px rgba(0,0,0,0.03);">
                    <h3 class="editor-card-title" style="font-family: var(--font-title); font-size: 1.2rem; color: var(--color-primary); margin-bottom: 20px;"><i class="fa-solid fa-lock" style="margin-right: 8px; color: var(--color-accent);"></i> Change Admin Password</h3>
                    <form action="index.php?tab=settings&section=security" method="POST">
                        <input type="hidden" name="form_action" value="reset_password">
                        <div class="form-group" style="margin-bottom: 16px;">
                            <label for="current_password_sec" style="font-weight: 700;">Current Password</label>
                            <input type="password" id="current_password_sec" name="current_password" class="input-control" required placeholder="Type current password" autocomplete="current-password">
                        </div>
                        <div class="form-group" style="margin-bottom: 16px;">
                            <label for="new_password_sec" style="font-weight: 700;">New Password</label>
                            <input type="password" id="new_password_sec" name="new_password" class="input-control" required placeholder="Choose a secure password (min. 6 chars)" autocomplete="new-password">
                        </div>
                        <div class="form-group" style="margin-bottom: 20px;">
                            <label for="confirm_password_sec" style="font-weight: 700;">Confirm New Password</label>
                            <input type="password" id="confirm_password_sec" name="confirm_password" class="input-control" required placeholder="Retype new password" autocomplete="new-password">
                        </div>
                        <button type="submit" class="action-btn" style="width: 100%; justify-content: center; margin-top: 10px; padding: 12px 20px;">
                            <i class="fa-solid fa-key"></i> Update Credentials
                        </button>
                    </form>
                </div>

            <?php elseif ($current_section === 'collections'): ?>
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
                                                        <button type="button" 
                                                                onclick="openEditBrandModal(<?php echo $b['id']; ?>, '<?php echo addslashes($b['name']); ?>', '<?php echo addslashes($b['logo_path']); ?>')" 
                                                                class="btn-icon edit" 
                                                                style="background: none; border: none; cursor: pointer; color: var(--color-accent); margin-right: 8px; font-size: 0.9rem;"
                                                                title="Edit Brand">
                                                            <i class="fa-solid fa-pen-to-square"></i>
                                                        </button>
                                                        <a href="index.php?tab=settings&section=collections&action=delete&id=<?php echo urlencode($b['id']); ?>" 
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
                            <form action="index.php?tab=settings&section=collections" method="POST" enctype="multipart/form-data">
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

                <!-- Section 2: Furniture Categories -->
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
                                            <th>Section Background</th>
                                            <th style="width: 100px; text-align: right;">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($categories_list)): ?>
                                            <tr>
                                                <td colspan="4" style="text-align: center; padding: 20px; color: var(--color-gray);">No categories registered.</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($categories_list as $cat): ?>
                                                <tr>
                                                    <td style="font-weight: 700; color: var(--color-primary);"><?php echo htmlspecialchars($cat['name']); ?></td>
                                                    <td><code style="color: var(--color-accent); font-family: var(--font-numeric); font-size: 0.85rem; background: var(--color-gray-dark); padding: 4px 8px; border-radius: 4px;"><?php echo htmlspecialchars($cat['slug']); ?></code></td>
                                                    <td>
                                                        <?php if (!empty($cat['bg_color'])): ?>
                                                            <div style="display: flex; align-items: center; gap: 8px;">
                                                                <span style="display: inline-block; width: 14px; height: 14px; border-radius: 4px; background: <?php echo htmlspecialchars($cat['bg_color']); ?>; border: 1px solid var(--color-panel-border);"></span>
                                                                <span style="font-size: 0.8rem; font-family: var(--font-numeric); color: var(--color-primary);"><?php echo htmlspecialchars($cat['bg_color']); ?></span>
                                                            </div>
                                                        <?php else: ?>
                                                            <span style="font-size: 0.75rem; color: var(--color-gray); font-style: italic;">Auto Pastel HSL</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td style="text-align: right;">
                                                        <button type="button" 
                                                                onclick="openEditCategoryModal(<?php echo $cat['id']; ?>, '<?php echo addslashes($cat['name']); ?>', '<?php echo addslashes($cat['slug']); ?>', '<?php echo addslashes($cat['bg_color'] ?? ''); ?>')" 
                                                                class="btn-icon edit" 
                                                                style="background: none; border: none; cursor: pointer; color: var(--color-accent); margin-right: 8px; font-size: 0.9rem;"
                                                                title="Edit Category">
                                                            <i class="fa-solid fa-pen-to-square"></i>
                                                        </button>
                                                        <a href="index.php?tab=settings&section=collections&action=delete_category&id=<?php echo $cat['id']; ?>" 
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
                            <form action="index.php?tab=settings&section=collections" method="POST">
                                <input type="hidden" name="form_action" value="add_category">
                                
                                <div class="form-group" style="margin-bottom: 15px;">
                                    <label for="cat_name">Category Name</label>
                                    <input type="text" id="cat_name" name="cat_name" class="input-control" required placeholder="e.g. Armchairs" oninput="document.getElementById('cat_slug').value = this.value.toLowerCase().replace(/[^a-z0-9]/g, '-').replace(/-+/g, '-');">
                                </div>
                                
                                <div class="form-group" style="margin-bottom: 15px;">
                                    <label for="cat_slug">Category Slug</label>
                                    <input type="text" id="cat_slug" name="cat_slug" class="input-control" required placeholder="e.g. armchairs">
                                </div>
                                
                                <div class="form-group" style="margin-bottom: 15px;">
                                    <label for="cat_bg_color">Section Background Color (Pastel)</label>
                                    <div style="display: flex; gap: 10px; align-items: center;">
                                        <input type="text" id="cat_bg_color" name="cat_bg_color" class="input-control" placeholder="e.g. #FAF9F6" value="#FAF9F6">
                                        <input type="color" id="cat_bg_picker" class="input-control" style="width: 45px; height: 42px; padding: 2px; border-radius: 6px; cursor: pointer; border: 1px solid var(--color-panel-border);" value="#FAF9F6" oninput="document.getElementById('cat_bg_color').value = this.value;">
                                    </div>
                                    <span style="font-size: 0.68rem; color: var(--color-gray); margin-top: 5px; display: inline-block;">Leave blank to dynamically generate a pastel shade.</span>
                                </div>
                                
                                <button type="submit" class="action-btn" style="width: 100%; justify-content: center; margin-top: 20px;">
                                    <i class="fa-solid fa-circle-plus"></i> Register Category
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Section 3: Material Types -->
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
                                                        <button type="button" 
                                                                onclick="openEditMaterialModal(<?php echo $mat['id']; ?>, '<?php echo addslashes($mat['name']); ?>', '<?php echo addslashes($mat['slug']); ?>')" 
                                                                class="btn-icon edit" 
                                                                style="background: none; border: none; cursor: pointer; color: var(--color-accent); margin-right: 8px; font-size: 0.9rem;"
                                                                title="Edit Material">
                                                            <i class="fa-solid fa-pen-to-square"></i>
                                                        </button>
                                                        <a href="index.php?tab=settings&section=collections&action=delete_material&id=<?php echo $mat['id']; ?>" 
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
                            <form action="index.php?tab=settings&section=collections" method="POST">
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

                <!-- Section 4: Color Palette -->
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
                                                        <button type="button" 
                                                                onclick="openEditColorModal(<?php echo $color['id']; ?>, '<?php echo addslashes($color['name']); ?>', '<?php echo addslashes($color['hex']); ?>')" 
                                                                class="btn-icon edit" 
                                                                style="background: none; border: none; cursor: pointer; color: var(--color-accent); margin-right: 8px; font-size: 0.9rem;"
                                                                title="Edit Color">
                                                            <i class="fa-solid fa-pen-to-square"></i>
                                                        </button>
                                                        <a href="index.php?tab=settings&section=collections&action=delete_color&id=<?php echo $color['id']; ?>" 
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
                            <form action="index.php?tab=settings&section=collections" method="POST">
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

            <?php elseif ($current_section === 'announcement'): ?>
                <?php
                $announcements_list = [];
                if ($db) {
                    try {
                        $ann_stmt = $db->query("SELECT * FROM `oxo_announcements` ORDER BY `id` DESC");
                        $announcements_list = $ann_stmt->fetchAll(PDO::FETCH_ASSOC);
                    } catch (\PDOException $e) {
                        $announcements_list = [];
                    }
                }
                ?>
                <div class="brands-grid" style="grid-template-columns: 1.2fr 1fr; gap: 30px; margin-bottom: 50px;">
                    <!-- Left: Upload / Add New Announcement Form -->
                    <div class="brands-form-panel">
                        <div class="editor-card" style="padding: 30px;">
                            <h3 style="font-family: var(--font-title); font-size: 1.3rem; color: var(--color-primary); margin-bottom: 10px; display: flex; align-items: center; gap: 10px;">
                                <i class="fa-solid fa-bullhorn" style="color: var(--color-accent);"></i> Post New Announcement
                            </h3>
                            <p style="color: var(--color-gray); font-size: 0.88rem; margin-bottom: 25px; line-height: 1.5;">
                                Upload a high-resolution announcement poster image. When set to <strong>Active</strong>, it will automatically popup for visitors when the index page loads. If no poster is active, the index page loads normally.
                            </p>

                            <form action="index.php?tab=settings&section=announcement" method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="form_action" value="save_announcement">

                                <div class="form-group" style="margin-bottom: 20px;">
                                    <label for="ann_poster_file" style="font-weight: 700;">Poster Image File (Upload)</label>
                                    <input type="file" id="ann_poster_file" name="poster_file" accept="image/*" class="input-control" style="padding: 10px;">
                                    <span style="font-size: 0.78rem; color: var(--color-gray); margin-top: 4px; display: block;">Supports JPG, PNG, WEBP, GIF. Image will be automatically compressed for fast loading.</span>
                                </div>

                                <div class="form-group" style="margin-bottom: 20px;">
                                    <label for="ann_poster_url" style="font-weight: 700;">Or Image Relative URL</label>
                                    <input type="text" id="ann_poster_url" name="poster_url" class="input-control" placeholder="assets/images/announcement.jpg">
                                </div>

                                <div class="form-group" style="margin-bottom: 20px;">
                                    <label for="ann_title" style="font-weight: 700;">Announcement Headline / Title (Optional)</label>
                                    <input type="text" id="ann_title" name="title" class="input-control" placeholder="e.g. Exclusive Private Trunk Show 2026">
                                </div>

                                <div class="form-group" style="margin-bottom: 20px;">
                                    <label for="ann_subtitle" style="font-weight: 700;">Subtitle / Brief Description (Optional)</label>
                                    <textarea id="ann_subtitle" name="subtitle" class="input-control" rows="2" placeholder="e.g. Discover our limited edition Calacatta Marble & Walnut Collection before public release."></textarea>
                                </div>

                                <div class="form-group" style="margin-bottom: 20px;">
                                    <label for="ann_link_url" style="font-weight: 700;">Click Link / Action Button URL (Optional)</label>
                                    <input type="text" id="ann_link_url" name="link_url" class="input-control" placeholder="e.g. shop.php?category=sofas or #contact">
                                </div>

                                <div class="form-group" style="margin-bottom: 25px; display: flex; align-items: center; gap: 12px; background: rgba(200, 162, 118, 0.08); padding: 16px; border-radius: 12px; border: 1px solid rgba(200, 162, 118, 0.2);">
                                    <input type="checkbox" id="ann_is_active" name="is_active" value="1" checked style="width: 20px; height: 20px; cursor: pointer; accent-color: var(--color-primary);">
                                    <label for="ann_is_active" style="font-weight: 700; color: var(--color-primary); cursor: pointer; margin: 0;">
                                        Set as Active Announcement Poster (Popup on index load)
                                    </label>
                                </div>

                                <button type="submit" class="action-btn" style="width: 100%; justify-content: center; padding: 14px 20px; font-size: 0.95rem;">
                                    <i class="fa-solid fa-cloud-arrow-up"></i> Publish Announcement Poster
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Right: Current Posters List & Status Management -->
                    <div class="brands-list-panel">
                        <div class="editor-card" style="padding: 30px;">
                            <h3 style="font-family: var(--font-title); font-size: 1.3rem; color: var(--color-primary); margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                                <i class="fa-solid fa-images" style="color: var(--color-accent);"></i> Posted Announcements
                            </h3>

                            <?php if (empty($announcements_list)): ?>
                                <div style="text-align: center; padding: 40px 20px; background: var(--color-bg-body); border-radius: 16px; border: 1px dashed var(--color-panel-border);">
                                    <i class="fa-solid fa-bullhorn" style="font-size: 2.5rem; color: var(--color-gray); margin-bottom: 12px; display: block;"></i>
                                    <p style="color: var(--color-gray); font-size: 0.95rem; margin: 0;">No announcement posters posted yet.</p>
                                    <span style="font-size: 0.8rem; color: var(--color-gray); display: block; margin-top: 6px;">Index page will load normally without popups.</span>
                                </div>
                            <?php else: ?>
                                <div style="display: flex; flex-direction: column; gap: 20px;">
                                    <?php foreach ($announcements_list as $ann): ?>
                                        <div style="background: var(--color-bg-body); border-radius: 16px; padding: 20px; border: 1px solid <?php echo $ann['is_active'] ? 'var(--color-accent)' : 'var(--color-panel-border)'; ?>; position: relative;">
                                            <div style="display: flex; gap: 18px; align-items: flex-start;">
                                                <div style="width: 120px; height: 120px; border-radius: 12px; overflow: hidden; background: #000; flex-shrink: 0;">
                                                    <img src="../<?php echo htmlspecialchars($ann['image_path']); ?>" alt="Poster" style="width: 100%; height: 100%; object-fit: cover;">
                                                </div>
                                                <div style="flex: 1;">
                                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                                        <span style="padding: 4px 12px; border-radius: 20px; font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; <?php echo $ann['is_active'] ? 'background: rgba(46, 204, 113, 0.15); color: #2ecc71; border: 1px solid #2ecc71;' : 'background: rgba(149, 165, 166, 0.15); color: #7f8c8d; border: 1px solid #7f8c8d;'; ?>">
                                                            <i class="fa-solid <?php echo $ann['is_active'] ? 'fa-circle-check' : 'fa-circle-pause'; ?>"></i> <?php echo $ann['is_active'] ? 'Active Popup' : 'Inactive'; ?>
                                                        </span>
                                                        <span style="font-size: 0.75rem; color: var(--color-gray); font-family: var(--font-numeric);">
                                                            <?php echo date('M d, Y', strtotime($ann['created_at'])); ?>
                                                        </span>
                                                    </div>
                                                    <?php if (!empty($ann['title'])): ?>
                                                        <h4 style="font-family: var(--font-title); font-size: 1rem; color: var(--color-primary); margin: 0 0 4px 0;"><?php echo htmlspecialchars($ann['title']); ?></h4>
                                                    <?php endif; ?>
                                                    <?php if (!empty($ann['subtitle'])): ?>
                                                        <p style="font-size: 0.82rem; color: var(--color-gray); margin: 0 0 12px 0; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;"><?php echo htmlspecialchars($ann['subtitle']); ?></p>
                                                    <?php endif; ?>

                                                    <div style="display: flex; gap: 10px; margin-top: 10px;">
                                                        <form action="index.php?tab=settings&section=announcement" method="POST" style="display: inline;">
                                                            <input type="hidden" name="form_action" value="toggle_announcement">
                                                            <input type="hidden" name="announcement_id" value="<?php echo $ann['id']; ?>">
                                                            <input type="hidden" name="new_status" value="<?php echo $ann['is_active'] ? 0 : 1; ?>">
                                                            <button type="submit" class="action-btn" style="padding: 6px 14px; font-size: 0.78rem; <?php echo $ann['is_active'] ? 'background: rgba(230, 126, 34, 0.15); color: #d35400; border: 1px solid #e67e22;' : 'background: rgba(46, 204, 113, 0.15); color: #27ae60; border: 1px solid #2ecc71;'; ?>">
                                                                <i class="fa-solid <?php echo $ann['is_active'] ? 'fa-pause' : 'fa-play'; ?>"></i> <?php echo $ann['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                                            </button>
                                                        </form>

                                                        <form action="index.php?tab=settings&section=announcement" method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this announcement poster?');">
                                                            <input type="hidden" name="form_action" value="delete_announcement">
                                                            <input type="hidden" name="announcement_id" value="<?php echo $ann['id']; ?>">
                                                            <button type="submit" class="action-btn" style="padding: 6px 14px; font-size: 0.78rem; background: rgba(231, 76, 60, 0.15); color: #e74c3c; border: 1px solid #e74c3c;">
                                                                <i class="fa-solid fa-trash-can"></i> Delete
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

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
        if (currentClientPhone.length === 10) {
            currentClientPhone = '91' + currentClientPhone;
        }
        
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
            updateLeadStatus(currentInquiryId, 'Addressed');
        }
        
        closeReplyModal();
    }

    // Kanban Drag and Drop Interactive Logic
    document.addEventListener('DOMContentLoaded', () => {
        const cards = document.querySelectorAll('.kanban-card');
        const containers = document.querySelectorAll('.kanban-cards-container');
        const columns = document.querySelectorAll('.kanban-column');

        cards.forEach(card => {
            card.addEventListener('dragstart', () => {
                card.classList.add('dragging');
            });
            card.addEventListener('dragend', () => {
                card.classList.remove('dragging');
            });
        });

        containers.forEach(container => {
            container.addEventListener('dragover', (e) => {
                e.preventDefault();
                const afterElement = getDragAfterElement(container, e.clientY);
                const draggingCard = document.querySelector('.dragging');
                if (draggingCard) {
                    if (afterElement == null) {
                        container.appendChild(draggingCard);
                    } else {
                        container.insertBefore(draggingCard, afterElement);
                    }
                }
            });
        });

        columns.forEach(column => {
            column.addEventListener('dragenter', (e) => {
                e.preventDefault();
                column.classList.add('drag-over');
            });
            column.addEventListener('dragleave', () => {
                column.classList.remove('drag-over');
            });
            column.addEventListener('drop', (e) => {
                e.preventDefault();
                column.classList.remove('drag-over');
                const draggingCard = document.querySelector('.dragging');
                if (draggingCard) {
                    const newStatus = column.getAttribute('data-status');
                    const cardId = draggingCard.getAttribute('data-id');
                    updateLeadStatus(cardId, newStatus);
                }
            });
        });

        function getDragAfterElement(container, y) {
            const draggableElements = [...container.querySelectorAll('.kanban-card:not(.dragging)')];
            return draggableElements.reduce((closest, child) => {
                const box = child.getBoundingClientRect();
                const offset = y - box.top - box.height / 2;
                if (offset < 0 && offset > closest.offset) {
                    return { offset: offset, element: child };
                } else {
                    return closest;
                }
            }, { offset: Number.NEGATIVE_INFINITY }).element;
        }
    });

    function updateLeadStatus(id, status) {
        const formData = new FormData();
        formData.append('id', id);
        formData.append('status', status);

        fetch('update-inquiry-status.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // Dynamically move card in DOM if updated via WhatsApp modal trigger
                const card = document.querySelector(`.kanban-card[data-id="${id}"]`);
                const targetContainer = document.querySelector(`.kanban-cards-container[data-status="${status}"]`);
                if (card && targetContainer && !card.classList.contains('dragging')) {
                    targetContainer.appendChild(card);
                }
                updateColumnBadges();
            } else {
                alert(data.error || 'Failed to update status.');
                window.location.reload();
            }
        })
        .catch(err => {
            console.error(err);
            window.location.reload();
        });
    }

    function updateColumnBadges() {
        document.querySelectorAll('.kanban-column').forEach(col => {
            const countBadge = col.querySelector('.kanban-column-count');
            const cardCount = col.querySelectorAll('.kanban-card').length;
            if (countBadge) {
                countBadge.textContent = cardCount;
            }
        });
    }
    </script>
    <?php endif; ?>

    <style>
    /* Premium Edit Modals */
    .edit-modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        background: rgba(10, 46, 36, 0.4);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 3000;
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    .edit-modal.active {
        display: flex;
        opacity: 1;
    }
    .edit-modal-content {
        background: var(--color-bg-panel);
        border: 1px solid var(--color-panel-border);
        border-radius: 12px;
        width: 90%;
        max-width: 480px;
        padding: 30px;
        box-shadow: 0 15px 35px rgba(0,0,0,0.15);
        transform: translateY(20px);
        transition: transform 0.3s ease;
    }
    .edit-modal.active .edit-modal-content {
        transform: translateY(0);
    }
    .edit-modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        border-bottom: 1px solid var(--color-panel-border);
        padding-bottom: 12px;
    }
    .edit-modal-title {
        font-family: var(--font-title);
        font-size: 1.2rem;
        color: var(--color-primary);
        margin: 0;
    }
    .edit-modal-close {
        border: none;
        background: none;
        font-size: 1.2rem;
        color: var(--color-gray);
        cursor: pointer;
        transition: color 0.2s;
    }
    .edit-modal-close:hover {
        color: var(--color-primary);
    }
    </style>

    <!-- Edit Category Modal -->
    <div id="edit-category-modal" class="edit-modal">
        <div class="edit-modal-content">
            <div class="edit-modal-header">
                <h4 class="edit-modal-title"><i class="fa-solid fa-layer-group" style="color: var(--color-accent); margin-right: 8px;"></i> Edit Category</h4>
                <button type="button" class="edit-modal-close" onclick="closeEditModal('edit-category-modal')">&times;</button>
            </div>
            <form action="index.php?tab=settings&section=collections" method="POST">
                <input type="hidden" name="form_action" value="edit_category">
                <input type="hidden" name="cat_id" id="edit_cat_id">
                
                <div class="form-group" style="margin-bottom: 15px;">
                    <label for="edit_cat_name">Category Name</label>
                    <input type="text" id="edit_cat_name" name="cat_name" class="input-control" required placeholder="e.g. Armchairs" oninput="document.getElementById('edit_cat_slug').value = this.value.toLowerCase().replace(/[^a-z0-9]/g, '-').replace(/-+/g, '-');">
                </div>
                
                <div class="form-group" style="margin-bottom: 15px;">
                    <label for="edit_cat_slug">Category Slug</label>
                    <input type="text" id="edit_cat_slug" name="cat_slug" class="input-control" required placeholder="e.g. armchairs">
                </div>
                
                <div class="form-group" style="margin-bottom: 20px;">
                    <label for="edit_cat_bg_color">Section Background Color (Pastel)</label>
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <input type="text" id="edit_cat_bg_color" name="cat_bg_color" class="input-control" placeholder="e.g. #FAF9F6">
                        <input type="color" id="edit_cat_bg_picker" class="input-control" style="width: 45px; height: 42px; padding: 2px; border-radius: 6px; cursor: pointer; border: 1px solid var(--color-panel-border);" oninput="document.getElementById('edit_cat_bg_color').value = this.value;">
                    </div>
                </div>
                
                <button type="submit" class="action-btn" style="width: 100%; justify-content: center;">
                    <i class="fa-solid fa-circle-check"></i> Save Changes
                </button>
            </form>
        </div>
    </div>

    <!-- Edit Material Modal -->
    <div id="edit-material-modal" class="edit-modal">
        <div class="edit-modal-content">
            <div class="edit-modal-header">
                <h4 class="edit-modal-title"><i class="fa-solid fa-cubes" style="color: var(--color-accent); margin-right: 8px;"></i> Edit Material</h4>
                <button type="button" class="edit-modal-close" onclick="closeEditModal('edit-material-modal')">&times;</button>
            </div>
            <form action="index.php?tab=settings&section=collections" method="POST">
                <input type="hidden" name="form_action" value="edit_material">
                <input type="hidden" name="mat_id" id="edit_mat_id">
                
                <div class="form-group" style="margin-bottom: 15px;">
                    <label for="edit_mat_name">Material Name</label>
                    <input type="text" id="edit_mat_name" name="mat_name" class="input-control" required placeholder="e.g. Teak Wood" oninput="document.getElementById('edit_mat_slug').value = this.value.toLowerCase().replace(/[^a-z0-9]/g, '-').replace(/-+/g, '-');">
                </div>
                
                <div class="form-group" style="margin-bottom: 20px;">
                    <label for="edit_mat_slug">Material Slug</label>
                    <input type="text" id="edit_mat_slug" name="mat_slug" class="input-control" required placeholder="e.g. teak-wood">
                </div>
                
                <button type="submit" class="action-btn" style="width: 100%; justify-content: center;">
                    <i class="fa-solid fa-circle-check"></i> Save Changes
                </button>
            </form>
        </div>
    </div>

    <!-- Edit Color Modal -->
    <div id="edit-color-modal" class="edit-modal">
        <div class="edit-modal-content">
            <div class="edit-modal-header">
                <h4 class="edit-modal-title"><i class="fa-solid fa-palette" style="color: var(--color-accent); margin-right: 8px;"></i> Edit Color</h4>
                <button type="button" class="edit-modal-close" onclick="closeEditModal('edit-color-modal')">&times;</button>
            </div>
            <form action="index.php?tab=settings&section=collections" method="POST">
                <input type="hidden" name="form_action" value="edit_color">
                <input type="hidden" name="color_id" id="edit_color_id">
                
                <div class="form-group" style="margin-bottom: 15px;">
                    <label for="edit_color_name">Color Name</label>
                    <input type="text" id="edit_color_name" name="color_name" class="input-control" required placeholder="e.g. Amber Gold">
                </div>
                
                <div class="form-group" style="margin-bottom: 20px;">
                    <label for="edit_color_hex">Color HEX Code & Picker</label>
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <input type="text" id="edit_color_hex" name="color_hex" class="input-control" required placeholder="#ffffff" pattern="^#([A-Fa-f0-9]{6})$" title="Must be a valid hex color code starting with #, followed by 6 hex characters.">
                        <input type="color" id="edit_color_picker" class="input-control" style="width: 45px; height: 42px; padding: 2px; border-radius: 6px; cursor: pointer; border: 1px solid var(--color-panel-border);" oninput="document.getElementById('edit_color_hex').value = this.value;">
                    </div>
                </div>
                
                <button type="submit" class="action-btn" style="width: 100%; justify-content: center;">
                    <i class="fa-solid fa-circle-check"></i> Save Changes
                </button>
            </form>
        </div>
    </div>

    <!-- Edit Brand Modal -->
    <div id="edit-brand-modal" class="edit-modal">
        <div class="edit-modal-content">
            <div class="edit-modal-header">
                <h4 class="edit-modal-title"><i class="fa-solid fa-certificate" style="color: var(--color-accent); margin-right: 8px;"></i> Edit Brand</h4>
                <button type="button" class="edit-modal-close" onclick="closeEditModal('edit-brand-modal')">&times;</button>
            </div>
            <form action="index.php?tab=settings&section=collections" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="form_action" value="edit_brand">
                <input type="hidden" name="brand_id" id="edit_brand_id">
                
                <div class="form-group" style="margin-bottom: 15px;">
                    <label for="edit_brand_name">Brand Name</label>
                    <input type="text" id="edit_brand_name" name="brand_name" class="input-control" required placeholder="e.g. Aethera Studio">
                </div>
                
                <div class="form-group" style="margin-bottom: 15px;">
                    <label>Upload New Logo Image (PNG / JPG / WEBP)</label>
                    <div class="upload-container" onclick="document.getElementById('edit_logo_file').click();" style="padding: 15px;">
                        <i class="fa-solid fa-cloud-arrow-up upload-icon"></i>
                        <div class="upload-text"><strong>Click to Upload Logo File</strong></div>
                    </div>
                    <input type="file" id="edit_logo_file" name="logo_file" class="upload-file-input" accept="image/*" style="display: none;">
                </div>
                
                <div class="form-group" style="margin-bottom: 20px;">
                    <label for="edit_logo_url">Or Logo Image URL Path</label>
                    <input type="text" id="edit_logo_url" name="logo_url" class="input-control" placeholder="assets/images/logo_client.png">
                </div>
                
                <button type="submit" class="action-btn" style="width: 100%; justify-content: center;">
                    <i class="fa-solid fa-circle-check"></i> Save Changes
                </button>
            </form>
        </div>
    </div>

    <script>
    function openEditModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'flex';
            setTimeout(() => {
                modal.classList.add('active');
            }, 10);
        }
    }

    function closeEditModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('active');
            setTimeout(() => {
                modal.style.display = 'none';
            }, 300);
        }
    }

    function openEditCategoryModal(id, name, slug, bgColor) {
        document.getElementById('edit_cat_id').value = id;
        document.getElementById('edit_cat_name').value = name;
        document.getElementById('edit_cat_slug').value = slug;
        document.getElementById('edit_cat_bg_color').value = bgColor;
        if (bgColor && bgColor.startsWith('#')) {
            document.getElementById('edit_cat_bg_picker').value = bgColor;
        }
        openEditModal('edit-category-modal');
    }

    function openEditMaterialModal(id, name, slug) {
        document.getElementById('edit_mat_id').value = id;
        document.getElementById('edit_mat_name').value = name;
        document.getElementById('edit_mat_slug').value = slug;
        openEditModal('edit-material-modal');
    }

    function openEditColorModal(id, name, hex) {
        document.getElementById('edit_color_id').value = id;
        document.getElementById('edit_color_name').value = name;
        document.getElementById('edit_color_hex').value = hex;
        if (hex && hex.startsWith('#')) {
            document.getElementById('edit_color_picker').value = hex;
        }
        openEditModal('edit-color-modal');
    }

    function openEditBrandModal(id, name, logoPath) {
        document.getElementById('edit_brand_id').value = id;
        document.getElementById('edit_brand_name').value = name;
        document.getElementById('edit_logo_url').value = logoPath;
        openEditModal('edit-brand-modal');
    }

    // Close modal when clicking outside contents
    window.addEventListener('click', (e) => {
        if (e.target.classList.contains('edit-modal')) {
            closeEditModal(e.target.id);
        }
    });
    </script>
    <!-- Import Database Modal -->
    <div id="importDbModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 9999; align-items: center; justify-content: center;">
        <div style="background: #ffffff; padding: 30px; border-radius: 12px; max-width: 480px; width: 90%; box-shadow: 0 10px 30px rgba(0,0,0,0.3); position: relative;">
            <button type="button" onclick="document.getElementById('importDbModal').style.display='none'" style="position: absolute; top: 15px; right: 15px; background: none; border: none; font-size: 1.4rem; cursor: pointer; color: #888;">&times;</button>
            <h3 style="margin-top: 0; font-size: 1.25rem; font-weight: 600; color: #1a1a1a; display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-database" style="color: #3498db;"></i> Import & Restore Database
            </h3>
            <p style="color: #666; font-size: 0.9rem; margin-top: 10px; margin-bottom: 20px; line-height: 1.4;">
                Select a <code>.sql</code> backup file to restore database tables.
                <br><strong style="color: #e74c3c;">Note:</strong> Existing tables will be updated with data from the SQL file.
            </p>
            <form action="import-db.php" method="POST" enctype="multipart/form-data">
                <div class="form-group" style="margin-bottom: 20px;">
                    <label for="sql_file" style="display: block; font-weight: 500; margin-bottom: 8px; font-size: 0.9rem;">Select SQL File (*.sql)</label>
                    <input type="file" id="sql_file" name="sql_file" accept=".sql" required class="input-control" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 6px; font-size: 0.9rem;">
                </div>
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" onclick="document.getElementById('importDbModal').style.display='none'" class="action-btn" style="background: #f1f2f6; color: #333; border: 1px solid #ccc; cursor: pointer;">Cancel</button>
                    <button type="submit" class="action-btn" style="background: #3498db; border-color: #2980b9; color: #fff; cursor: pointer;">
                        <i class="fa-solid fa-upload"></i> Upload & Import
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- System Documentation Type Selection Modal -->
    <div id="docsModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.65); z-index: 9999; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
        <div style="background: #ffffff; padding: 35px 30px; border-radius: 20px; max-width: 520px; width: 90%; box-shadow: 0 20px 40px rgba(0,0,0,0.3); position: relative; text-align: center;">
            <button type="button" onclick="document.getElementById('docsModal').style.display='none'" style="position: absolute; top: 16px; right: 18px; background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #999;">&times;</button>
            
            <div style="width: 60px; height: 60px; border-radius: 50%; background: rgba(10, 46, 36, 0.08); color: #0A2E24; display: inline-flex; align-items: center; justify-content: center; font-size: 1.8rem; margin-bottom: 15px;">
                <i class="fa-solid fa-file-pdf" style="color: #D4AF37;"></i>
            </div>

            <h3 style="font-size: 1.35rem; font-weight: 700; color: #1a1a1a; margin-bottom: 8px;">Generate System Documentation</h3>
            <p style="color: #666; font-size: 0.9rem; margin-bottom: 25px;">Please select the documentation suite format to generate or print to PDF:</p>
            
            <div style="display: flex; flex-direction: column; gap: 15px; text-align: left;">
                <!-- Developer Docs Option -->
                <a href="generate-docs.php?type=developer" target="_blank" onclick="document.getElementById('docsModal').style.display='none'" style="display: flex; align-items: center; gap: 16px; padding: 18px; background: #f8f9fa; border: 1.5px solid #e2e8f0; border-radius: 14px; text-decoration: none; transition: all 0.25s ease;">
                    <div style="width: 48px; height: 48px; border-radius: 12px; background: #0A2E24; color: #D4AF37; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; shrink: 0;">
                        <i class="fa-solid fa-code"></i>
                    </div>
                    <div style="flex: 1;">
                        <strong style="display: block; font-size: 1.02rem; color: #0A2E24; font-weight: 700;">For Developers</strong>
                        <span style="font-size: 0.82rem; color: #64748b; line-height: 1.4; display: block; margin-top: 2px;">Technical architecture specs, Mermaid data flow diagrams, database schemas & code module maps.</span>
                    </div>
                    <i class="fa-solid fa-chevron-right" style="color: #94a3b8; font-size: 0.9rem;"></i>
                </a>

                <!-- Admin User Guide Option -->
                <a href="generate-docs.php?type=admin" target="_blank" onclick="document.getElementById('docsModal').style.display='none'" style="display: flex; align-items: center; gap: 16px; padding: 18px; background: #f8f9fa; border: 1.5px solid #e2e8f0; border-radius: 14px; text-decoration: none; transition: all 0.25s ease;">
                    <div style="width: 48px; height: 48px; border-radius: 12px; background: #2563eb; color: #ffffff; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; shrink: 0;">
                        <i class="fa-solid fa-user-gear"></i>
                    </div>
                    <div style="flex: 1;">
                        <strong style="display: block; font-size: 1.02rem; color: #1e40af; font-weight: 700;">For Admin & Management</strong>
                        <span style="font-size: 0.82rem; color: #64748b; line-height: 1.4; display: block; margin-top: 2px;">Operational user guide, catalog management, business stats, price analytics & DB backup/import manuals.</span>
                    </div>
                    <i class="fa-solid fa-chevron-right" style="color: #94a3b8; font-size: 0.9rem;"></i>
                </a>
            </div>
        </div>
    </div>

    <script>
    // Bulk Delete Selection JS
    function toggleSelectAllProducts(master) {
        const checkboxes = document.querySelectorAll('.product-select-chk');
        checkboxes.forEach(chk => {
            chk.checked = master.checked;
        });
        const headerChk = document.getElementById('headerSelectAll');
        const masterChk = document.getElementById('selectAllProducts');
        if (headerChk) headerChk.checked = master.checked;
        if (masterChk) masterChk.checked = master.checked;
        updateBulkDeleteState();
    }

    function updateBulkDeleteState() {
        const checkboxes = document.querySelectorAll('.product-select-chk');
        const selected = document.querySelectorAll('.product-select-chk:checked');
        const count = selected.length;

        const bulkBtn = document.getElementById('bulkDeleteBtn');
        const badge = document.getElementById('selectedCountBadge');
        const btnCount = document.getElementById('btnSelectedCount');

        if (badge) badge.textContent = count + ' selected';
        if (btnCount) btnCount.textContent = count;

        if (bulkBtn) {
            if (count > 0) {
                bulkBtn.style.display = 'inline-flex';
            } else {
                bulkBtn.style.display = 'none';
            }
        }

        const headerChk = document.getElementById('headerSelectAll');
        const masterChk = document.getElementById('selectAllProducts');
        if (checkboxes.length > 0) {
            const allChecked = (count === checkboxes.length);
            if (headerChk) headerChk.checked = allChecked;
            if (masterChk) masterChk.checked = allChecked;
        }
    }

    function confirmBulkDelete() {
        const count = document.querySelectorAll('.product-select-chk:checked').length;
        if (count === 0) {
            alert('Please select at least one creation to delete.');
            return false;
        }
        return confirm('WARNING: Are you sure you want to delete ' + count + ' selected creation(s)?\n\nThis will permanently delete the records from the database and remove all associated image files from the server disk.');
    }

    // Mobile Sidebar Off-Canvas Navigation Toggle
    document.addEventListener('DOMContentLoaded', function() {
        const toggleBtn = document.getElementById('mobileNavToggle');
        const sidebar = document.querySelector('.admin-sidebar');
        const backdrop = document.getElementById('sidebarBackdrop');

        function toggleSidebar() {
            if (!sidebar) return;
            const isOpen = sidebar.classList.contains('mobile-open');
            if (isOpen) {
                closeSidebar();
            } else {
                openSidebar();
            }
        }

        function openSidebar() {
            if (sidebar) sidebar.classList.add('mobile-open');
            if (backdrop) backdrop.classList.add('active');
            if (toggleBtn) toggleBtn.innerHTML = '<i class="fa-solid fa-xmark"></i>';
            document.body.style.overflow = 'hidden';
        }

        function closeSidebar() {
            if (sidebar) sidebar.classList.remove('mobile-open');
            if (backdrop) backdrop.classList.remove('active');
            if (toggleBtn) toggleBtn.innerHTML = '<i class="fa-solid fa-bars"></i>';
            document.body.style.overflow = '';
        }

        if (toggleBtn) toggleBtn.addEventListener('click', toggleSidebar);
        if (backdrop) backdrop.addEventListener('click', closeSidebar);

        // Close sidebar when clicking any navigation link on mobile
        document.querySelectorAll('.sidebar-link').forEach(link => {
            link.addEventListener('click', closeSidebar);
        });
    });

    // Close modals when clicking backdrop
    window.addEventListener('click', (e) => {
        const importModal = document.getElementById('importDbModal');
        const docsModal = document.getElementById('docsModal');
        if (e.target === importModal) {
            importModal.style.display = 'none';
        }
        if (e.target === docsModal) {
            docsModal.style.display = 'none';
        }
    });
    </script>
    <!-- Real-Time Chart.js Initialization -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // 1. Category Distribution Doughnut Chart
        const catCanvas = document.getElementById('categoryChart');
        if (catCanvas && typeof Chart !== 'undefined') {
            const catCtx = catCanvas.getContext('2d');
            new Chart(catCtx, {
                type: 'doughnut',
                data: {
                    labels: <?php echo json_encode($analytics_cat_labels); ?>,
                    datasets: [{
                        data: <?php echo json_encode($analytics_cat_values); ?>,
                        backgroundColor: ['#D97706', '#10B981', '#3B82F6', '#8B5CF6', '#EC4899', '#F59E0B', '#6366F1'],
                        borderWidth: 2,
                        borderColor: '#111A15'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { color: '#9CA3AF', font: { family: 'Inter', size: 12 } }
                        }
                    }
                }
            });
        }

        // 2. Material Breakdown Bar Chart
        const matCanvas = document.getElementById('materialChart');
        if (matCanvas && typeof Chart !== 'undefined') {
            const matCtx = matCanvas.getContext('2d');
            new Chart(matCtx, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode($analytics_mat_labels); ?>,
                    datasets: [{
                        label: 'Products Count',
                        data: <?php echo json_encode($analytics_mat_values); ?>,
                        backgroundColor: 'rgba(217, 119, 6, 0.75)',
                        borderColor: '#D97706',
                        borderWidth: 1.5,
                        borderRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(255, 255, 255, 0.05)' },
                            ticks: { color: '#9CA3AF', stepSize: 1 }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { color: '#9CA3AF' }
                        }
                    },
                    plugins: {
                        legend: { display: false }
                    }
                }
            });
        }
    });
    </script>
</body>
</html>
