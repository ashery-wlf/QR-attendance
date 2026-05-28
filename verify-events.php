<?php
include("includes/db.php");

echo "=== EVENT VISIBILITY VERIFICATION ===\n\n";

// Get all users by role
echo "USERS BY ROLE:\n";
$result = $conn->query("SELECT id, name, role, organization_id FROM users ORDER BY role, name");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "  [{$row['role']}] {$row['name']} (ID: {$row['id']}, Org: {$row['organization_id']})\n";
    }
}

echo "\n\nEVENTS:\n";
$result = $conn->query("
    SELECT e.id, e.name, e.organization_id, e.created_by, u.name, u.role
    FROM events e
    LEFT JOIN users u ON e.created_by = u.id
    WHERE e.deleted = FALSE
    ORDER BY e.id
");

if ($result->num_rows === 0) {
    echo "  (No events)\n";
} else {
    while ($row = $result->fetch_assoc()) {
        echo "  [{$row['id']}] {$row['name']}\n";
        echo "      Organization: {$row['organization_id']}\n";
        echo "      Created by: {$row['name']} ({$row['role']})\n";
    }
}

echo "\n\nACCESS RULES:\n";
echo "  ✗ super_admin: NO access to events.php (blocked)\n";
echo "  ✓ organization_admin: See org events\n";
echo "  ✓ event_organizer: See own + org events\n";
echo "  ✓ attendee: See org events\n";
?>
