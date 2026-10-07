<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
defined('BASE_URL') || define('BASE_URL', '/online-book-resale');
requireUserLogin();
$userId = (int)$_SESSION['user_id'];
$user = getCurrentUser($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'update_profile') {
        $name  = sanitize($_POST['name'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');
        $addr  = sanitize($_POST['address'] ?? '');
        $city  = sanitize($_POST['city'] ?? '');
        $state = sanitize($_POST['state'] ?? '');
        $pin   = sanitize($_POST['pincode'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        // Handle photo upload
        $photo = $user['profile_photo'];
        if (!empty($_FILES['photo']['name'])) {
            $uploaded = uploadImage($_FILES['photo'], 'profiles');
            if ($uploaded) $photo = basename($uploaded);
        }
        $stmt = $conn->prepare("UPDATE users SET name=?,email=?,phone=?,address=?,city=?,state=?,pincode=?,profile_photo=? WHERE id=?");
        $stmt->bind_param('ssssssssi', $name,$email,$phone,$addr,$city,$state,$pin,$photo,$userId);
        if ($stmt->execute()) { $_SESSION['user_name']=$name; $_SESSION['user_email']=$email; setFlash('success','Profile updated successfully!'); }
        else setFlash('error','Update failed. Try again.');
    } elseif ($action === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password'] ?? '';
        $conf    = $_POST['confirm_password'] ?? '';
        if (!password_verify($current, $user['password'])) setFlash('error','Current password is incorrect.');
        elseif (strlen($new) < 8) setFlash('error','New password must be at least 8 characters.');
        elseif ($new !== $conf) setFlash('error','Passwords do not match.');
        else {
            $hash = password_hash($new, PASSWORD_BCRYPT);
            $conn->prepare("UPDATE users SET password=? WHERE id=?")->execute_or_die ?? null;
            $stmt = $conn->prepare("UPDATE users SET password=? WHERE id=?");
            $stmt->bind_param('si', $hash, $userId); $stmt->execute();
            setFlash('success','Password changed successfully!');
        }
    }
    header('Location: ' . SITE_URL . '/user/profile.php'); exit;
}
$pageTitle = 'My Profile';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<div class="container py-5">
    <?= renderFlash() ?>
    <div class="row g-4">
        <div class="col-lg-3">
            <div class="glass-card p-0 overflow-hidden">
                <div class="text-center p-4" style="background:linear-gradient(135deg,rgba(212,175,55,0.1),rgba(124,58,237,0.1));">
                    <img src="<?= UPLOAD_URL ?>profiles/<?= sanitize($user['profile_photo']) ?>" alt="Profile" class="profile-avatar mb-3" onerror="this.src='<?= UPLOAD_URL ?>default_user.png'">
                    <h6 class="text-white fw-bold mb-0"><?= sanitize($user['name']) ?></h6>
                    <small class="text-muted"><?= sanitize($user['email']) ?></small><br>
                    <small class="text-muted">Joined <?= date('M Y',strtotime($user['created_at'])) ?></small>
                </div>
                <ul class="list-unstyled p-2 mb-0">
                    <?php $links=[['dashboard.php','bi-speedometer2','Dashboard'],['profile.php','bi-person','My Profile'],['orders.php','bi-bag-check','My Orders'],['wishlist.php','bi-heart','Wishlist'],['cart.php','bi-cart3','Cart'],['mybooks.php','bi-journal-bookmarks','My Listings']]; ?>
                    <?php foreach ($links as $l): ?>
                    <li><a href="<?= SITE_URL ?>/user/<?= $l[0] ?>" class="dropdown-item rounded-3 py-2 px-3 mb-1 <?= basename($_SERVER['PHP_SELF'])===$l[0]?'bg-gold text-dark fw-bold':'' ?>"><i class="bi <?= $l[1] ?> me-2"></i><?= $l[2] ?></a></li>
                    <?php endforeach; ?>
                    <li><hr class="dropdown-divider my-1"></li>
                    <li><a href="<?= SITE_URL ?>/auth/logout.php" class="dropdown-item rounded-3 py-2 px-3 mb-1 text-danger"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                </ul>
            </div>
        </div>
        <div class="col-lg-9">
            <!-- Profile Form -->
            <div class="glass-card p-4 mb-4">
                <h5 class="text-white fw-bold mb-4"><i class="bi bi-person me-2 text-gold"></i>Edit Profile</h5>
                <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                    <input type="hidden" name="action" value="update_profile">
                    <div class="row g-3">
                        <div class="col-12 text-center">
                            <img src="<?= UPLOAD_URL ?>profiles/<?= sanitize($user['profile_photo']) ?>" alt="Profile" class="profile-avatar mb-3" id="profilePreview" onerror="this.src='<?= UPLOAD_URL ?>default_user.png'">
                            <div><label class="btn btn-outline-gold btn-sm cursor-pointer" for="photoInput"><i class="bi bi-camera me-2"></i>Change Photo</label>
                            <input type="file" name="photo" id="photoInput" class="d-none" accept="image/*" onchange="previewPhoto(this)"></div>
                        </div>
                        <div class="col-md-6"><label class="form-label">Full Name *</label><input type="text" name="name" class="form-control" value="<?= sanitize($user['name']) ?>" required></div>
                        <div class="col-md-6"><label class="form-label">Email *</label><input type="email" name="email" class="form-control" value="<?= sanitize($user['email']) ?>" required></div>
                        <div class="col-md-6"><label class="form-label">Phone</label><input type="tel" name="phone" class="form-control" value="<?= sanitize($user['phone'] ?? '') ?>"></div>
                        <div class="col-md-6"><label class="form-label">City</label><input type="text" name="city" class="form-control" value="<?= sanitize($user['city'] ?? '') ?>"></div>
                        <div class="col-12"><label class="form-label">Address</label><textarea name="address" class="form-control" rows="2"><?= sanitize($user['address'] ?? '') ?></textarea></div>
                        <div class="col-md-6"><label class="form-label">State</label><input type="text" name="state" class="form-control" value="<?= sanitize($user['state'] ?? '') ?>"></div>
                        <div class="col-md-6"><label class="form-label">Pincode</label><input type="text" name="pincode" class="form-control" value="<?= sanitize($user['pincode'] ?? '') ?>" pattern="[0-9]{6}"></div>
                        <div class="col-12"><button type="submit" class="btn btn-gold"><i class="bi bi-save me-2"></i>Update Profile</button></div>
                    </div>
                </form>
            </div>
            <!-- Change Password -->
            <div class="glass-card p-4">
                <h5 class="text-white fw-bold mb-4"><i class="bi bi-lock me-2 text-gold"></i>Change Password</h5>
                <form method="POST" class="needs-validation" novalidate>
                    <input type="hidden" name="action" value="change_password">
                    <div class="row g-3">
                        <div class="col-md-4"><label class="form-label">Current Password</label><input type="password" name="current_password" class="form-control" required></div>
                        <div class="col-md-4"><label class="form-label">New Password</label><input type="password" name="new_password" class="form-control" minlength="8" required></div>
                        <div class="col-md-4"><label class="form-label">Confirm Password</label><input type="password" name="confirm_password" class="form-control" required></div>
                        <div class="col-12"><button type="submit" class="btn btn-outline-gold"><i class="bi bi-shield-check me-2"></i>Change Password</button></div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
function previewPhoto(input) {
    if (input.files && input.files[0]) {
        const r = new FileReader();
        r.onload = e => document.getElementById('profilePreview').src = e.target.result;
        r.readAsDataURL(input.files[0]);
    }
}
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

