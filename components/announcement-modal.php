<?php
/**
 * Announcement Poster Pop-up Component for OXO Landing Page
 * Displays active announcement poster when index page loads.
 * If no active poster exists, outputs nothing.
 */
require_once __DIR__ . '/../includes/db.php';

$db = get_db_connection();
$active_poster = null;

if ($db) {
    try {
        $stmt = $db->query("SELECT * FROM `oxo_announcements` WHERE `is_active` = 1 ORDER BY `id` DESC LIMIT 1");
        $active_poster = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (\PDOException $e) {
        $active_poster = null;
    }
}

if ($active_poster && !empty($active_poster['image_path'])):
?>
<!-- Announcement Poster Modal Overlay -->
<div id="announcementModal" class="announcement-modal-overlay">
    <div class="announcement-modal-card">
        <button type="button" class="announcement-modal-close" onclick="closeAnnouncementModal()" aria-label="Close Announcement">&times;</button>
        <div class="announcement-modal-body">
            <?php if (!empty($active_poster['link_url'])): ?>
                <a href="<?php echo htmlspecialchars($active_poster['link_url']); ?>" class="announcement-img-link">
                    <img src="<?php echo htmlspecialchars($active_poster['image_path']); ?>" alt="<?php echo htmlspecialchars($active_poster['title'] ?? 'Announcement'); ?>" class="announcement-poster-img">
                </a>
            <?php else: ?>
                <img src="<?php echo htmlspecialchars($active_poster['image_path']); ?>" alt="<?php echo htmlspecialchars($active_poster['title'] ?? 'Announcement'); ?>" class="announcement-poster-img">
            <?php endif; ?>
            
            <?php if (!empty($active_poster['title']) || !empty($active_poster['subtitle'])): ?>
                <div class="announcement-modal-content">
                    <?php if (!empty($active_poster['title'])): ?>
                        <h3 class="announcement-title"><?php echo htmlspecialchars($active_poster['title']); ?></h3>
                    <?php endif; ?>
                    <?php if (!empty($active_poster['subtitle'])): ?>
                        <p class="announcement-subtitle"><?php echo htmlspecialchars($active_poster['subtitle']); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($active_poster['link_url'])): ?>
                        <a href="<?php echo htmlspecialchars($active_poster['link_url']); ?>" class="announcement-cta-btn">
                            Explore Creation &nbsp; <i class="fa-solid fa-arrow-right-long"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.announcement-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(10, 46, 36, 0.85);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    z-index: 999999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.4s cubic-bezier(0.16, 1, 0.3, 1), visibility 0.4s;
}

.announcement-modal-overlay.show {
    opacity: 1;
    visibility: visible;
}

.announcement-modal-card {
    position: relative;
    width: 100%;
    max-width: 520px;
    background: #FFFFFF;
    border-radius: 28px;
    overflow: hidden;
    box-shadow: 0 35px 90px rgba(0, 0, 0, 0.5);
    border: 1px solid rgba(200, 162, 118, 0.3);
    transform: scale(0.9);
    transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}

.announcement-modal-overlay.show .announcement-modal-card {
    transform: scale(1);
}

.announcement-modal-close {
    position: absolute;
    top: 16px;
    right: 16px;
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: rgba(10, 46, 36, 0.7);
    color: #FFFFFF;
    border: 1px solid rgba(255, 255, 255, 0.3);
    font-size: 24px;
    line-height: 1;
    cursor: pointer;
    z-index: 10;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}

.announcement-modal-close:hover {
    background: #c8a276;
    color: #0A2E24;
    transform: rotate(90deg) scale(1.1);
}

.announcement-modal-body {
    display: flex;
    flex-direction: column;
}

.announcement-img-link {
    display: block;
    overflow: hidden;
}

.announcement-poster-img {
    width: 100%;
    max-height: 460px;
    object-fit: cover;
    display: block;
    transition: transform 0.5s ease;
}

.announcement-img-link:hover .announcement-poster-img {
    transform: scale(1.03);
}

.announcement-modal-content {
    padding: 24px 30px 28px 30px;
    text-align: center;
    background: #FFFFFF;
}

.announcement-title {
    font-family: var(--font-title, 'Cinzel', serif);
    font-size: 1.5rem;
    color: var(--color-primary, #0A2E24);
    font-weight: 700;
    margin: 0 0 8px 0;
    letter-spacing: -0.01em;
}

.announcement-subtitle {
    font-size: 0.92rem;
    color: #555555;
    line-height: 1.6;
    margin: 0 0 20px 0;
    font-family: var(--font-body, sans-serif);
}

.announcement-cta-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 14px 32px;
    background: var(--color-primary, #0A2E24);
    color: #FFFFFF;
    text-decoration: none;
    border-radius: 50px;
    font-weight: 600;
    font-size: 0.82rem;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    transition: all 0.3s ease;
    border: 1px solid var(--color-primary, #0A2E24);
}

.announcement-cta-btn:hover {
    background: #c8a276;
    color: #0A2E24;
    border-color: #c8a276;
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(200, 162, 118, 0.3);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('announcementModal');
    if (!modal) return;
    
    // Automatically display announcement poster modal on load
    setTimeout(() => {
        modal.classList.add('show');
    }, 350);

    // Dismiss on overlay backdrop click
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            closeAnnouncementModal();
        }
    });

    // Dismiss on Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modal.classList.contains('show')) {
            closeAnnouncementModal();
        }
    });
});

function closeAnnouncementModal() {
    const modal = document.getElementById('announcementModal');
    if (modal) {
        modal.classList.remove('show');
        setTimeout(() => {
            modal.style.display = 'none';
        }, 400);
    }
}
</script>
<?php endif; ?>
