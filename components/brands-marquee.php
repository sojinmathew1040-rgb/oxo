<?php
/**
 * Partner Brand Logos Infinite Marquee Ribbon
 * Shows only visual brand logos (ignoring text-only fallbacks).
 */

require_once __DIR__ . '/../includes/db.php';
$db = get_db_connection();
$brands = [];

if ($db) {
    try {
        // Fetch all brand listings
        $stmt = $db->query("SELECT * FROM `oxo_brands` ORDER BY `created_at` DESC");
        $all_brands = $stmt->fetchAll();
        
        // Filter out brands that do not have an image logo path
        foreach ($all_brands as $b) {
            if (!empty($b['logo_path'])) {
                $brands[] = $b;
            }
        }
    } catch (\Exception $e) {
        error_log("Failed to load brands for marquee: " . $e->getMessage());
    }
}

// If no brands with logos are registered, hide the marquee
if (empty($brands)) {
    return;
}

$brands_count = count($brands);
?>
<section class="brands-marquee-section">
    <div class="marquee-container">
        <!-- Pass the logo-only brands count to CSS variable for scroll loop offsets -->
        <div class="marquee-track" style="--brands-count: <?php echo $brands_count; ?>;">
            
            <!-- Loop 1: Render actual logo images + Brand Names -->
            <?php foreach ($brands as $b): ?>
                <div class="marquee-item">
                    <div class="marquee-logo-box">
                        <img src="<?php echo htmlspecialchars($b['logo_path']); ?>" alt="<?php echo htmlspecialchars($b['name']); ?>" class="marquee-logo-img" onerror="this.style.display='none';">
                    </div>
                    <span class="marquee-brand-name"><?php echo htmlspecialchars($b['name']); ?></span>
                </div>
            <?php endforeach; ?>
            
            <!-- Loop 2: Duplicate items for seamless infinite wrap -->
            <?php foreach ($brands as $b): ?>
                <div class="marquee-item">
                    <div class="marquee-logo-box">
                        <img src="<?php echo htmlspecialchars($b['logo_path']); ?>" alt="<?php echo htmlspecialchars($b['name']); ?>" class="marquee-logo-img" onerror="this.style.display='none';">
                    </div>
                    <span class="marquee-brand-name"><?php echo htmlspecialchars($b['name']); ?></span>
                </div>
            <?php endforeach; ?>
            
        </div>
    </div>
</section>
