<?php
require_once 'includes/db.php';

// Redirect if already logged in
if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true) {
    header("Location: " . (isset($_GET['redirect']) ? htmlspecialchars($_GET['redirect']) : "index.php"));
    exit;
}

$redirect = isset($_GET['redirect']) ? htmlspecialchars($_GET['redirect']) : 'index.php';
$isGoogleConfigured = (defined('GOOGLE_CLIENT_ID') && GOOGLE_CLIENT_ID !== 'YOUR_GOOGLE_CLIENT_ID' && GOOGLE_CLIENT_ID !== '');

function renderGoogleDevWarning()
{
    ?>
    <div class="pro-dev-warning">
        <div class="pro-dev-warning-title">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                stroke-linecap="round" stroke-linejoin="round" style="color: #ff9500; margin-right: 2px;">
                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                <line x1="12" y1="9" x2="12" y2="13"></line>
                <line x1="12" y1="17" x2="12.01" y2="17"></line>
            </svg>
            Google OAuth Setup Required
        </div>
        <div class="pro-dev-warning-text">
            To enable Google Sign-In, configure <code>GOOGLE_CLIENT_ID</code> in <a
                href="includes/db.php" class="pro-dev-link">db.php</a>. Get one from <a
                href="https://console.cloud.google.com/" target="_blank" rel="noopener noreferrer"
                class="pro-dev-link">Google Cloud Console</a>.
            <div style="margin-top: 12px; border-top: 1px dashed rgba(255, 149, 0, 0.15); padding-top: 10px;">
                <a href="#" onclick="simulateGoogleLogin(event)" class="pro-dev-link"
                    style="display: inline-flex; align-items: center; gap: 4px; font-weight: 600;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                        stroke-linecap="round" stroke-linejoin="round" style="color: #ff9500;">
                        <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>
                    </svg>
                    Simulate Google Sign-In (Dev Sandbox)
                </a>
            </div>
        </div>
    </div>
    <?php
}

include 'includes/header.php';
?>

