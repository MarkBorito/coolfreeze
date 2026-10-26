<?php
// Same setup style as admin_home.php
$userName    = $_SESSION['username'] ?? 'Admin';
$currentPage = 'admin_profile';   // highlights "Profile" in the shared sidebar

$pageTitle    = 'Admin Profile';
$assetsPath   = BASE_URL . 'frontend/assets';
$profileImage = $assetsPath . '/img/admin-profile.jpg';   // CHECK: change if your image is elsewhere

$admin = [
    'full_name' => 'Admin User', 'role' => 'Administrator', 'employee_id' => 'ADM-001',
    'status' => 'Active', 'created_at' => 'September 16, 2026',
];
$contactInfo = ['email' => 'admin@coolfreeze.com', 'phone' => '0999-999-9999'];
$accountDetails = [
    'username' => 'admin',
    'password_mask' => '******',
    'password_last_changed' => 'September 10, 2026',
];
$systemAccess = ['last_login' => 'Sep 18 2026', 'login_attempts' => 'Successful'];
$securitySettings = [
    ['key' => 'two_factor', 'title' => 'Two-Factor Authentication', 'desc' => 'Add an extra layer of security', 'enabled' => false],
    ['key' => 'failed_login', 'title' => 'Failed Login Protection', 'desc' => 'Protect account from repeated failed login attempts', 'enabled' => false],
    ['key' => 'auto_logout', 'title' => 'Auto Logout', 'desc' => 'Automatically log out the admin after a period of inactivity', 'enabled' => true],
];
$logoutOptions = [15, 30, 60, 120];
$logoutAfter = 30;
$loginHistory = [
    ['date' => 'Sep 16', 'device' => 'Windows PC', 'status' => 'Success'],
    ['date' => 'Sep 15', 'device' => 'Android',    'status' => 'Success'],
    ['date' => 'Sep 15', 'device' => 'Windows PC', 'status' => 'Success'],
];

