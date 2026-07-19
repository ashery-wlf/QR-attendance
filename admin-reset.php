<?php
session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");

include("includes/db.php");
include("includes/app.php");

ensureUserSchema($conn);

appRequireRole(['super_admin']);

$message = '';
$message_type = '';
$foundUser = null;
$tempPassword = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'search') {
        $email = trim($_POST['email'] ?? '');
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Enter a valid email address.';
            $message_type = 'error';
        } else {
            $stmt = $conn->prepare("SELECT id, name, email, role, organization_id FROM users WHERE email = ? LIMIT 1");
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res && $res->num_rows > 0) {
                $foundUser = $res->fetch_assoc();
            } else {
                $message = 'No user found with that email.';
                $message_type = 'error';
            }
            $stmt->close();
        }
    } elseif ($action === 'reset') {
        if (!appVerifyCsrf()) {
            $message = 'Invalid request token.';
            $message_type = 'error';
        } else {
            $user_id = (int) ($_POST['user_id'] ?? 0);
            if ($user_id <= 0) {
                $message = 'Invalid user.';
                $message_type = 'error';
            } else {
                $stmt = $conn->prepare("SELECT id, email, name FROM users WHERE id = ? LIMIT 1");
                $stmt->bind_param('i', $user_id);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($res && $res->num_rows > 0) {
                    $userRow = $res->fetch_assoc();

                    // generate temporary password
                    $tempPassword = substr(bin2hex(random_bytes(5)), 0, 10);
                    $hash = password_hash($tempPassword, PASSWORD_DEFAULT);

                    $upd = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $upd->bind_param('si', $hash, $user_id);
                    $ok = $upd->execute();
                    $upd->close();

                    if ($ok) {
                        // mark any pending reset requests for this email as resolved
                        $email = $userRow['email'];
                        $adminId = (int) ($_SESSION['user_id'] ?? 0);
                        $conn->query("UPDATE password_reset_requests SET status='resolved', resolved_by=$adminId, resolved_at=NOW() WHERE email='" . $conn->real_escape_string($email) . "' AND status='pending'");

                        $message = 'Password reset successful. Share the temporary password with the user.';
                        $message_type = 'success';
                        $foundUser = $userRow;
                    } else {
                        $message = 'Could not update password.';
                        $message_type = 'error';
                    }

                } else {
                    $message = 'User not found.';
                    $message_type = 'error';
                }
                $stmt->close();
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin - Reset User Password</title>
    <link rel="stylesheet" href="includes/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .admin-reset { max-width:720px; margin:40px auto; }
        .user-card { border:1px solid #eee; padding:16px; margin-top:12px; }
        .temp-pass { background:#f3f4f6; padding:8px; display:inline-block; margin-top:8px; font-weight:600; }
    </style>
</head>
<body>
    <div class="admin-reset">
        <h1>System Admin - Reset User Password</h1>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo h($message_type); ?>">
                <i class="fas <?php echo $message_type === 'error' ? 'fa-exclamation-circle' : 'fa-check-circle'; ?>"></i>
                <span><?php echo h($message); ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" style="margin-top:12px;">
            <input type="hidden" name="action" value="search">
            <label>Email to find user:</label>
            <div style="display:flex; gap:8px; margin-top:6px;">
                <input type="email" name="email" placeholder="user@example.com" required class="form-input">
                <button class="btn btn-primary" type="submit">Search</button>
            </div>
        </form>

        <?php if ($foundUser): ?>
            <div class="user-card">
                <div><strong>Name:</strong> <?php echo h($foundUser['name'] ?? ''); ?></div>
                <div><strong>Email:</strong> <?php echo h($foundUser['email'] ?? ''); ?></div>
                <div><strong>Role:</strong> <?php echo h(appRoleLabel($foundUser['role'] ?? '')); ?></div>

                <?php if (!empty($tempPassword)): ?>
                    <div class="temp-pass">Temporary password: <?php echo h($tempPassword); ?></div>
                <?php endif; ?>

                <form method="POST" style="margin-top:12px;">
                    <?php echo appCsrfInput(); ?>
                    <input type="hidden" name="action" value="reset">
                    <input type="hidden" name="user_id" value="<?php echo (int) ($foundUser['id'] ?? 0); ?>">
                    <button class="btn btn-danger" type="submit">Reset Password</button>
                    <a href="dashboard.php" class="btn">Back</a>
                </form>
            </div>
        <?php endif; ?>

    </div>
</body>
</html>
