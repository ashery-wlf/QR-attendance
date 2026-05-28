<?php
include("includes/db.php");

echo "Deleting events created by super_admin (incorrect creators)...\n";

// Find super_admin role
$adminResult = $conn->query("SELECT id FROM users WHERE role = 'super_admin'");
if ($adminResult && $adminResult->num_rows > 0) {
    $admin = $adminResult->fetch_assoc();
    $adminId = $admin['id'];
    
    // Delete events created by super_admin
    $deleteResult = $conn->query("UPDATE events SET deleted = TRUE, deleted_at = NOW() WHERE created_by = $adminId");
    echo "Deleted " . $conn->affected_rows . " events created by super_admin.\n";
} else {
    echo "Super admin not found.\n";
}

echo "\nVerification:\n";
$result = $conn->query("SELECT COUNT(*) as total FROM events WHERE deleted = FALSE");
$row = $result->fetch_assoc();
echo "Total active events: " . $row['total'] . "\n";
?>
