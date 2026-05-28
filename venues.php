<?php
include("includes/auth.php");
include("includes/db.php");
include("includes/app.php");

ensureEventSchema($conn);
appRequireRole(['organization_admin', 'super_admin']);

$user_id = (int) $_SESSION['user_id'];
$organization_id = isset($_SESSION['organization_id']) ? (int) $_SESSION['organization_id'] : 0;
$message = "";
$error = "";
$returnUrl = "venues.php";

// Super admin tunatakiwa awe na venue management global OR awe na org choice
// Kwa sasa: if super_admin, anataka awe na all orgs, else use session org
if ($_SESSION['user_role'] === 'super_admin') {
    $organization_id = (int) ($_GET['org'] ?? $_POST['org'] ?? 0);
    $returnUrl = "venues.php?org=" . $organization_id;
    if ($organization_id <= 0) {
        $error = "Select an organization.";
    }
} elseif ($organization_id <= 0) {
    die("No organization assigned.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $error === '') {
    if (!appVerifyCsrf()) {
        die("Security check failed.");
    }

    $action = $_POST['action'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $capacity = (int) ($_POST['capacity'] ?? 0);
    $latitudeRaw = trim($_POST['latitude'] ?? '');
    $longitudeRaw = trim($_POST['longitude'] ?? '');
    [$latitude, $longitude] = appNormalizeLatLng(
        is_numeric($latitudeRaw) ? (float) $latitudeRaw : null,
        is_numeric($longitudeRaw) ? (float) $longitudeRaw : null
    );
    $description = trim($_POST['description'] ?? '');
    $venue_id = (int) ($_POST['venue_id'] ?? 0);
    $existingVenue = null;

    if ($action === 'update' && $venue_id > 0) {
        $existingStmt = $conn->prepare("SELECT * FROM venues WHERE id = ? AND organization_id = ? LIMIT 1");
        $existingStmt->bind_param("ii", $venue_id, $organization_id);
        $existingStmt->execute();
        $existingResult = $existingStmt->get_result();
        $existingVenue = $existingResult && $existingResult->num_rows > 0 ? $existingResult->fetch_assoc() : null;

        if ($existingVenue) {
            if ($latitude === null && is_numeric($existingVenue['latitude'])) {
                $latitude = (float) $existingVenue['latitude'];
            }
            if ($longitude === null && is_numeric($existingVenue['longitude'])) {
                $longitude = (float) $existingVenue['longitude'];
            }
            if ($address === '') {
                $address = $existingVenue['address'] ?? '';
            }
            if ($city === '') {
                $city = $existingVenue['city'] ?? '';
            }
        }
    }

    if ($action === 'create' || $action === 'update') {
        if ($name === '') {
            $error = "Venue name is required.";
        } elseif ($capacity <= 0) {
            $error = "Venue capacity must be greater than 0.";
        } elseif ($latitude === null || $longitude === null) {
            $error = "Set the exact venue location on the map.";
        }
    }

    $venueLocation = trim(implode(', ', array_filter([$address, $city])));

    if ($error === '' && $action === 'create') {
        $stmt = $conn->prepare("
            INSERT INTO venues(organization_id, name, address, city, capacity, latitude, longitude, description, created_by)
            VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("isssiddsi", $organization_id, $name, $address, $city, $capacity, $latitude, $longitude, $description, $user_id);

        if ($stmt->execute()) {
            appSetFlash("Venue created successfully.", "success");
            header("Location: " . $returnUrl);
            exit();
        } else {
            $error = "Venue could not be created.";
        }
    }

    if ($error === '' && $action === 'update') {
        if (!$existingVenue) {
            $error = "Venue not found or does not belong to your organization.";
        } else {
            $stmt = $conn->prepare("
                UPDATE venues
                SET name = ?, address = ?, city = ?, capacity = ?, latitude = ?, longitude = ?, description = ?
                WHERE id = ? AND organization_id = ?
            ");
            $stmt->bind_param("sssiddsii", $name, $address, $city, $capacity, $latitude, $longitude, $description, $venue_id, $organization_id);

            if ($stmt->execute()) {
                $syncStmt = $conn->prepare("
                    UPDATE events
                    SET venue_name = ?, venue_location = ?, location_lat = ?, location_lng = ?
                    WHERE venue_id = ? AND organization_id = ? AND deleted = FALSE
                ");
                $syncStmt->bind_param("ssddii", $name, $venueLocation, $latitude, $longitude, $venue_id, $organization_id);
                $syncStmt->execute();

                appSetFlash("Venue updated successfully.", "success");
                header("Location: " . $returnUrl);
                exit();
            } else {
                $error = "Venue could not be updated.";
            }
        }
    }

    if ($action === 'delete') {
        $venue_id = (int) ($_POST['venue_id'] ?? 0);
        
        // Check if venue is used in events
        $checkEventsStmt = $conn->prepare("SELECT COUNT(*) AS total FROM events WHERE venue_id = ? AND organization_id = ?");
        $checkEventsStmt->bind_param("ii", $venue_id, $organization_id);
        $checkEventsStmt->execute();
        $checkEventsResult = $checkEventsStmt->get_result();
        $eventCount = (int) $checkEventsResult->fetch_assoc()['total'];

        if ($eventCount > 0) {
            $error = "Cannot delete venue - it is being used in {$eventCount} event(s).";
        } else {
            $stmt = $conn->prepare("DELETE FROM venues WHERE id = ? AND organization_id = ?");
            $stmt->bind_param("ii", $venue_id, $organization_id);

            if ($stmt->execute()) {
                appSetFlash("Venue deleted successfully.", "success");
                header("Location: " . $returnUrl);
                exit();
            } else {
                $error = "Venue could not be deleted.";
            }
        }
    }
}

// Fetch venues
$venuesQuery = $conn->prepare("
    SELECT v.*,
           (SELECT COUNT(*) FROM events WHERE venue_id = v.id AND deleted = FALSE) AS event_count
    FROM venues v
    WHERE v.organization_id = ?
    ORDER BY v.name ASC
");
$venuesQuery->bind_param("i", $organization_id);
$venuesQuery->execute();
$venuesResult = $venuesQuery->get_result();

// Get organization details
$orgQuery = $conn->prepare("SELECT id, name FROM organizations WHERE id = ?");
$orgQuery->bind_param("i", $organization_id);
$orgQuery->execute();
$orgResult = $orgQuery->get_result();
$currentOrg = $orgResult->fetch_assoc();

// If super admin, get all organizations for selection
$allOrgsResult = null;
if ($_SESSION['user_role'] === 'super_admin') {
    $allOrgsResult = $conn->query("SELECT id, name FROM organizations WHERE is_active = 1 ORDER BY name ASC");
}

// Stats
$totalVenuesResult = $conn->prepare("SELECT COUNT(*) AS total FROM venues WHERE organization_id = ?");
$totalVenuesResult->bind_param("i", $organization_id);
$totalVenuesResult->execute();
$totalVenues = (int) $totalVenuesResult->get_result()->fetch_assoc()['total'];

$totalCapacityResult = $conn->prepare("SELECT SUM(capacity) AS total FROM venues WHERE organization_id = ?");
$totalCapacityResult->bind_param("i", $organization_id);
$totalCapacityResult->execute();
$totalCapacity = (int) ($totalCapacityResult->get_result()->fetch_assoc()['total'] ?? 0);

$pageCss = appLocationMapsHeadHtml($conn) . '<style>
.admin-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 14px;
    margin-bottom: 18px;
}
.metric {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 16px;
    box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05);
}
.metric span {
    display: block;
    color: #64748b;
    font-size: 13px;
    font-weight: 700;
}
.metric strong {
    display: block;
    margin-top: 6px;
    font-size: 30px;
    color: #0f172a;
}
.venue-layout {
    display: grid;
    grid-template-columns: 380px 1fr;
    gap: 16px;
}
.panel {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 18px;
    padding: 18px;
    box-shadow: 0 12px 28px rgba(15, 23, 42, 0.06);
}
.panel h2 {
    margin: 0 0 12px;
    font-size: 20px;
}
.form {
    display: grid;
    gap: 12px;
}
.form label {
    display: grid;
    gap: 6px;
    font-size: 13px;
    font-weight: 800;
    color: #334155;
}
.form input, .form textarea, .form select {
    width: 100%;
    border: 1px solid #dbe4f0;
    border-radius: 12px;
    padding: 12px;
    font-size: 14px;
    background: #f8fbff;
    color: #0f172a;
}
.form textarea {
    min-height: 80px;
    resize: vertical;
}
.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}
.form-row label {
    grid-column: auto;
}
.venue-map-tools {
    border: 1px solid #dbe4f0;
    border-radius: 14px;
    padding: 12px;
    background: #f8fbff;
    display: grid;
    gap: 10px;
}
.venue-map-toolbar {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto auto;
    gap: 8px;
}
.venue-map {
    height: 260px;
    border: 1px solid #dbe4f0;
    border-radius: 12px;
    overflow: hidden;
    background: #fff;
}
.map-status {
    color: #1d4ed8;
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    border-radius: 10px;
    padding: 9px 10px;
    font-size: 12px;
    font-weight: 800;
}
.map-btn {
    border: none;
    border-radius: 10px;
    padding: 0 12px;
    background: #e0e7ff;
    color: #3730a3;
    font-weight: 900;
    cursor: pointer;
}
.btn {
    border: none;
    border-radius: 12px;
    padding: 12px 14px;
    font-weight: 900;
    cursor: pointer;
    font-size: 14px;
}
.primary { background: #2563ff; color: #fff; }
.muted { background: #e2e8f0; color: #334155; }
.danger { background: #fee2e2; color: #b91c1c; }
.success { background: #dcfce7; color: #166534; }
.message, .error {
    padding: 12px 14px;
    border-radius: 14px;
    margin-bottom: 14px;
    font-weight: 800;
    font-size: 14px;
}
.message { background: #ecfdf5; color: #166534; border: 1px solid #bbf7d0; }
.error { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
.venue-list {
    display: grid;
    gap: 12px;
}
.venue-card {
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 14px;
    background: #fff;
}
.venue-head {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    align-items: flex-start;
}
.venue-head h3 {
    margin: 0;
    font-size: 18px;
}
.venue-info {
    display: grid;
    gap: 6px;
    margin: 10px 0;
    font-size: 13px;
    color: #64748b;
}
.venue-stat {
    display: inline-flex;
    gap: 4px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 6px 10px;
    font-size: 12px;
    font-weight: 800;
    color: #334155;
}
.venue-actions {
    display: grid;
    gap: 10px;
    margin-top: 12px;
    border-top: 1px solid #e2e8f0;
    padding-top: 12px;
}
.btn-group {
    display: flex;
    gap: 8px;
}
.btn-small {
    flex: 1;
    padding: 10px 12px;
    font-size: 13px;
}
.side-stack {
    display: grid;
    gap: 16px;
    align-content: start;
}
.org-selector {
    margin-bottom: 12px;
}
.org-selector label {
    display: grid;
    gap: 6px;
    font-size: 13px;
    font-weight: 800;
}
@media (max-width: 900px) {
    .venue-layout { grid-template-columns: 1fr; }
    .venue-map-toolbar { grid-template-columns: 1fr; }
    .map-btn { padding: 11px 12px; }
}
</style>';

renderAppShellStart($conn, [
    "title" => "Venues",
    "active" => "venues",
    "page_title" => "Venues",
    "page_subtitle" => "Create and manage venues for your organization's events.",
    "extra_head" => $pageCss,
]);
?>

<?php if ($_SESSION['user_role'] === 'super_admin' && $allOrgsResult): ?>
    <div class="panel org-selector">
        <form method="GET" style="display: grid; gap: 8px; grid-template-columns: 1fr auto;">
            <label>
                Select Organization
                <select name="org" required onchange="this.form.submit();">
                    <option value="">Choose organization...</option>
                    <?php while ($org = $allOrgsResult->fetch_assoc()): ?>
                        <option value="<?php echo (int) $org['id']; ?>" <?php echo (int) $org['id'] === $organization_id ? 'selected' : ''; ?>>
                            <?php echo h($org['name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </label>
        </form>
    </div>
<?php endif; ?>

<?php if ($message !== ''): ?>
    <div class="message"><?php echo h($message); ?></div>
<?php endif; ?>
<?php if ($error !== ''): ?>
    <div class="error"><?php echo h($error); ?></div>
<?php endif; ?>

<?php if ($currentOrg): ?>
    <div style="margin-bottom: 18px;">
        <h2 style="margin: 0; color: #0f172a; font-size: 24px;"><?php echo h($currentOrg['name']); ?> - Venues</h2>
    </div>
<?php endif; ?>

<section class="admin-grid">
    <article class="metric">
        <span>Total Venues</span>
        <strong><?php echo $totalVenues; ?></strong>
    </article>
    <article class="metric">
        <span>Total Capacity</span>
        <strong><?php echo $totalCapacity; ?></strong>
    </article>
</section>

<section class="venue-layout">
    <aside class="side-stack">
        <div class="panel">
            <h2>Create Venue</h2>
            <form method="POST" class="form" novalidate>
                <?php echo appCsrfInput(); ?>
                <input type="hidden" name="action" value="create">
                <?php if ($_SESSION['user_role'] === 'super_admin'): ?>
                    <input type="hidden" name="org" value="<?php echo (int) $organization_id; ?>">
                <?php endif; ?>
                <label>
                    Venue Name
                    <input type="text" name="name" required>
                </label>
                <label>
                    Address
                    <input type="text" name="address" placeholder="123 Main Street">
                </label>
                <label>
                    City
                    <input type="text" name="city" placeholder="e.g., Morogoro">
                </label>
                <label>
                    Capacity
                    <input type="number" name="capacity" min="1" required>
                </label>
                <div class="form-row">
                    <label>
                        Latitude
                        <input class="venue-lat-display" type="text" inputmode="decimal" placeholder="-6.8250" readonly>
                        <input class="venue-lat" type="hidden" name="latitude">
                    </label>
                    <label>
                        Longitude
                        <input class="venue-lng-display" type="text" inputmode="decimal" placeholder="37.6600" readonly>
                        <input class="venue-lng" type="hidden" name="longitude">
                    </label>
                </div>
                <div class="venue-map-tools" data-map-block>
                    <label>Set Exact Venue on Map</label>
                    <div class="venue-map-toolbar">
                        <input type="text" data-map-search placeholder="Search location (free)">
                        <button type="button" class="map-btn" data-map-find>Find</button>
                        <button type="button" class="map-btn" data-map-current>Use My Location</button>
                    </div>
                    <div class="map-status" data-map-status>Click the map or search to set exact venue coordinates.</div>
                    <div class="venue-map google-map-canvas" data-venue-map></div>
                </div>
                <label>
                    Description
                    <textarea name="description" placeholder="Add details about the venue..."></textarea>
                </label>
                <button type="submit" class="btn primary">Create Venue</button>
            </form>
        </div>
    </aside>

    <div class="panel">
        <h2>Managed Venues</h2>
        <div class="venue-list">
            <?php if ($venuesResult && $venuesResult->num_rows > 0): ?>
                <?php while ($venue = $venuesResult->fetch_assoc()):
                    $venueLat = $venue['latitude'];
                    $venueLng = $venue['longitude'];
                    if (is_numeric($venueLat) && is_numeric($venueLng)) {
                        [$venueLat, $venueLng] = appNormalizeLatLng((float) $venueLat, (float) $venueLng);
                    }
                ?>
                    <article class="venue-card">
                        <div class="venue-head">
                            <div>
                                <h3><?php echo h($venue['name']); ?></h3>
                            </div>
                            <span class="venue-stat"><?php echo (int) $venue['event_count']; ?> event<?php echo (int) $venue['event_count'] !== 1 ? 's' : ''; ?></span>
                        </div>

                        <div class="venue-info">
                            <?php if (!empty($venue['address'])): ?>
                                <div>📍 <?php echo h($venue['address']); ?><?php echo !empty($venue['city']) ? ', ' . h($venue['city']) : ''; ?></div>
                            <?php endif; ?>
                            <div>👥 Capacity: <strong><?php echo (int) $venue['capacity']; ?> people</strong></div>
                            <?php if ($venueLat && $venueLng): ?>
                                <div>🗺️ Lat <?php echo number_format((float) $venueLat, 4); ?>, Lng <?php echo number_format((float) $venueLng, 4); ?></div>
                            <?php endif; ?>
                            <?php if (!empty($venue['description'])): ?>
                                <div style="margin-top: 8px; color: #64748b; line-height: 1.4;"><?php echo h($venue['description']); ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="venue-actions">
                            <form method="POST" class="form" novalidate>
                                <?php echo appCsrfInput(); ?>
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="venue_id" value="<?php echo (int) $venue['id']; ?>">
                                <?php if ($_SESSION['user_role'] === 'super_admin'): ?>
                                    <input type="hidden" name="org" value="<?php echo (int) $organization_id; ?>">
                                <?php endif; ?>
                                <label>
                                    Venue Name
                                    <input type="text" name="name" value="<?php echo h($venue['name']); ?>" required>
                                </label>
                                <label>
                                    Address
                                    <input type="text" name="address" value="<?php echo h($venue['address']); ?>">
                                </label>
                                <label>
                                    City
                                    <input type="text" name="city" value="<?php echo h($venue['city']); ?>">
                                </label>
                                <label>
                                    Capacity
                                    <input type="number" name="capacity" value="<?php echo (int) $venue['capacity']; ?>" min="1" required>
                                </label>
                                <div class="form-row">
                                    <label>
                                        Latitude
                                        <input class="venue-lat-display" type="text" inputmode="decimal" value="<?php echo $venueLat ? h($venueLat) : ''; ?>" readonly>
                                        <input class="venue-lat" type="hidden" name="latitude" value="<?php echo $venueLat ? h($venueLat) : ''; ?>">
                                    </label>
                                    <label>
                                        Longitude
                                        <input class="venue-lng-display" type="text" inputmode="decimal" value="<?php echo $venueLng ? h($venueLng) : ''; ?>" readonly>
                                        <input class="venue-lng" type="hidden" name="longitude" value="<?php echo $venueLng ? h($venueLng) : ''; ?>">
                                    </label>
                                </div>
                                <div class="venue-map-tools" data-map-block>
                                    <label>Update Exact Venue on Map</label>
                                    <div class="venue-map-toolbar">
                                        <input type="text" data-map-search placeholder="Search location (free)">
                                        <button type="button" class="map-btn" data-map-find>Find</button>
                                        <button type="button" class="map-btn" data-map-current>Use My Location</button>
                                    </div>
                                    <div class="map-status" data-map-status>
                                        <?php echo ($venue['latitude'] && $venue['longitude']) ? 'Venue location loaded. Drag marker or click map to adjust.' : 'Click the map or search to set exact venue coordinates.'; ?>
                                    </div>
                                    <div class="venue-map google-map-canvas" data-venue-map></div>
                                </div>
                                <label>
                                    Description
                                    <textarea name="description"><?php echo h($venue['description']); ?></textarea>
                                </label>
                                <button type="submit" class="btn muted">Save Changes</button>
                            </form>

                            <form method="POST" style="display: flex; gap: 8px;">
                                <?php echo appCsrfInput(); ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="venue_id" value="<?php echo (int) $venue['id']; ?>">
                                <?php if ($_SESSION['user_role'] === 'super_admin'): ?>
                                    <input type="hidden" name="org" value="<?php echo (int) $organization_id; ?>">
                                <?php endif; ?>
                                <button type="submit" class="btn danger" onclick="return confirm('Are you sure? This cannot be undone.');" style="flex: 1;">Delete Venue</button>
                            </form>
                        </div>
                    </article>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="venue-card" style="text-align: center; color: #64748b;">
                    <p>No venues created yet. Create your first venue to get started.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<script>
document.addEventListener("DOMContentLoaded", function() {
    document.querySelectorAll(".venue-lat-display, .venue-lng-display").forEach(function(input) {
        input.setAttribute("type", "text");
        input.removeAttribute("step");
        input.removeAttribute("min");
        input.removeAttribute("max");
    });

    if (typeof LocationPicker === "undefined") {
        return;
    }

    document.querySelectorAll("[data-map-block]").forEach(function(block, index) {
        const form = block.closest("form");
        const latInput = form ? form.querySelector(".venue-lat") : null;
        const lngInput = form ? form.querySelector(".venue-lng") : null;
        const latDisplay = form ? form.querySelector(".venue-lat-display") : null;
        const lngDisplay = form ? form.querySelector(".venue-lng-display") : null;
        const addressInput = form ? form.querySelector('input[name="address"]') : null;
        const cityInput = form ? form.querySelector('input[name="city"]') : null;
        const mapElement = block.querySelector("[data-venue-map]");

        if (!latInput || !lngInput || !mapElement) {
            return;
        }

        if (!mapElement.id) {
            mapElement.id = "venueMap" + index;
        }

        LocationPicker.registerPicker({
            mapElement: mapElement,
            latInput: latInput,
            lngInput: lngInput,
            latDisplay: latDisplay,
            lngDisplay: lngDisplay,
            searchInput: block.querySelector("[data-map-search]"),
            findButton: block.querySelector("[data-map-find]"),
            currentButton: block.querySelector("[data-map-current]"),
            statusElement: block.querySelector("[data-map-status]"),
            addressInput: addressInput,
            cityInput: cityInput,
            countryRestriction: "tz"
        });
    });
});
</script>

<?php renderAppShellEnd("venues"); ?>
