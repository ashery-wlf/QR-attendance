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

$message = "";
$message_type = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Enter a valid email address.";
        $message_type = "error";
    } else {
        ensurePasswordResetRequestSchema($conn);

        // Check if the email exists in users
        $stmt = $conn->prepare("SELECT id, name FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();

        // Record request and whether it matched an existing account, but do NOT reveal match to the requester
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $user_id = (int) $user['id'];
            $ins = $conn->prepare("INSERT INTO password_reset_requests (email, user_id, matched, requested_ip) VALUES (?, ?, 1, ?)");
            $ins->bind_param('sis', $email, $user_id, $ip);
            $ins->execute();
        } else {
            $ins = $conn->prepare("INSERT INTO password_reset_requests (email, matched, requested_ip) VALUES (?, 0, ?)");
            $ins->bind_param('ss', $email, $ip);
            $ins->execute();
        }

        $message = "If that email belongs to an account we've recorded your request. Contact your Organization Admin or the System Admin for help. <a href=\"index.php#contact\">Contact Us</a>";
        $message_type = "success";
        if (isset($stmt)) { $stmt->close(); }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Forgot Password - QR Attendance</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="includes/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="white-background"></div>

    <div class="simple-auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <div class="logo-container">
                    <img src="logo.png" alt="QR Attendance" class="logo">
                </div>
                <h1 class="auth-title">Forgot Password</h1>
                <p class="auth-subtitle">Request password reset guidance</p>
            </div>

            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo h($message_type); ?>">
                    <i class="fas <?php echo $message_type === 'error' ? 'fa-exclamation-circle' : 'fa-check-circle'; ?>"></i>
                    <span><?php echo $message; ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" class="auth-form">
                <div class="form-group">
                    <label for="email" class="form-label">
                        <i class="fas fa-envelope"></i>
                        Email Address
                    </label>
                    <input type="email" id="email" name="email" class="form-input" placeholder="Enter your account email" required>
                </div>

                <button type="submit" class="btn btn-primary btn-full">
                    <i class="fas fa-paper-plane"></i>
                    Continue
                </button>
            </form>

            <div class="auth-footer">
                <p>Remember your password? <a href="login.php" class="auth-link">Back to login</a></p>
                <p><a href="index.php" class="auth-link">&larr; Back to Home</a></p>
            </div>
        </div>
    </div>
</body>
</html>