if (!function_exists('e')) {
    function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('field')) {
    function field($label, $value, $bind = '') {
        echo '<div class="info-row"><span class="info-label">' . e($label) . '</span>'
           . '<div class="info-field"' . ($bind ? ' data-bind="' . e($bind) . '"' : '') . '>' . e($value) . '</div></div>';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle) ?> | CoolFreeze</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= BASE_URL ?>frontend/assets/css/main.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <link rel="stylesheet" href="<?= e($assetsPath) ?>/css/auth/admin-profile.css">
</head>
<body>
<div class="layout">

    <!-- Shared sidebar -->
    <?php require FRONTEND_PATH . 'includes/admin_sidebar.php'; ?>

    <!-- MOBILE OVERLAY -->
    <div class="overlay" id="overlay"></div>

    <div class="main">

        <!-- Shared topbar -->
        <?php require FRONTEND_PATH . 'includes/admin_topbar.php'; ?>

        <main class="content">
            <p class="breadcrumb">System &rsaquo; Profile</p>
            <h1 class="page-title"><?= e($pageTitle) ?></h1>
            <p class="page-sub">View and manage your account information</p>

            <div class="profile-layout">
                <!-- Profile summary card -->
                <aside class="card profile-card">
                    <div class="avatar-wrap">
                        <img id="avatarImg" src="<?= e($profileImage) ?>" alt="Admin profile photo">
                        <button class="camera-btn" id="cameraBtn" aria-label="Change photo"><i class="fa-solid fa-camera"></i></button>
                        <input type="file" id="photoInput" accept="image/*" hidden>
                    </div>
                    <p class="role-label"><?= e($admin['role']) ?></p>
                    <h2 class="admin-name" data-bind="full_name"><?= e($admin['full_name']) ?></h2>
                    <p class="emp-id"><?= e($admin['employee_id']) ?></p>
                    <span class="badge"><?= e($admin['status']) ?></span>

                    <div class="contact-card">
                        <div class="contact-row"><i class="fa-solid fa-envelope"></i><div><strong>Email Address</strong><span data-bind="email"><?= e($contactInfo['email']) ?></span></div></div>
                        <div class="contact-row"><i class="fa-solid fa-phone"></i><div><strong>Contact Number</strong><span data-bind="phone"><?= e($contactInfo['phone']) ?></span></div></div>
                        <div class="contact-row"><i class="fa-solid fa-circle-check"></i><div><strong>Account Created</strong><span><?= e($admin['created_at']) ?></span></div></div>
                        <button class="btn btn-solid" data-open="editModal" data-group="all"><i class="fa-solid fa-pen-to-square"></i>Edit Profile</button>
                    </div>
                </aside>

                <!-- Right panel -->
                <section class="card panel">
                    <div class="tabs" role="tablist">
                        <button class="tab active" data-tab="tabAccount" role="tab">Account Information</button>
                        <button class="tab" data-tab="tabSecurity" role="tab">Security Access</button>
                    </div>

                    <!-- TAB 1 -->
                    <div class="tab-content active" id="tabAccount">
                        <div class="section">
                            <div class="section-head"><i class="fa-solid fa-user"></i><h3>Personal Information</h3>
                                <button class="btn btn-outline" data-open="editModal" data-group="personal"><i class="fa-solid fa-pen-to-square"></i>Edit</button></div>
                            <?php field('Full Name', $admin['full_name'], 'full_name'); field('Role', $admin['role']); field('Employee ID', $admin['employee_id']); ?>
                        </div>
                        <div class="section">
                            <div class="section-head"><i class="fa-solid fa-envelope"></i><h3>Contact Information</h3>
                                <button class="btn btn-outline" data-open="editModal" data-group="contact"><i class="fa-solid fa-pen-to-square"></i>Edit</button></div>
                            <?php field('Email Address', $contactInfo['email'], 'email'); field('Contact Number', $contactInfo['phone'], 'phone'); ?>
                        </div>
                        <div class="section">
                            <div class="section-head"><i class="fa-solid fa-shield-halved"></i><h3>Account Details</h3>
                                <button class="btn btn-outline" data-open="editModal" data-group="account"><i class="fa-solid fa-pen-to-square"></i>Edit</button></div>
                            <?php field('Username', $accountDetails['username'], 'username'); field('Password', $accountDetails['password_mask']); ?>
                        </div>
                        <div class="section">
                            <div class="section-head"><i class="fa-solid fa-circle-info"></i><h3>System Access</h3></div>
                            <div class="plain-row"><span class="info-label">Last Login</span><span><?= e($systemAccess['last_login']) ?></span></div>
                            <div class="plain-row"><span class="info-label">Login Attempts</span><span><?= e($systemAccess['login_attempts']) ?></span></div>
                            <div class="plain-row"><span class="info-label">Account Status</span><span class="badge"><?= e($admin['status']) ?></span></div>
                        </div>
                    </div>

                    <!-- TAB 2 -->
                    <div class="tab-content" id="tabSecurity">
                        <div class="section">
                            <div class="section-head"><i class="fa-solid fa-user"></i><h3>Password</h3></div>
                            <div class="info-row"><span class="info-label">Password</span>
                                <div class="info-field with-icon"><span id="pwDisplay" data-mask="***********" data-real="Placeholder1">***********</span>
                                    <button class="icon-btn" id="pwEye" aria-label="Show or hide password"><i class="fa-solid fa-eye-slash"></i></button></div></div>
                            <div class="info-row"><span class="info-label">Last changed</span><div class="info-field"><?= e($accountDetails['password_last_changed']) ?></div></div>
                            <div class="section-foot"><button class="btn btn-outline" data-open="passwordModal"><i class="fa-solid fa-pen-to-square"></i>Change Password</button></div>
                        </div>

                        <div class="section">
                            <div class="section-head"><i class="fa-solid fa-envelope"></i><h3>Login Security</h3>
                                <button class="btn btn-outline" id="securityEdit"><i class="fa-solid fa-pen-to-square"></i><span>Edit</span></button></div>
                            <div id="securityBody" class="locked">
                                <?php foreach ($securitySettings as $s): ?>
                                    <div class="setting-row">
                                        <div><strong><?= e($s['title']) ?></strong><small><?= e($s['desc']) ?></small></div>
                                        <button class="switch <?= $s['enabled'] ? 'on' : '' ?>" role="switch" aria-checked="<?= $s['enabled'] ? 'true' : 'false' ?>" aria-label="<?= e($s['title']) ?>" id="sw_<?= e($s['key']) ?>"></button>
                                    </div>
                                <?php endforeach; ?>
                                <div class="setting-row"><strong>Logout after</strong>
                                    <select id="logoutAfter" aria-label="Logout after">
                                        <?php foreach ($logoutOptions as $m): ?><option value="<?= $m ?>" <?= $m === $logoutAfter ? 'selected' : '' ?>><?= $m ?> minutes</option><?php endforeach; ?>
                                    </select></div>
                            </div>
                        </div>

                        <div class="section">
                            <div class="section-head"><i class="fa-solid fa-circle-info"></i><h3>Recent Login Activity</h3></div>
                            <?php foreach ($loginHistory as $row): ?>
                                <div class="plain-row"><strong><?= e($row['date']) ?></strong><span class="device"><?= e($row['device']) ?></span><span class="badge"><?= e($row['status']) ?></span></div>
                            <?php endforeach; ?>
                            <div class="section-foot"><button class="btn btn-outline" data-open="historyModal">View Login History</button></div>
                        </div>
                    </div>
                </section>
            </div>
        </main>
    </div>
</div>

<!-- Edit modal -->
<div class="modal" id="editModal" aria-hidden="true"><div class="modal-box">
    <h3>Edit Profile</h3>
    <form id="editForm">
        <label data-group="personal all">Full Name<input id="f_full_name" name="full_name" value="<?= e($admin['full_name']) ?>" required></label>
        <label data-group="contact all">Email Address<input id="f_email" name="email" type="email" value="<?= e($contactInfo['email']) ?>" required></label>
        <label data-group="contact all">Contact Number<input id="f_phone" name="phone" value="<?= e($contactInfo['phone']) ?>" required></label>
        <label data-group="account all">Username<input id="f_username" name="username" value="<?= e($accountDetails['username']) ?>" required></label>
        <div class="modal-actions"><button type="button" class="btn btn-outline" data-close>Cancel</button><button type="submit" class="btn btn-solid">Save Changes</button></div>
    </form>
</div></div>

<!-- Password modal -->
<div class="modal" id="passwordModal" aria-hidden="true"><div class="modal-box">
    <h3>Change Password</h3>
    <form id="passwordForm">
        <?php foreach (['current' => 'Current Password', 'new' => 'New Password', 'confirm' => 'Confirm New Password'] as $id => $label): ?>
            <label><?= e($label) ?><span class="pw-wrap"><input type="password" id="pw_<?= $id ?>" autocomplete="off"><button type="button" class="icon-btn pw-toggle" data-target="pw_<?= $id ?>" aria-label="Show or hide"><i class="fa-solid fa-eye-slash"></i></button></span></label>
        <?php endforeach; ?>
        <p class="form-msg" id="pwMsg" role="alert"></p>
        <div class="modal-actions"><button type="button" class="btn btn-outline" data-close>Cancel</button><button type="submit" class="btn btn-solid">Save Changes</button></div>
    </form>
</div></div>

<!-- History modal -->
<div class="modal" id="historyModal" aria-hidden="true"><div class="modal-box">
    <h3>Login History</h3>
    <?php foreach ($loginHistory as $row): ?>
        <div class="plain-row"><strong><?= e($row['date']) ?></strong><span class="device"><?= e($row['device']) ?></span><span class="badge"><?= e($row['status']) ?></span></div>
    <?php endforeach; ?>
    <div class="modal-actions"><button type="button" class="btn btn-outline" data-close>Close</button></div>
</div></div>

<script src="<?= e($assetsPath) ?>/js/admin-profile.js"></script>
</body>
</html>