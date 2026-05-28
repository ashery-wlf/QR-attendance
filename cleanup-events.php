<?php
include("includes/db.php");

echo "Checking for events with invalid creators...\n";

// Find events where created_by is NULL or 0 or doesn't exist in users table
$result = $conn->query("
    SELECT e.id, e.name, e.created_by 
    FROM events e
    LEFT JOIN users u ON e.created_by = u.id
    WHERE e.created_by IS NULL OR e.created_by = 0 OR u.id IS NULL
");

if ($result && $result->num_rows > 0) {
    echo "Found " . $result->num_rows . " events with invalid creators:\n";
    $ids = [];
    while ($row = $result->fetch_assoc()) {
        echo "  - ID: " . $row['id'] . ", Name: " . $row['name'] . ", Creator: " . ($row['created_by'] ?? 'NULL') . "\n";
        $ids[] = $row['id'];
    }
    
    if (!empty($ids)) {
        $idList = implode(',', $ids);
        $deleteResult = $conn->query("DELETE FROM events WHERE id IN ($idList)");
        echo "\nDeleted " . $conn->affected_rows . " invalid events.\n";
    }
} else {
    echo "No invalid events found.\n";
}

echo "\nAll events now have valid creators.\n";
?>
