<?php
session_start();

// Disable caching completely
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

// Security headers
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_SESSION['user_role']) || !array_key_exists('organization_id', $_SESSION)) {
    include_once(__DIR__ . "/db.php");

    $userId = (int) $_SESSION['user_id'];
    $result = $conn->query("SELECT * FROM users WHERE id=$userId LIMIT 1");
    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $roleMap = [
            'admin' => 'super_admin',
            'organizer' => 'event_organizer',
            'super_admin' => 'super_admin',
            'organization_admin' => 'organization_admin',
            'event_organizer' => 'event_organizer',
            'attendee' => 'attendee',
        ];

        if (($user['status'] ?? 'active') !== 'active' || (int) ($user['is_active'] ?? 1) !== 1) {
            session_destroy();
            header("Location: login.php");
            exit;
        }

        $_SESSION['user_role'] = $roleMap[$user['role'] ?? 'attendee'] ?? 'attendee';
        $_SESSION['organization_id'] = $user['organization_id'] ?? null;
    }
}
?>
