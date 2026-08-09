<?php
require_once 'includes/db.php';

// Route if not logged in
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

$pdo = get_db_connection();
if (!$pdo) {
    die("Database connection offline.");
}

// Fetch user info
$stmt = $pdo->prepare("SELECT * FROM `oxo_users` WHERE `id` = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header("Location: logout.php");
    exit;
}

// Handle Profile Updates
$profile_msg = '';
$profile_err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    
    try {
        $update_stmt = $pdo->prepare("UPDATE `oxo_users` SET `phone` = ?, `address` = ? WHERE `id` = ?");
        $update_stmt->execute([$phone, $address, $user['id']]);
        
        $profile_msg = "Profile updated successfully.";
        // Refresh local info
        $user['phone'] = $phone;
        $user['address'] = $address;
    } catch (PDOException $e) {
        $profile_err = "Error updating profile: " . $e->getMessage();
    }
}

// Fetch Bespoke Design Consultations using email
$stmt = $pdo->prepare("SELECT * FROM `oxo_consultations` WHERE `email` = ? ORDER BY `id` DESC");
$stmt->execute([$user['email']]);
$consultations = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<style>
    /* Premium overrides for account.php in OXO */
    body {
        background: var(--color-secondary);
        padding-top: var(--header-height);
    }

    .pro-acc-wrap {
        max-width: 1200px;
        margin: 60px auto 100px;
        padding: 0 40px;
    }

    .pro-acc-header {
        margin-bottom: 60px;
        text-align: center;
    }

    .pro-acc-header h1 {
        font-family: var(--font-title);
        font-size: 48px;
        font-weight: 700;
        color: var(--color-black);
        letter-spacing: -0.04em;
        margin-bottom: 10px;
        text-transform: uppercase;
    }

    .pro-acc-header p {
        font-family: var(--font-body);
        font-size: 16px;
        color: var(--color-gray);
    }

    .pro-bento-grid {
        display: grid;
        grid-template-columns: 360px 1fr;
        gap: 40px;
        align-items: start;
    }

    .pro-bento-profile {
        background: var(--color-white);
        border-radius: 24px;
        padding: 40px;
        box-shadow: 0 20px 50px rgba(10, 46, 36, 0.04);
        text-align: center;
        border: 1px solid rgba(10, 46, 36, 0.02);
        position: sticky;
        top: 120px;
    }

    .pro-avatar {
        width: 90px;
        height: 90px;
        background: linear-gradient(135deg, var(--color-accent), var(--color-primary));
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-family: var(--font-title);
        font-size: 36px;
        font-weight: 700;
        color: var(--color-white);
        margin-bottom: 24px;
        box-shadow: 0 10px 25px rgba(200, 162, 118, 0.3);
    }

    .pro-name {
        font-family: var(--font-title);
        font-size: 22px;
        font-weight: 700;
        color: var(--color-black);
        letter-spacing: -0.01em;
        margin-bottom: 6px;
    }

    .pro-email {
        font-family: var(--font-body);
        font-size: 14px;
        color: var(--color-gray);
        margin-bottom: 30px;
    }

    .pro-user-info-section {
        border-top: 1px solid rgba(10, 46, 36, 0.08);
        padding-top: 25px;
        margin-bottom: 30px;
        text-align: left;
    }

    .pro-user-info-item {
        margin-bottom: 18px;
    }

    .pro-user-info-label {
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: var(--color-accent-green);
        margin-bottom: 4px;
    }

    .pro-user-info-value {
        font-size: 14px;
        color: var(--color-black);
        word-break: break-all;
    }

    .pro-btn-group {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .pro-btn {
        padding: 16px;
        border-radius: 30px;
        font-size: 15px;
        font-weight: 600;
        text-decoration: none;
        text-align: center;
        transition: all 0.3s var(--transition-smooth);
        cursor: pointer;
    }

    .pro-btn-dark {
        background: var(--color-primary);
        color: var(--color-white);
    }

    .pro-btn-dark:hover {
        background: var(--color-accent-green);
        transform: translateY(-2px);
    }

    .pro-btn-light {
        background: rgba(10, 46, 36, 0.04);
        color: var(--color-black);
    }

    .pro-btn-light:hover {
        background: rgba(255, 59, 48, 0.1);
        color: #ff3b30;
        transform: translateY(-2px);
    }

    .pro-btn-outline {
        background: transparent;
        border: 1px solid rgba(10, 46, 36, 0.15);
        color: var(--color-black);
    }

    .pro-btn-outline:hover {
        border-color: var(--color-primary);
        background: rgba(10, 46, 36, 0.02);
    }

    .pro-bento-orders {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .pro-orders-header {
        font-family: var(--font-title);
        font-size: 26px;
        font-weight: 700;
        color: var(--color-black);
        letter-spacing: -0.02em;
        margin-bottom: 10px;
    }

    .pro-order-card {
        background: var(--color-white);
        border-radius: 20px;
        padding: 30px;
        box-shadow: 0 15px 40px rgba(10, 46, 36, 0.03);
        border: 1px solid rgba(10, 46, 36, 0.02);
        transition: all 0.3s var(--transition-smooth);
    }

    .pro-order-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 20px 50px rgba(10, 46, 36, 0.06);
    }

    .pro-order-top {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid rgba(10, 46, 36, 0.08);
        padding-bottom: 20px;
        margin-bottom: 20px;
    }

    .pro-order-id {
        font-family: var(--font-title);
        font-size: 18px;
        font-weight: 700;
        color: var(--color-black);
        margin-bottom: 4px;
    }

    .pro-order-date {
        font-size: 13px;
        color: var(--color-gray);
    }

    .pro-order-status {
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .status-completed {
        background: rgba(40, 167, 69, 0.08);
        color: #28a745;
        border: 1px solid rgba(40, 167, 69, 0.15);
    }

    .status-pending {
        background: rgba(200, 162, 118, 0.1);
        color: #8c673b;
        border: 1px solid rgba(200, 162, 118, 0.2);
    }

    .pro-order-detail-row {
        margin-bottom: 10px;
    }

    .pro-order-detail-label {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        color: var(--color-accent-green);
        letter-spacing: 0.05em;
        margin-bottom: 6px;
    }

    .pro-order-message {
        font-size: 14px;
        color: var(--color-black);
        line-height: 1.6;
        background: rgba(10, 46, 36, 0.02);
        padding: 16px 20px;
        border-radius: 12px;
        border-left: 3px solid var(--color-accent);
    }

    .pro-alert {
        padding: 15px;
        border-radius: 12px;
        font-size: 14px;
        margin-bottom: 20px;
    }
    
    .pro-alert-success {
        background: rgba(40, 167, 69, 0.08);
        color: #28a745;
        border: 1px solid rgba(40, 167, 69, 0.15);
    }
    
    .pro-alert-danger {
        background: rgba(255, 59, 48, 0.08);
        color: #ff3b30;
        border: 1px solid rgba(255, 59, 48, 0.15);
    }

    /* Modal dialog style */
    .pro-modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(10, 46, 36, 0.4);
        backdrop-filter: blur(4px);
        z-index: 1000;
        display: none;
        align-items: center;
        justify-content: center;
    }

    .pro-modal {
        background: var(--color-white);
        border-radius: 20px;
        padding: 40px;
        width: 100%;
        max-width: 440px;
        box-shadow: 0 30px 60px rgba(10, 46, 36, 0.15);
        border: 1px solid rgba(10, 46, 36, 0.05);
        position: relative;
    }

    .pro-modal-title {
        font-family: var(--font-title);
        font-size: 24px;
        font-weight: 700;
        color: var(--color-black);
        margin-bottom: 20px;
    }

    .pro-modal-input-group {
        margin-bottom: 20px;
        text-align: left;
    }

    .pro-modal-input-group label {
        display: block;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        color: var(--color-black);
        margin-bottom: 8px;
        letter-spacing: 0.05em;
    }

    .pro-modal-input-group input,
    .pro-modal-input-group textarea {
        width: 100%;
        padding: 14px 18px;
        border: 1px solid rgba(10, 46, 36, 0.1);
        border-radius: 10px;
        background: rgba(10, 46, 36, 0.02);
        color: var(--color-black);
        box-sizing: border-box;
        font-family: var(--font-body);
        font-size: 14px;
        resize: none;
    }

    .pro-modal-input-group input:focus,
    .pro-modal-input-group textarea:focus {
        border-color: var(--color-primary);
        background: var(--color-white);
    }

    @media(max-width: 992px) {
        .pro-acc-wrap {
            padding: 0 20px;
        }

        .pro-bento-grid {
            grid-template-columns: 1fr;
        }

        .pro-bento-profile {
            position: static;
        }
    }

    @media(max-width: 768px) {
        .pro-acc-wrap {
            padding: 0 16px;
            margin: 30px auto 60px;
        }
        .pro-acc-header {
            margin-bottom: 35px;
        }
        .pro-acc-header h1 {
            font-size: clamp(1.8rem, 6vw, 2.8rem);
        }
        .pro-bento-profile {
            padding: 24px 18px;
            border-radius: 20px;
        }
        .pro-order-card {
            padding: 20px 16px;
            border-radius: 16px;
        }
        .pro-order-top {
            flex-direction: column;
            align-items: flex-start;
            gap: 10px;
        }
        .pro-modal {
            padding: 24px 18px;
            max-width: 90vw;
            border-radius: 16px;
        }
    }
</style>

<div class="pro-acc-wrap">
    <div class="pro-acc-header">
        <h1>Overview.</h1>
        <p>Manage your luxury profile and check bespoke consultation requests.</p>
    </div>

    <!-- Alert Messages -->
    <?php if ($profile_msg): ?>
        <div class="pro-alert pro-alert-success"><?= htmlspecialchars($profile_msg) ?></div>
    <?php endif; ?>
    <?php if ($profile_err): ?>
        <div class="pro-alert pro-alert-danger"><?= htmlspecialchars($profile_err) ?></div>
    <?php endif; ?>

    <div class="pro-bento-grid">
        <!-- User Profile Panel -->
        <div class="pro-bento-profile">
            <div class="pro-avatar"><?= strtoupper(substr(htmlspecialchars($user['name']), 0, 1)) ?></div>
            <div class="pro-name"><?= htmlspecialchars($user['name']) ?></div>
            <div class="pro-email"><?= htmlspecialchars($user['email']) ?></div>

            <div class="pro-user-info-section">
                <div class="pro-user-info-item">
                    <div class="pro-user-info-label">Phone Number</div>
                    <div class="pro-user-info-value">
                        <?= !empty($user['phone']) ? htmlspecialchars($user['phone']) : '— Not Provided —' ?>
                    </div>
                </div>
                <div class="pro-user-info-item">
                    <div class="pro-user-info-label">Shipping / Project Address</div>
                    <div class="pro-user-info-value">
                        <?= !empty($user['address']) ? nl2br(htmlspecialchars($user['address'])) : '— Not Provided —' ?>
                    </div>
                </div>
            </div>

            <div class="pro-btn-group">
                <button onclick="openEditProfileModal()" class="pro-btn pro-btn-outline">Edit Profile</button>
                <a href="shop.php" class="pro-btn pro-btn-dark">Explore Catalog</a>
                <a href="logout.php" class="pro-btn pro-btn-light">Sign Out</a>
            </div>
        </div>

        <!-- Consultations History Panel -->
        <div class="pro-bento-orders">
            <div class="pro-orders-header">Consultation History</div>

            <?php if (empty($consultations)): ?>
                <div style="background: var(--color-white); padding: 80px 40px; border-radius: 24px; text-align: center; box-shadow: 0 15px 40px rgba(10,46,36,0.03); border: 1px solid rgba(10,46,36,0.02);">
                    <h3 style="font-family: var(--font-title); font-size: 22px; font-weight: 700; color: var(--color-black); margin-bottom: 12px;">No inquiries placed yet.</h3>
                    <p style="color: var(--color-gray); margin-bottom: 30px; font-size: 15px;">Discover our bespoke collections or request an design review.</p>
                    <a href="shop.php" class="pro-btn pro-btn-dark" style="display: inline-block; padding: 16px 30px;">Browse Products</a>
                </div>
            <?php else: ?>
                <?php foreach ($consultations as $inquiry): ?>
                    <div class="pro-order-card">
                        <div class="pro-order-top">
                            <div>
                                <div class="pro-order-id"><?= htmlspecialchars($inquiry['product_title']) ?></div>
                                <div class="pro-order-date"><?= date('F j, Y, g:i a', strtotime($inquiry['created_at'])) ?></div>
                            </div>
                            <?php
                            $status_class = 'status-pending';
                            if (strtolower($inquiry['status']) === 'responded' || strtolower($inquiry['status']) === 'completed') {
                                $status_class = 'status-completed';
                            }
                            ?>
                            <div class="pro-order-status <?= $status_class ?>"><?= htmlspecialchars($inquiry['status']) ?></div>
                        </div>

                        <div class="pro-order-detail-row">
                            <div class="pro-order-detail-label">Your message</div>
                            <div class="pro-order-message"><?= nl2br(htmlspecialchars($inquiry['message'])) ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Edit Profile Modal -->
<div class="pro-modal-overlay" id="editProfileModal">
    <div class="pro-modal">
        <h3 class="pro-modal-title">Edit Details</h3>
        
        <form method="POST" action="account.php">
            <input type="hidden" name="action" value="update_profile">
            
            <div class="pro-modal-input-group">
                <label>Phone Number</label>
                <input type="text" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder="e.g. +91 9876543210">
            </div>
            
            <div class="pro-modal-input-group">
                <label>Project / Shipping Address</label>
                <textarea name="address" rows="4" placeholder="Enter your full address here..."><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
            </div>
            
            <div style="display: flex; gap: 12px; margin-top: 30px;">
                <button type="button" onclick="closeEditProfileModal()" class="pro-btn pro-btn-light" style="flex: 1; padding: 14px;">Cancel</button>
                <button type="submit" class="pro-btn pro-btn-dark" style="flex: 1; padding: 14px;">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openEditProfileModal() {
        document.getElementById('editProfileModal').style.display = 'flex';
    }

    function closeEditProfileModal() {
        document.getElementById('editProfileModal').style.display = 'none';
    }

    // Close modal when clicking outside
    window.addEventListener('click', function(e) {
        const modal = document.getElementById('editProfileModal');
        if (e.target === modal) {
            closeEditProfileModal();
        }
    });
</script>

<?php include 'includes/footer.php'; ?>