<style>
    /* OXO Luxury Brand overrides for login.php */
    body {
        background: var(--color-secondary);
        padding-top: 80px;
    }

    .pro-auth-section {
        min-height: calc(100vh - 80px);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 60px 20px;
    }

    .pro-auth-card {
        width: 100%;
        max-width: 440px;
        background: #ffffff;
        border-radius: 28px;
        padding: 50px 40px;
        box-shadow: 0 25px 60px rgba(10, 46, 36, 0.08);
        text-align: center;
        border: 1px solid rgba(10, 46, 36, 0.06);
    }

    .pro-auth-title {
        font-size: 32px;
        font-weight: 700;
        color: var(--color-primary);
        letter-spacing: -0.02em;
        margin-bottom: 8px;
        font-family: var(--font-title);
    }

    .pro-auth-subtitle {
        font-size: 14px;
        color: var(--color-gray);
        margin-bottom: 35px;
        font-family: var(--font-body);
    }

    .pro-input-group {
        margin-bottom: 20px;
        text-align: left;
    }

    .pro-input-group label {
        display: block;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        color: var(--color-primary);
        margin-bottom: 8px;
        font-family: var(--font-title);
    }

    .pro-input-group input {
        width: 100%;
        padding: 14px 18px;
        border: 1px solid rgba(10, 46, 36, 0.1);
        border-radius: 14px;
        font-size: 15px;
        color: var(--color-primary);
        background: #FAF9F6;
        box-sizing: border-box;
        transition: all 0.3s ease;
        font-family: var(--font-body);
    }

    .pro-input-group input:focus {
        outline: none;
        border-color: var(--color-accent);
        background: #ffffff;
        box-shadow: 0 0 0 3px rgba(200, 162, 118, 0.15);
    }

    .pro-auth-btn {
        width: 100%;
        padding: 16px;
        background: #1d1d1f;
        color: #fff;
        border: none;
        border-radius: 980px;
        font-size: 17px;
        font-weight: 600;
        cursor: pointer;
        transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1), background 0.3s;
        margin-top: 10px;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    }

    .pro-auth-btn:hover {
        background: #333336;
        transform: scale(0.98);
    }

    .pro-divider {
        display: flex;
        align-items: center;
        margin: 30px 0;
        font-size: 13px;
        color: #86868b;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    }

    .pro-divider::before,
    .pro-divider::after {
        content: "";
        flex: 1;
        height: 1px;
        background: rgba(0, 0, 0, 0.08);
    }

    .pro-divider span {
        padding: 0 15px;
    }

    .pro-switch {
        margin-top: 30px;
        font-size: 14px;
        color: #86868b;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    }

    .pro-switch a {
        color: #007aff;
        text-decoration: none;
        font-weight: 500;
        cursor: pointer;
        transition: 0.3s;
    }

    .pro-switch a:hover {
        opacity: 0.7;
    }

    .pro-error {
        color: #ff3b30;
        background: #ffeeee;
        padding: 16px;
        border-radius: 14px;
        font-size: 14px;
        font-weight: 500;
        margin-bottom: 25px;
        display: none;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    }

    /* Premium Developer Setup Warning styles */
    .pro-dev-warning {
        background: rgba(255, 149, 0, 0.05);
        border: 1px dashed rgba(255, 149, 0, 0.35);
        border-radius: 16px;
        padding: 16px;
        margin: 20px 0;
        text-align: left;
        transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    }

    .pro-dev-warning:hover {
        background: rgba(255, 149, 0, 0.08);
        border-color: rgba(255, 149, 0, 0.5);
        transform: translateY(-1px);
    }

    .pro-dev-warning-title {
        font-size: 14px;
        font-weight: 600;
        color: #c97d00;
        display: flex;
        align-items: center;
        gap: 6px;
        margin-bottom: 6px;
    }

    .pro-dev-warning-text {
        font-size: 12px;
        color: #515154;
        line-height: 1.5;
    }

    .pro-dev-warning-text code {
        background: rgba(0, 0, 0, 0.04);
        padding: 2px 5px;
        border-radius: 4px;
        font-family: SFMono-Regular, Consolas, "Liberation Mono", Menlo, monospace;
        font-size: 11px;
    }

    .pro-dev-link {
        color: #007aff;
        text-decoration: none;
        font-weight: 500;
    }

    .pro-dev-link:hover {
        text-decoration: underline;
    }
</style>

<section class="pro-auth-section">
    <div class="pro-auth-card">

        <!-- LOGIN FORM -->
        <div id="loginFormContainer">
            <h2 class="pro-auth-title">Sign In</h2>
            <p class="pro-auth-subtitle">Log in to manage your OXO account.</p>

            <div class="pro-error" id="loginError"></div>

            <form id="loginForm" onsubmit="handleProAuth(event, 'login')">
                <div class="pro-input-group">
                    <label>Email Address</label>
                    <input type="email" name="email" placeholder="name@example.com" required>
                </div>
                <div class="pro-input-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="••••••••" required>
                </div>
                <button type="submit" class="pro-auth-btn">Sign In</button>
            </form>

            <div class="pro-divider"><span>or connection</span></div>

            <?php if ($isGoogleConfigured): ?>
                <div id="g_id_onload_login" data-client_id="<?= htmlspecialchars(GOOGLE_CLIENT_ID) ?>" data-context="signin"
                    data-ux_mode="popup" data-callback="handleGoogleCb" data-auto_prompt="false"></div>
                <div class="g_id_signin" data-type="standard" data-shape="rectangular" data-theme="outline"
                    data-text="signin_with" data-size="medium" data-logo_alignment="center"
                    style="display: flex; justify-content: center;"></div>
            <?php else: ?>
                <?php renderGoogleDevWarning(); ?>
            <?php endif; ?>

            <div class="pro-switch">
                Don't have an account? <a onclick="toggleProAuth('signup')">Create yours now.</a>
            </div>
        </div>

        <!-- SIGNUP FORM -->
        <div id="signupFormContainer" style="display: none;">
            <h2 class="pro-auth-title">Create ID</h2>
            <p class="pro-auth-subtitle">One account for everything OXO.</p>

            <div class="pro-error" id="signupError"></div>

            <form id="signupForm" onsubmit="handleProAuth(event, 'signup')">
                <div class="pro-input-group">
                    <label>Full Name</label>
                    <input type="text" name="name" placeholder="John Appleseed" required>
                </div>
                <div class="pro-input-group">
                    <label>Email Address</label>
                    <input type="email" name="email" placeholder="name@example.com" required>
                </div>
                <div class="pro-input-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="••••••••" required>
                </div>
                <button type="submit" class="pro-auth-btn">Continue</button>
            </form>

            <div class="pro-divider"><span>or connection</span></div>

            <?php if ($isGoogleConfigured): ?>
                <div id="g_id_onload" data-client_id="<?= htmlspecialchars(GOOGLE_CLIENT_ID) ?>" data-context="use"
                    data-ux_mode="popup" data-callback="handleGoogleCb" data-auto_prompt="false"></div>
                <div class="g_id_signin" data-type="standard" data-shape="rectangular" data-theme="outline"
                    data-text="signup_with" data-size="medium" data-logo_alignment="center"
                    style="display: flex; justify-content: center;"></div>
            <?php else: ?>
                <?php renderGoogleDevWarning(); ?>
            <?php endif; ?>

            <div class="pro-switch">
                Already have an ID? <a onclick="toggleProAuth('login')">Sign In.</a>
            </div>
        </div>

    </div>
</section>

<!-- Google Mock Sandbox Modal -->
<div id="googleMockModal"
    style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.4); backdrop-filter:blur(4px); align-items:center; justify-content:center;">
    <div
        style="background:#fff; border-radius:16px; padding:30px; width:100%; max-width:360px; box-shadow:0 10px 30px rgba(0,0,0,0.15); text-align:center; position:relative; font-family:-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
        <svg width="24" height="24" viewBox="0 0 24 24" style="margin-bottom:15px;">
            <path fill="#4285F4"
                d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" />
            <path fill="#34A853"
                d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" />
            <path fill="#FBBC05"
                d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22c-.87-2.6-2.86-4.53-5.29-4.53z" />
            <path fill="#EA4335"
                d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z" />
        </svg>
        <h3 style="font-size:18px; font-weight:600; color:#1d1d1f; margin: 0 0 10px 0;">Google Dev Sandbox</h3>
        <p style="font-size:13px; color:#86868b; margin: 0 0 20px 0; line-height: 1.4;">Simulate Google Sign-In with any
            Google account email address.</p>

        <input type="email" id="googleMockEmail" value="sojinmathew1040@gmail.com" placeholder="name@gmail.com"
            style="width:100%; padding:12px; border:1px solid rgba(0,0,0,0.08); border-radius:8px; font-size:14px; box-sizing:border-box; margin-bottom:15px; text-align:center; outline: none; background: #f5f5f7;">

        <div style="display:flex; gap:10px;">
            <button onclick="closeGoogleMock()"
                style="flex:1; padding:12px; background:#f5f5f7; border:none; border-radius:8px; font-size:14px; font-weight:500; cursor:pointer; color: #1d1d1f;">Cancel</button>
            <button onclick="submitGoogleMock()"
                style="flex:1; padding:12px; background:#0071e3; color:#fff; border:none; border-radius:8px; font-size:14px; font-weight:500; cursor:pointer;">Sign
                In</button>
        </div>
    </div>
</div>

<script>
    function toggleProAuth(type) {
        document.getElementById('loginFormContainer').style.display = type === 'login' ? 'block' : 'none';
        document.getElementById('signupFormContainer').style.display = type === 'signup' ? 'block' : 'none';
    }

    async function handleProAuth(e, type) {
        e.preventDefault();
        const form = e.target;
        const btn = form.querySelector('button');
        const err = type === 'login' ? document.getElementById('loginError') : document.getElementById('signupError');

        btn.innerHTML = 'Processing...'; btn.disabled = true; err.style.display = 'none';

        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());
        data.action = type;

        try {
            const res = await fetch('ajax/auth_action.php', {
                method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data)
            });
            const result = await res.json();

            if (result.success) window.location.href = '<?= $redirect ?>';
            else {
                err.textContent = result.message || 'An error occurred.';
                err.style.display = 'block';
                btn.innerHTML = type === 'login' ? 'Sign In' : 'Continue';
                btn.disabled = false;
            }
        } catch (error) {
            err.textContent = 'Network error. Try again.';
            err.style.display = 'block';
            btn.innerHTML = type === 'login' ? 'Sign In' : 'Continue';
            btn.disabled = false;
        }
    }

    async function handleGoogleCb(response) {
        try {
            const res = await fetch('ajax/auth_action.php', {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'google_auth', credential: response.credential })
            });
            const result = await res.json();
            if (result.success) window.location.href = '<?= $redirect ?>';
            else alert('Failed: ' + (result.message || 'Unknown'));
        } catch (error) { alert('Network error during Sign-In.'); }
    }

    function simulateGoogleLogin(e) {
        e.preventDefault();
        document.getElementById('googleMockModal').style.display = 'flex';
    }

    function closeGoogleMock() {
        document.getElementById('googleMockModal').style.display = 'none';
    }

    async function submitGoogleMock() {
        const email = document.getElementById('googleMockEmail').value.trim();
        if (!email) {
            alert('Please enter a Gmail address.');
            return;
        }
        if (!email.includes('@') || !email.includes('.')) {
            alert('Please enter a valid email address.');
            return;
        }

        try {
            const res = await fetch('ajax/auth_action.php', {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'google_auth',
                    credential: 'mock_credential_jwt_payload',
                    email: email
                })
            });
            const result = await res.json();
            if (result.success) window.location.href = '<?= $redirect ?>';
            else alert('Failed: ' + (result.message || 'Unknown'));
        } catch (error) { alert('Network error during simulated Sign-In.'); }
    }
</script>

<?php if ($isGoogleConfigured): ?>
    <script src="https://accounts.google.com/gsi/client" async defer></script>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
