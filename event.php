<?php
include("includes/auth.php");
include("includes/db.php");
include("includes/app.php");

ensureEventSchema($conn);
appRequireRole(['organization_admin', 'event_organizer', 'attendee']);

$user_id = (int) $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_role = $_SESSION['user_role'] ?? 'attendee';
$user_org_id = isset($_SESSION['organization_id']) ? (int) $_SESSION['organization_id'] : 0;
$event_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$message = "";
$error = "";

if ($event_id <= 0) {
    die("Event not found.");
}

$eventQuery = $conn->query("SELECT * FROM events WHERE id=$event_id AND deleted = FALSE LIMIT 1");
$event = $eventQuery ? $eventQuery->fetch_assoc() : null;

if (!$event) {
    die("Event not found.");
}

if (is_numeric($event['location_lat'] ?? null) && is_numeric($event['location_lng'] ?? null)) {
    [$event['location_lat'], $event['location_lng']] = appNormalizeLatLng(
        (float) $event['location_lat'],
        (float) $event['location_lng']
    );
}

// Check organization access
if ((int) $event['organization_id'] !== $user_org_id) {
    die("Access denied: Event not found in your organization.");
}

$isAdmin = ((int) $event['created_by'] === $user_id) || ($user_role === 'organization_admin');
$organizerVenueOnly = in_array($user_role, ['event_organizer', 'organization_admin'], true);

// Handle delete action
if (isset($_POST['delete_event']) && $isAdmin) {
    if (!appVerifyCsrf()) {
        die("Security check failed.");
    }

    $deleteId = (int) ($_POST['delete_event'] ?? 0);

    if ($deleteId === $event_id) {
        $eventId = $event_id;

        $eventData = json_encode($event);

        $stmt = $conn->prepare("
            INSERT INTO deleted_events (original_event_id, event_name, deleted_by, reason, attendance_data_preserved, event_data)
            VALUES (?, ?, ?, 'Event deleted by organizer', TRUE, ?)
        ");
        $stmt->bind_param("isis", $eventId, $event['name'], $user_id, $eventData);

        if ($stmt->execute()) {
            $result = $conn->query("UPDATE events SET deleted = TRUE, deleted_at = NOW() WHERE id = $eventId");
            if ($result) {
                header("Location: events.php");
                exit();
            } else {
                die("Error deleting event: " . $conn->error);
            }
        } else {
            die("Error storing deleted event: " . $conn->error);
        }
    } else {
        die("Invalid delete request.");
    }
}

if ($isAdmin) {
    registerUserToEvent($conn, $user_id, $event_id);
}

if (isset($_POST['save_settings']) && $isAdmin) {
    if (!appVerifyCsrf()) {
        die("Security check failed.");
    }

    $name = trim($_POST['name'] ?? "");
    $description = trim($_POST['description'] ?? "");
    $date = $_POST['date'] ?? "";
    $time = $_POST['time'] ?? "";
    $end_time = $_POST['end_time'] ?? "";
    $attendance_start = $_POST['attendance_start'] ?? "";
    $attendance_end = $_POST['attendance_end'] ?? "";
    $registration_mode = $_POST['registration_mode'] ?? "self";
    $venue_id = (int) ($_POST['venue_id'] ?? 0);
    $venue_name = trim($_POST['venue_name'] ?? "");
    $venue_location = trim($_POST['venue_location'] ?? "");
    $max_distance_km = trim($_POST['max_distance_km'] ?? "");
    $locationLatValue = null;
    $locationLngValue = null;
    $target_audience = trim($_POST['target_audience'] ?? "all");
    if ($target_audience === "") {
        $target_audience = "all";
    }
    $access_code = trim($_POST['access_code'] ?? "");
    $registrant_list = trim($_POST['registrant_list'] ?? "");

    $scheduleError = appEventScheduleError($date, $time, $end_time, $attendance_start, $attendance_end);
    if ($scheduleError !== "") {
        $error = $scheduleError;
    } elseif (!in_array($target_audience, ['all', 'student', 'staff', 'guest'], true)) {
        $error = "Choose a valid target attendee type.";
    }

    if ($error === "" && $registration_mode === "code" && $access_code === "") {
        $access_code = generateAccessCode();
    }

    $maxDistanceValue = is_numeric($max_distance_km) && (float) $max_distance_km >= 0 ? (float) $max_distance_km : null;
    $registrants = parseRegistrantLines($registrant_list);
    $invitedEmailsText = implode("\n", array_map(function ($item) {
        return $item['email'];
    }, $registrants));

    if ($venue_id <= 0) {
        $venue_id = (int) ($event['venue_id'] ?? 0);
    }

    if ($organizerVenueOnly && $venue_id <= 0) {
        $error = "Please select a venue from the list.";
    } else {
        $venueStmt = $conn->prepare("SELECT id, name, address, city, latitude, longitude FROM venues WHERE id = ? AND organization_id = ? LIMIT 1");
        $venueStmt->bind_param("ii", $venue_id, $user_org_id);
        $venueStmt->execute();
        $venueResult = $venueStmt->get_result();
        if ($venue_id > 0 && (!$venueResult || $venueResult->num_rows === 0)) {
            $error = "Please choose a valid venue from your organization.";
        } elseif ($venue_id > 0) {
            $venue = $venueResult->fetch_assoc();
            if (!is_numeric($venue['latitude']) || !is_numeric($venue['longitude'])) {
                $error = "This venue has no map location yet. Ask your Organization Admin to update it in Venues.";
            } else {
                $venue_name = $venue['name'];
                $venue_location = trim(implode(', ', array_filter([$venue['address'] ?? '', $venue['city'] ?? ''])));
                [$locationLatValue, $locationLngValue] = appNormalizeLatLng(
                    (float) $venue['latitude'],
                    (float) $venue['longitude']
                );
            }
        }
    }

    if ($error !== "") {
        // Skip update when validation fails.
    } else {
    $attendanceStartValue = appTimeValue($attendance_start) !== '' ? appTimeValue($attendance_start) : null;
    $attendanceEndValue = appTimeValue($attendance_end) !== '' ? appTimeValue($attendance_end) : null;
    $venueIdValue = $venue_id > 0 ? $venue_id : null;
    $stmt = $conn->prepare("
        UPDATE events
        SET name=?, description=?, date=?, time=?, end_time=?, attendance_start=?, attendance_end=?,
            venue_id=?, venue_name=?, venue_location=?, location_lat=?, location_lng=?, max_distance_km=?, target_audience=?, registration_mode=?, access_code=?, invited_emails=?
        WHERE id=?
    ");
    $stmt->bind_param(
        "sssssssissdddssssi",
        $name,
        $description,
        $date,
        $time,
        $end_time,
        $attendanceStartValue,
        $attendanceEndValue,
        $venueIdValue,
        $venue_name,
        $venue_location,
        $locationLatValue,
        $locationLngValue,
        $maxDistanceValue,
        $target_audience,
        $registration_mode,
        $access_code,
        $invitedEmailsText,
        $event_id
    );

    if ($stmt->execute()) {
        if ($registration_mode === "code" && !empty($registrants)) {
            foreach ($registrants as $registrant) {
                upsertPrivateRegistrant($conn, $event_id, $registrant);
                sendEventAccessCodeEmail($registrant['name'], $registrant['email'], $event, $access_code);
            }
        }
        $message = "Event settings updated.";
        $eventQuery = $conn->query("SELECT * FROM events WHERE id=$event_id AND deleted = FALSE LIMIT 1");
        $event = $eventQuery ? $eventQuery->fetch_assoc() : $event;
    } else {
        $error = "Settings could not be saved.";
    }
    }
}

if (isset($_POST['join_self'])) {
    if (!appVerifyCsrf()) {
        die("Security check failed.");
    }

    if (eventLifecycleStatus($event) === 'ended') {
        $error = "Registration is closed because this event has already ended.";
    } elseif (registrationExists($conn, $user_id, $event_id)) {
        $message = "You are already registered for this event.";
    } elseif (eventRegistrationMode($event) !== 'self') {
        $error = "This event needs an access code.";
    } else {
        if (registerUserToEvent($conn, $user_id, $event_id)) {
            appSetFlash('success', 'Registration completed. You can now access this event.');
            header("Location: event.php?id=" . $event_id);
            exit();
        } else {
            $error = "Failed to register for the event. Please try again or contact support.";
            error_log("Event registration failed for user $user_id on event $event_id");
        }
    }
}

if (isset($_POST['join_code'])) {
    if (!appVerifyCsrf()) {
        die("Security check failed.");
    }

    if (eventLifecycleStatus($event) === 'ended') {
        $error = "Registration is closed because this event has already ended.";
    } elseif (registrationExists($conn, $user_id, $event_id)) {
        $message = "You are already registered for this event.";
    } elseif (trim($_POST['access_code'] ?? '') !== (string) ($event['access_code'] ?? '')) {
        $error = "Wrong access code.";
    } else {
        if (registerUserToEvent($conn, $user_id, $event_id)) {
            appSetFlash('success', 'Access granted and registration completed.');
            header("Location: event.php?id=" . $event_id);
            exit();
        } else {
            $error = "Failed to register with access code. Please try again.";
            error_log("Access code registration failed for user $user_id on event $event_id");
        }
    }
}

$isRegistered = registrationExists($conn, $user_id, $event_id);
$registrationMode = eventRegistrationMode($event);
$lifecycle = eventLifecycleStatus($event);
$lifecycleLabel = eventLifecycleLabel($event);
$windowState = attendanceWindowState($event);
$attendanceOpenTime = appEventTimeOrDefault($event['attendance_start'] ?? '', $event['time'] ?? '');
$attendanceCloseTime = appEventTimeOrDefault($event['attendance_end'] ?? '', appEventTimeOrDefault($event['end_time'] ?? '', $event['time'] ?? ''));

// Get user's personal access code if registered
$userAccessCode = '';
if ($isRegistered && $registrationMode === 'code') {
    $participantResult = $conn->query("SELECT access_code FROM participants WHERE user_id=$user_id AND event_id=$event_id LIMIT 1");
    if ($participantResult && $participantResult->num_rows > 0) {
        $userAccessCode = $participantResult->fetch_assoc()['access_code'] ?? '';
    }
}
$registeredCountResult = $conn->query("SELECT COUNT(*) AS total FROM participants WHERE event_id=$event_id");
$registeredCount = $registeredCountResult ? (int) $registeredCountResult->fetch_assoc()['total'] : 0;
$attendedCountResult = $conn->query("SELECT COUNT(*) AS total FROM attendance WHERE event_id=$event_id");
$attendedCount = $attendedCountResult ? (int) $attendedCountResult->fetch_assoc()['total'] : 0;
$eventCoords = appEventCoordinates($event);
$hasMapLocation = eventHasMapLocation($event);
$directionsUrl = eventDirectionsUrl($event);
$mapEmbedUrl = eventMapEmbedUrl($event, $conn);
$registrantRowsResult = $conn->query("SELECT participant_name, participant_email, participant_phone, invite_status FROM participants WHERE event_id=$event_id ORDER BY invited_at DESC, id DESC");
$registrantRows = [];
if ($registrantRowsResult) {
    while ($registrant = $registrantRowsResult->fetch_assoc()) {
        $registrantRows[] = $registrant;
    }
}
$registrantListValue = implode("\n", array_map(function ($item) {
    return trim(($item['participant_name'] ?? '') . ', ' . ($item['participant_email'] ?? '') . ', ' . ($item['participant_phone'] ?? ''));
}, $registrantRows));

$attendanceRows = $conn->query("
    SELECT u.name, u.email, a.time
    FROM attendance a
    JOIN users u ON u.id = a.user_id
    WHERE a.event_id = $event_id
    ORDER BY a.time DESC
");

$organizationVenues = [];
if ($isAdmin && $organizerVenueOnly) {
    $venuesStmt = $conn->prepare("SELECT id, name, address, city, latitude, longitude FROM venues WHERE organization_id = ? ORDER BY name ASC");
    $venuesStmt->bind_param("i", $user_org_id);
    $venuesStmt->execute();
    $venuesResult = $venuesStmt->get_result();
    if ($venuesResult) {
        while ($venueRow = $venuesResult->fetch_assoc()) {
            $organizationVenues[] = $venueRow;
        }
    }
}
?>
<?php
$pageCss = appLocationMapsHeadHtml($conn) . '<style>
.nav{
    display:flex;
    justify-content:space-between;
    gap:12px;
    align-items:center;
    margin-bottom:16px;
}

.nav a{
    text-decoration:none;
    font-weight:800;
}

.back{
    color:#1d4ed8;
}

.hero{
    display:grid;
    grid-template-columns:280px 1fr;
    gap:22px;
    background:#fff;
    border:1px solid #dce5f1;
    border-radius:24px;
    overflow:hidden;
    box-shadow:0 20px 40px rgba(15, 23, 42, 0.08);
}

.hero img{
    width:100%;
    height:100%;
    min-height:260px;
    object-fit:cover;
}

.hero-body{
    padding:24px;
}

.badges{
    display:flex;
    flex-wrap:wrap;
    gap:8px;
}

.badge{
    padding:7px 12px;
    border-radius:999px;
    font-size:12px;
    font-weight:800;
}

.admin{background:#dbeafe;color:#1d4ed8;}
.reg-open{background:#ecfdf5;color:#15803d;}
.reg-code{background:#fff7ed;color:#c2410c;}
.live{background:#fee2e2;color:#b91c1c;}
.upcoming{background:#eff6ff;color:#2563eb;}
.ended{background:#f1f5f9;color:#475569;}

.hero-body h1{
    margin:14px 0 8px;
    font-size:38px;
    line-height:1.05;
}

.hero-body p{
    margin:0;
    color:#64748b;
    line-height:1.7;
}

.grid{
    margin-top:22px;
    display:grid;
    grid-template-columns:repeat(4, minmax(0, 1fr));
    gap:14px;
}

.stat{
    background:#fff;
    border:1px solid #dce5f1;
    border-radius:18px;
    padding:18px;
}

.stat small{
    color:#64748b;
    font-weight:700;
    display:block;
    margin-bottom:8px;
}

.stat strong{
    font-size:22px;
}

.section{
    margin-top:22px;
    background:#fff;
    border:1px solid #dce5f1;
    border-radius:22px;
    padding:22px;
    box-shadow:0 16px 34px rgba(15, 23, 42, 0.06);
}

.section h2{
    margin:0 0 8px;
    font-size:24px;
}

.section p{
    margin:0;
    color:#64748b;
}

.message, .error{
    margin-top:16px;
    padding:14px 16px;
    border-radius:14px;
    font-weight:700;
}

.message{background:#ecfdf5;color:#166534;}
.error{background:#fef2f2;color:#b91c1c;}

.form-grid{
    margin-top:18px;
    display:grid;
    grid-template-columns:repeat(2, minmax(0, 1fr));
    gap:16px;
}

.field{
    display:flex;
    flex-direction:column;
    gap:8px;
}

.field.full{
    grid-column:1 / -1;
}

label{
    font-size:14px;
    font-weight:700;
    color:#334155;
}

input, textarea, select{
    width:100%;
    border:1px solid #dce5f1;
    border-radius:14px;
    padding:13px 14px;
    font-size:14px;
    background:#f8fbff;
}

textarea{
    min-height:110px;
    resize:vertical;
}

.actions{
    margin-top:18px;
    display:flex;
    gap:12px;
    flex-wrap:wrap;
}

button, .button{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    border:none;
    border-radius:14px;
    padding:13px 18px;
    font-weight:800;
    cursor:pointer;
    text-decoration:none;
}

.primary{
    background:linear-gradient(180deg, #2563eb 0%, #1d4ed8 100%);
    color:#fff;
}

.deleted{
    background:#6b7280;
    color:#fff;
}

.section-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:12px;
}

.header-buttons{
    display:flex;
    gap:8px;
    align-items:center;
}

.edit-button{
    background:#2563ff;
    color:#fff;
    border:none;
    border-radius:8px;
    padding:8px 16px;
    font-size:14px;
    font-weight:600;
    cursor:pointer;
    transition:background 0.2s;
}

.edit-button:hover{
    background:#1d4ed8;
}

.delete-header{
    background:#dc2626;
    color:#fff;
    text-decoration:none;
    border-radius:8px;
    padding:8px 16px;
    font-size:14px;
    font-weight:600;
    transition:background 0.2s;
}

.delete-header:hover{
    background:#b91c1c;
}

.dark{
    background:#0f172a;
    color:#fff;
}

.danger{
    background:#dc2626;
    color:#fff;
}

.danger:hover{
    background:#b91c1c;
}

.soft{
    background:#e2e8f0;
    color:#334155;
}

.mini-grid{
    margin-top:18px;
    display:grid;
    grid-template-columns:repeat(3, minmax(0, 1fr));
    gap:14px;
}

.mini{
    background:#f8fbff;
    border:1px solid #e5edf7;
    border-radius:16px;
    padding:16px;
}

.mini strong{
    display:block;
    margin-top:6px;
}

.map-panel{
    margin-top:20px;
    border:1px solid #dce5f1;
    border-radius:20px;
    overflow:hidden;
    background:#fff;
}

.map-panel #eventLocationMap{
    width:100%;
    height:320px;
}

.map-panel-foot{
    padding:14px 16px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:12px;
    flex-wrap:wrap;
    border-top:1px solid #e7edf7;
}

.map-panel-foot span{
    color:#64748b;
    font-size:13px;
    font-weight:700;
}

table{
    width:100%;
    border-collapse:collapse;
    margin-top:16px;
}

th, td{
    padding:14px 10px;
    text-align:left;
    border-bottom:1px solid #eef2f7;
}

th{
    font-size:12px;
    text-transform:uppercase;
    letter-spacing:0.04em;
    color:#64748b;
}

.location-tools,
.invite-tools{
    grid-column:1 / -1;
    border:1px solid #dce5f1;
    border-radius:18px;
    padding:16px;
    background:#f8fbff;
}

.location-toolbar{
    margin-top:12px;
    display:grid;
    grid-template-columns:minmax(0, 1fr) auto;
    gap:10px;
    align-items:center;
}

.ghost{
    background:#e0e7ff;
    color:#3730a3;
}

.inline-map{
    margin-top:14px;
    border-radius:16px;
    overflow:hidden;
    border:1px solid #dbe3ef;
    min-height:118px;
    background:#fff;
}

.inline-map #adminEventMap{
    width:100%;
    height:118px;
}


.registrant-list{
    margin-top:18px;
    display:grid;
    gap:10px;
}

.registrant-item{
    display:flex;
    justify-content:space-between;
    gap:12px;
    align-items:flex-start;
    border:1px solid #e5edf7;
    border-radius:16px;
    padding:14px;
    background:#fff;
}

.registrant-item strong{
    display:block;
}

.registrant-item span{
    display:block;
    margin-top:4px;
    color:#64748b;
    font-size:13px;
}

@media (max-width: 920px){
    .hero{
        grid-template-columns:1fr;
    }

    .grid, .mini-grid, .form-grid{
        grid-template-columns:1fr 1fr;
    }
}

@media (max-width: 640px){
    .grid, .mini-grid, .form-grid{
        grid-template-columns:1fr;
    }

    .hero-body h1{
        font-size:30px;
    }

    .location-toolbar{
        grid-template-columns:1fr;
    }

    .ghost{
        width:100%;
    }
}

/* Private Event Registrants Modal Styles */
.modal-overlay {
    display: none;
    position: fixed;
    top: 0; left: 0;
    width: 100%; height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 1000;
    align-items: center;
    justify-content: center;
}

.modal-content {
    background: white;
    border-radius: 16px;
    max-width: 500px;
    width: 90%;
    max-height: 80vh;
    overflow-y: auto;
    box-shadow: 0 20px 40px rgba(0,0,0,0.2);
    animation: modalSlideIn 0.3s ease-out;
}

@keyframes modalSlideIn {
    from {
        opacity: 0;
        transform: translateY(-50px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid #e5e7eb;
}

.modal-title {
    margin: 0;
    font-size: 18px;
    font-weight: 700;
    color: #1f2937;
}

.modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #6b7280;
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: all 0.2s;
}

.modal-close:hover {
    background: #f3f4f6;
    color: #374151;
}

.modal-body {
    padding: 20px;
}

.modal-form {
    margin-bottom: 20px;
}

.modal-field {
    margin-bottom: 16px;
}

.modal-field label {
    display: block;
    margin-bottom: 6px;
    font-weight: 600;
    color: #374151;
    font-size: 14px;
}

.modal-field input {
    width: 100%;
    padding: 10px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 14px;
    transition: border-color 0.2s;
}

.modal-field input:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.modal-help {
    font-size: 12px;
    color: #6b7280;
    margin-top: 4px;
}

.modal-actions {
    display: flex;
    gap: 10px;
    padding: 20px;
    border-top: 1px solid #e5e7eb;
    background: #f9fafb;
}

.modal-btn {
    padding: 10px 16px;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.modal-btn-primary {
    background: #3b82f6;
    color: white;
}

.modal-btn-primary:hover {
    background: #2563eb;
}

.modal-btn-secondary {
    background: #e5e7eb;
    color: #374151;
}

.modal-btn-secondary:hover {
    background: #d1d5db;
}

.modal-registrants-list {
    border-top: 1px solid #e5e7eb;
    padding: 20px;
    background: #f9fafb;
}

.registrant-item {
    transition: all 0.2s;
}

.registrant-item:hover {
    background: #f3f4f6;
}

.invite-tools {
    margin-top: 20px;
}

.ghost-button {
    background: #f8fafc;
    border: 2px dashed #cbd5e1;
    color: #64748b;
    padding: 12px;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.ghost-button:hover {
    background: #f1f5f9;
    border-color: #94a3b8;
    color: #475569;
}

</style>
';

renderAppShellStart($conn, [
    "title" => $event['name'],
    "active" => "events",
    "page_title" => $event['name'],
    "page_subtitle" => "Open the event, manage access, and monitor attendance from one place.",
    "search_placeholder" => "Search events...",
    "show_page_head" => false,
    "extra_head" => $pageCss,
]);
?>
    <div class="nav">
        <a href="events.php" class="back">← Back to events</a>
        <a href="dashboard.php" class="back">Dashboard</a>
    </div>

    <section class="hero">
        <img src="<?php echo h(eventImagePath($event['image'])); ?>" alt="<?php echo h($event['name']); ?>">

        <div class="hero-body">
            <div class="badges">
                <?php if ($isAdmin): ?><span class="badge admin">Admin of this event</span><?php endif; ?>
                <span class="badge <?php echo $registrationMode === 'code' ? 'reg-code' : 'reg-open'; ?>">
                    <?php echo $registrationMode === 'code' ? 'Access code required' : 'Self registration enabled'; ?>
                </span>
                <span class="badge <?php echo h($lifecycle); ?>"><?php echo h($lifecycleLabel); ?></span>
            </div>

            <h1><?php echo h($event['name']); ?></h1>
            <p><?php echo h($event['description'] ?: 'No description added yet.'); ?></p>

            <div class="mini-grid">
                <div class="mini">
                    <small>Event Starts</small>
                    <strong><?php echo h(formatEventDate($event['date'])); ?> • <?php echo h(formatEventTime($event['time'])); ?></strong>
                </div>
                <div class="mini">
                    <small>Attendance Window</small>
                    <strong><?php echo h(formatEventTime($attendanceOpenTime)); ?> - <?php echo h(formatEventTime($attendanceCloseTime)); ?></strong>
                </div>
                <div class="mini">
                    <small>Shared Location</small>
                    <strong><?php echo h($event['venue_location'] ?: 'Not shared yet'); ?></strong>
                </div>
            </div>

            <?php if ($hasMapLocation): ?>
                <div class="map-panel">
                    <div id="eventLocationMap" class="google-map-canvas"></div>
                    <div class="map-panel-foot">
                        <span>Latitude <?php echo h(number_format((float) $eventCoords['lat'], 5)); ?>, Longitude <?php echo h(number_format((float) $eventCoords['lng'], 5)); ?></span>
                        <a href="<?php echo h($directionsUrl); ?>" target="_blank" rel="noopener noreferrer" class="dark button">Open Directions</a>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($message !== ""): ?><div class="message"><?php echo h($message); ?></div><?php endif; ?>
            <?php if ($error !== ""): ?><div class="error"><?php echo h($error); ?></div><?php endif; ?>
            <?php if (isset($registrationDetails)): ?><?php echo $registrationDetails; ?><?php endif; ?>
            <?php if ($lifecycle === 'ended' && $isAdmin): ?>
                <div class="message">This event is currently marked as Ended. If that happened by mistake, update the event time or attendance window below and save again.</div>
            <?php endif; ?>
        </div>
    </section>

    <section class="grid">
        <div class="stat"><small>Registered</small><strong><?php echo $registeredCount; ?></strong></div>
        <div class="stat"><small>Attended</small><strong><?php echo $attendedCount; ?></strong></div>
        <div class="stat"><small>Venue</small><strong><?php echo h($event['venue_name'] ?: 'Not set'); ?></strong></div>
        <div class="stat"><small>Audience</small><strong><?php echo h($event['target_audience'] ?: 'Open to all'); ?></strong></div>
        <div class="stat"><small>Scan Radius</small><strong><?php echo !empty($event['max_distance_km']) ? h($event['max_distance_km'] . ' km') : 'No limit'; ?></strong></div>
    </section>

    <?php if ($isAdmin): ?>
        <section class="section">
            <div class="section-header">
                <h2>Event Settings</h2>
                <div class="header-buttons">
                    <button type="button" id="toggleSettings" class="edit-button">
                        <span id="toggleText">Edit Settings</span>
                    </button>
                    <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this event? Attendance data will be preserved but the event will be hidden from public.')">
                        <?php echo appCsrfInput(); ?>
                        <button type="submit" name="delete_event" value="<?php echo $event_id; ?>" class="danger delete-header">Delete Event</button>
                    </form>
                </div>
            </div>
            <p id="settingsDescription">Adjust event time, attendance window, who should attend, and how people access this event.</p>

            <div id="adminSettingsForm" style="display: none;">
                <form method="POST" class="form-grid">
                <?php echo appCsrfInput(); ?>
                <div class="field full">
                    <label for="name">Event Name</label>
                    <input id="name" type="text" name="name" required value="<?php echo h($event['name']); ?>">
                </div>

                <div class="field full">
                    <label for="description">Description</label>
                    <textarea id="description" name="description"><?php echo h($event['description']); ?></textarea>
                </div>

                <div class="field">
                    <label for="date">Event Date</label>
                    <input id="date" type="date" name="date" value="<?php echo h($event['date']); ?>">
                </div>

                <div class="field">
                    <label for="time">Start Time</label>
                    <input id="time" type="time" name="time" value="<?php echo h(substr((string) $event['time'], 0, 5)); ?>">
                </div>

                <div class="field">
                    <label for="end_time">End Time</label>
                    <input id="end_time" type="time" name="end_time" required value="<?php echo h(substr((string) $event['end_time'], 0, 5)); ?>">
                </div>

                <div class="field">
                    <label for="target_audience">Target Attendees</label>
                    <?php $selectedAudience = $event['target_audience'] ?: 'all'; ?>
                    <select id="target_audience" name="target_audience" required>
                        <option value="all" <?php echo $selectedAudience === 'all' ? 'selected' : ''; ?>>All attendees</option>
                        <option value="student" <?php echo $selectedAudience === 'student' ? 'selected' : ''; ?>>Students only</option>
                        <option value="staff" <?php echo $selectedAudience === 'staff' ? 'selected' : ''; ?>>Staff only</option>
                        <option value="guest" <?php echo $selectedAudience === 'guest' ? 'selected' : ''; ?>>Guests only</option>
                    </select>
                </div>

                <div class="field">
                    <label for="attendance_start">Attendance Opens</label>
                    <input id="attendance_start" type="time" name="attendance_start" value="<?php echo h(appTimeValue($event['attendance_start'] ?? '')); ?>">
                </div>

                <div class="field">
                    <label for="attendance_end">Attendance Closes</label>
                    <input id="attendance_end" type="time" name="attendance_end" value="<?php echo h(appTimeValue($event['attendance_end'] ?? '')); ?>">
                </div>

                <?php if ($organizerVenueOnly): ?>
                <div class="field full">
                    <label for="venue_id">Select Venue</label>
                    <select id="venue_id" name="venue_id" required>
                        <option value="">-- Choose venue --</option>
                        <?php foreach ($organizationVenues as $venueOption): ?>
                            <?php
                            $addressParts = array_filter([$venueOption['address'] ?? '', $venueOption['city'] ?? '']);
                            $address = implode(', ', $addressParts);
                            $displayText = $venueOption['name'];
                            if ($address !== '') {
                                $displayText .= ' - ' . $address;
                            }
                            $selected = (int) ($event['venue_id'] ?? 0) === (int) $venueOption['id'] ? 'selected' : '';
                            ?>
                            <option value="<?php echo (int) $venueOption['id']; ?>"
                                data-name="<?php echo h($venueOption['name']); ?>"
                                data-address="<?php echo h($address); ?>"
                                data-lat="<?php echo h($venueOption['latitude'] ?? ''); ?>"
                                data-lng="<?php echo h($venueOption['longitude'] ?? ''); ?>"
                                <?php echo $selected; ?>><?php echo h($displayText); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="inline-note">Event location comes from the venue. Your Organization Admin manages venue coordinates in Venues.</div>
                    <p id="venueSelectMessage" class="inline-note" style="display:none;margin-top:8px;color:#b91c1c;font-weight:700;"></p>
                </div>

                <div class="field">
                    <label for="venue_name">Venue Name</label>
                    <input id="venue_name" type="text" name="venue_name" value="<?php echo h($event['venue_name']); ?>" readonly>
                </div>

                <div class="field">
                    <label for="venue_location">Venue Address</label>
                    <input id="venue_location" type="text" name="venue_location" value="<?php echo h($event['venue_location']); ?>" readonly>
                </div>
                <?php else: ?>
                <div class="field">
                    <label for="venue_name">Venue Name</label>
                    <input id="venue_name" type="text" name="venue_name" value="<?php echo h($event['venue_name']); ?>">
                </div>

                <div class="field">
                    <label for="venue_location">Shared Location</label>
                    <input id="venue_location" type="text" name="venue_location" value="<?php echo h($event['venue_location']); ?>">
                </div>
                <?php endif; ?>

                <div class="field">
                    <label for="max_distance_km">Allowed Scan Radius (km)</label>
                    <input id="max_distance_km" type="number" name="max_distance_km" min="0" step="0.01" value="<?php echo h($event['max_distance_km'] ?? ''); ?>">
                    <div class="inline-note">Leave empty or 0 to allow scans from any distance.</div>
                </div>

                <?php if ($organizerVenueOnly): ?>
                <?php $showVenueMapPreview = (int) ($event['venue_id'] ?? 0) > 0 && $hasMapLocation; ?>
                <div id="venueMapPreview" class="location-tools" style="display:<?php echo $showVenueMapPreview ? 'block' : 'none'; ?>;">
                    <label>Venue Location Preview</label>
                    <p style="margin-top:8px;">Read-only map for the selected venue. You cannot edit the location here.</p>
                    <div id="locationStatus" class="message" style="display:block;margin-top:14px;background:#eff6ff;color:#1d4ed8;"></div>
                    <div class="inline-map">
                        <div id="adminEventMap" class="google-map-canvas"></div>
                    </div>
                </div>
                <?php endif; ?>

                <div class="field">
                    <label for="registration_mode">Access Mode</label>
                    <select id="registration_mode" name="registration_mode">
                        <option value="self" <?php echo $registrationMode === 'self' ? 'selected' : ''; ?>>Self registration</option>
                        <option value="code" <?php echo $registrationMode === 'code' ? 'selected' : ''; ?>>Access code</option>
                    </select>
                </div>

                <div class="field">
                    <label for="access_code">Access Code</label>
                    <input id="access_code" type="text" name="access_code" value="<?php echo h($event['access_code']); ?>">
                </div>

                <div class="invite-tools">
                    <label>Private Event Registrants</label>
                    <div class="inline-note">Add specific people who can register for this private event.</div>
                    <button type="button" class="ghost-button" id="openRegistrantModal" style="width: 100%; margin-top: 10px;">
                        + Add Private Registrants
                    </button>
                    <div id="registrantCount" class="inline-note" style="margin-top: 8px; display: none;">
                        <span id="registrantCountText">0</span> registrant(s) added
                    </div>
                </div>
                <input type="hidden" id="registrant_list" name="registrant_list" value="<?php echo h($registrantListValue); ?>">

                <div class="actions">
                    <button type="submit" name="save_settings" class="primary">Save Settings</button>
                    <a href="attendance.php?id=<?php echo $event_id; ?>" class="dark button">View Attendance</a>
                    <?php if ($windowState === 'open'): ?>
                        <a href="generate-qr.php?id=<?php echo $event_id; ?>" class="dark button">Display Live QR</a>
                    <?php endif; ?>
                </div>
            </form>
    </div>

    <!-- Private Event Registrants Modal -->
    <div id="registrantModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Add Private Event Registrants</h3>
                <button class="modal-close" id="closeModal">&times;</button>
            </div>
            <div class="modal-body">
                <p>Add specific people who can register for this private event. They will receive the access code via email.</p>
                
                <div class="modal-form">
                    <div class="modal-field">
                        <label for="modalRegistrantName">Full Name</label>
                        <input type="text" id="modalRegistrantName" placeholder="Enter registrant full name">
                        <div class="modal-help">Example: Amina Yusuf</div>
                    </div>
                    
                    <div class="modal-field">
                        <label for="modalRegistrantEmail">Email Address</label>
                        <input type="email" id="modalRegistrantEmail" placeholder="Enter email address">
                        <div class="modal-help">Example: amina@example.com</div>
                    </div>
                    
                    <div class="modal-field">
                        <label for="modalRegistrantPhone">Phone Number</label>
                        <input type="tel" id="modalRegistrantPhone" placeholder="Enter phone number">
                        <div class="modal-help">Example: 0712345678</div>
                    </div>
                    
                    <div class="modal-field">
                        <label for="modalAccessCode">Access Code (Optional)</label>
                        <input type="text" id="modalAccessCode" placeholder="Leave blank to auto-generate">
                        <div class="modal-help">If empty, system will create a unique code automatically</div>
                    </div>
                </div>
            </div>
            <div class="modal-actions">
                <button class="modal-btn modal-btn-secondary" id="cancelModal">Cancel</button>
                <button class="modal-btn modal-btn-secondary" id="addAnotherRegistrant">+ Add Another</button>
                <button class="modal-btn modal-btn-primary" id="saveRegistrants">Save All Registrants</button>
            </div>
            
            <!-- Current Registrants List -->
            <div class="modal-registrants-list" id="registrantsList" style="margin-top: 20px; display: none;">
                <h4 style="margin: 0 0 12px 0; font-size: 16px; color: #374151;">Current Registrants:</h4>
                <div id="registrantsListContent"></div>
            </div>
        </div>
    </div>

        </section>
    <?php endif; ?>

    <section class="section">
        <h2><?php echo $isAdmin ? 'Event Control' : 'Event Access'; ?></h2>
        <p>
            <?php if ($isAdmin): ?>
                You are the admin for this event. You can manage settings, display the live QR, and review attendance progress here.
            <?php elseif ($isRegistered): ?>
                You have already joined this event. When the attendance window opens, the scan button appears here automatically.
            <?php else: ?>
                Register or enter the access code to join this event. Once attendance opens, the scan button becomes available.
            <?php endif; ?>
        </p>

        <div class="actions">
            <?php if (!$isAdmin && !$isRegistered && $lifecycle === 'ended'): ?>
                <div class="message">Registration is closed because this event has already ended.</div>
            <?php elseif (!$isAdmin && !$isRegistered && $registrationMode === 'self'): ?>
                <form method="POST">
                    <?php echo appCsrfInput(); ?>
                    <button type="submit" name="join_self" class="primary">Register for Event</button>
                </form>
            <?php elseif (!$isAdmin && !$isRegistered && $registrationMode === 'code'): ?>
                <form method="POST" style="display:flex;gap:10px;flex-wrap:wrap;width:100%;margin-top:16px;">
                    <?php echo appCsrfInput(); ?>
                    <input type="text" name="access_code" placeholder="Enter access code" style="flex:1;min-width:220px;">
                    <button type="submit" name="join_code" class="primary">Access Event</button>
                </form>
            <?php elseif ($isRegistered || $isAdmin): ?>
                <?php if ($registrationMode === 'code' && !empty($userAccessCode) && !$isAdmin): ?>
                    <div class="message success" style="background: #e8f5e9; border: 1px solid #4caf50; padding: 15px; border-radius: 8px; margin-bottom: 15px;">
                        <strong>✅ Your Personal Access Code:</strong><br>
                        <code style="font-size: 18px; font-weight: bold; color: #2e7d32;"><?php echo h($userAccessCode); ?></code><br>
                        <small>This code has been sent to your email. Keep it safe!</small>
                    </div>
                <?php endif; ?>
                
                <?php if ($windowState === 'before'): ?>
                    <div class="message">You have already joined this event. The scan button will appear here at <?php echo h(formatEventTime($attendanceOpenTime)); ?>.</div>
                <?php elseif ($windowState === 'open'): ?>
                    <a href="scan.php?id=<?php echo $event_id; ?>" class="primary button">Scan Attendance</a>
                    <?php if ($isAdmin): ?><a href="generate-qr.php?id=<?php echo $event_id; ?>" class="dark button">Open Live QR</a><?php endif; ?>
                <?php else: ?>
                    <div class="message">Attendance window has closed for this event.</div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </section>

    <?php if ($isAdmin || $lifecycle === 'ended'): ?>
        <section class="section">
            <h2>Attendance Progress</h2>
            <p>Once the event is over, this becomes your quick review table for the people who checked in.</p>

            <table>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Time</th>
                </tr>
                <?php if ($attendanceRows && $attendanceRows->num_rows > 0): ?>
                    <?php while ($row = $attendanceRows->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo h($row['name']); ?></td>
                            <td><?php echo h($row['email']); ?></td>
                            <td><?php echo h($row['time']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3">No attendance records yet.</td>
                    </tr>
                <?php endif; ?>
            </table>
        </section>
    <?php endif; ?>

    <?php if ($isAdmin): ?>
        <section class="section">
            <h2>Registered / Invited People</h2>
            <p>These are the people already known for this event before attendance scanning starts.</p>
            <div class="registrant-list">
                <?php if (!empty($registrantRows)): ?>
                    <?php foreach ($registrantRows as $registrant): ?>
                        <div class="registrant-item">
                            <div>
                                <strong><?php echo h($registrant['participant_name'] ?: 'No name'); ?></strong>
                                <span><?php echo h($registrant['participant_email'] ?: 'No email'); ?></span>
                                <span><?php echo h($registrant['participant_phone'] ?: 'No phone'); ?></span>
                            </div>
                            <span class="badge <?php echo ($registrant['invite_status'] ?? '') === 'invited' ? 'upcoming' : 'admin'; ?>">
                                <?php echo h(ucfirst($registrant['invite_status'] ?: 'registered')); ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="mini">No invited or registered list yet.</div>
                <?php endif; ?>
            </div>
            </div>
        </section>
    <?php endif; ?>
<script>
(function() {
    const accessCodeInput = document.getElementById("access_code");
    const modeSelect = document.getElementById("registration_mode");
    const venueSelect = document.getElementById("venue_id");
    const venueNameInput = document.getElementById("venue_name");
    const venueLocationInput = document.getElementById("venue_location");
    const locationStatus = document.getElementById("locationStatus");
    const mapPreview = document.getElementById("venueMapPreview");
    const venueSelectMessage = document.getElementById("venueSelectMessage");
    const dateInput = document.getElementById("date");
    const startInput = document.getElementById("time");
    const endInput = document.getElementById("end_time");
    const attendanceStartInput = document.getElementById("attendance_start");
    const attendanceEndInput = document.getElementById("attendance_end");

    function generateCode() {
        return String(Math.floor(100000 + Math.random() * 900000));
    }

    function pad(value) {
        return String(value).padStart(2, "0");
    }

    function todayValue() {
        const now = new Date();
        return now.getFullYear() + "-" + pad(now.getMonth() + 1) + "-" + pad(now.getDate());
    }

    function currentTimeValue() {
        const now = new Date();
        return pad(now.getHours()) + ":" + pad(now.getMinutes());
    }

    function addMinutes(time, minutes) {
        if (!time) {
            return "";
        }

        const parts = time.split(":").map(Number);
        const date = new Date();
        date.setHours(parts[0] || 0, parts[1] || 0, 0, 0);
        date.setMinutes(date.getMinutes() + minutes);
        return pad(date.getHours()) + ":" + pad(date.getMinutes());
    }

    function maxTime() {
        const values = Array.prototype.slice.call(arguments).filter(Boolean);
        return values.sort().pop() || "";
    }

    function syncTimeLimits() {
        const today = todayValue();
        const nowTime = currentTimeValue();
        const isToday = dateInput && dateInput.value === today;
        const startMin = isToday ? nowTime : "";
        const startValue = startInput ? startInput.value : "";
        const endMin = startValue ? addMinutes(startValue, 5) : startMin;
        const attendanceOpenMin = maxTime(startValue, startMin);
        const attendanceOpenValue = attendanceStartInput && attendanceStartInput.value ? attendanceStartInput.value : startValue;
        const attendanceCloseMin = attendanceOpenValue ? addMinutes(attendanceOpenValue, 1) : endMin;

        if (dateInput) {
            dateInput.min = today;
        }
        if (startInput) {
            startInput.min = startMin;
            if (startMin && startInput.value && startInput.value < startMin) {
                startInput.value = startMin;
            }
        }
        if (endInput) {
            endInput.min = endMin;
            if (endInput.value && endMin && endInput.value < endMin) {
                endInput.value = endMin;
            }
        }
        if (attendanceStartInput) {
            attendanceStartInput.min = attendanceOpenMin;
            attendanceStartInput.max = endInput && endInput.value ? addMinutes(endInput.value, -1) : "";
            if (attendanceStartInput.value && attendanceOpenMin && attendanceStartInput.value < attendanceOpenMin) {
                attendanceStartInput.value = attendanceOpenMin;
            }
        }
        if (attendanceEndInput) {
            attendanceEndInput.min = attendanceCloseMin;
            attendanceEndInput.max = endInput && endInput.value ? endInput.value : "";
            if (attendanceEndInput.value && attendanceCloseMin && attendanceEndInput.value < attendanceCloseMin) {
                attendanceEndInput.value = attendanceCloseMin;
            }
            if (attendanceEndInput.max && attendanceEndInput.value && attendanceEndInput.value > attendanceEndInput.max) {
                attendanceEndInput.value = attendanceEndInput.max;
            }
        }
    }

    [dateInput, startInput, endInput, attendanceStartInput, attendanceEndInput].forEach(function(input) {
        if (input) {
            input.addEventListener("change", syncTimeLimits);
        }
    });
    syncTimeLimits();
    window.setInterval(syncTimeLimits, 30000);

    if (modeSelect) {
        modeSelect.addEventListener("change", function() {
            if (modeSelect.value === "code" && accessCodeInput && !accessCodeInput.value.trim()) {
                accessCodeInput.value = generateCode();
            }
        });
    }

    function showVenueMap(lat, lng, venueName) {
        if (!mapPreview || typeof LocationPicker === "undefined") {
            return;
        }

        mapPreview.style.display = "";

        if (!LocationPicker.instances.adminEventMap) {
            LocationPicker.registerDisplay({
                mapElement: "adminEventMap",
                lat: lat,
                lng: lng,
                zoom: 16,
                interactive: false,
                statusElement: "locationStatus"
            });
        } else {
            LocationPicker.instances.adminEventMap.setPosition(lat, lng, 16, "Venue: " + venueName);
        }

        if (locationStatus) {
            locationStatus.textContent = "Showing location for " + venueName + " (read-only).";
        }
    }

    function applySelectedVenuePreview() {
        if (venueSelectMessage) {
            venueSelectMessage.style.display = "none";
            venueSelectMessage.textContent = "";
        }

        if (!venueSelect || !venueSelect.value) {
            if (mapPreview) {
                mapPreview.style.display = "none";
            }
            if (venueNameInput) {
                venueNameInput.value = "";
            }
            if (venueLocationInput) {
                venueLocationInput.value = "";
            }
            return;
        }

        const selected = venueSelect.options[venueSelect.selectedIndex];
        const venueName = selected.dataset.name || selected.textContent.trim();
        const venueAddress = selected.dataset.address || "";
        const coords = appNormalizeLatLng(selected.dataset.lat, selected.dataset.lng);

        if (venueNameInput) {
            venueNameInput.value = venueName;
        }
        if (venueLocationInput) {
            venueLocationInput.value = venueAddress;
        }

        if (!Number.isNaN(coords.lat) && !Number.isNaN(coords.lng) && coords.lat !== 0 && coords.lng !== 0) {
            showVenueMap(coords.lat, coords.lng, venueName);
        } else {
            if (mapPreview) {
                mapPreview.style.display = "none";
            }
            if (venueSelectMessage) {
                venueSelectMessage.textContent = "This venue has no map coordinates yet. Ask your Organization Admin to update it in Venues.";
                venueSelectMessage.style.display = "block";
            }
        }
    }

    if (venueSelect) {
        venueSelect.addEventListener("change", applySelectedVenuePreview);
        if (venueSelect.value) {
            applySelectedVenuePreview();
        }
    }

// Toggle admin settings visibility
    const toggleButton = document.getElementById("toggleSettings");
    const toggleText = document.getElementById("toggleText");
    const settingsForm = document.getElementById("adminSettingsForm");
    const settingsDescription = document.getElementById("settingsDescription");
    
    if (toggleButton && settingsForm) {
        toggleButton.addEventListener("click", function() {
            if (settingsForm.style.display === "none") {
                settingsForm.style.display = "block";
                toggleText.textContent = "Hide Settings";
                settingsDescription.style.display = "none";
            } else {
                settingsForm.style.display = "none";
                toggleText.textContent = "Edit Settings";
                settingsDescription.style.display = "block";
            }
        });
    }

    // Private Event Registrants Modal functionality
    const openRegistrantModal = document.getElementById("openRegistrantModal");
    const registrantModal = document.getElementById("registrantModal");
    const closeModal = document.getElementById("closeModal");
    const cancelModal = document.getElementById("cancelModal");
    const saveRegistrants = document.getElementById("saveRegistrants");
    const addAnotherRegistrant = document.getElementById("addAnotherRegistrant");
    const registrantsList = document.getElementById("registrantsList");
    const registrantsListContent = document.getElementById("registrantsListContent");
    const registrantCount = document.getElementById("registrantCount");
    const registrantCountText = document.getElementById("registrantCountText");
    const registrant_list = document.getElementById("registrant_list");
    
    let registrants = [];
    
    // Load existing registrants if any
    const existingRegistrants = registrant_list.value;
    if (existingRegistrants) {
        try {
            registrants = JSON.parse(existingRegistrants);
            updateRegistrantsList();
        } catch (e) {
            registrants = [];
        }
    }
    
    // Open modal
    if (openRegistrantModal) {
        openRegistrantModal.addEventListener("click", () => {
            registrantModal.style.display = "flex";
        });
    }
    
    // Close modal
    function closeRegistrantModal() {
        registrantModal.style.display = "none";
        clearModalForm();
    }
    
    if (closeModal) closeModal.addEventListener("click", closeRegistrantModal);
    if (cancelModal) cancelModal.addEventListener("click", closeRegistrantModal);
    
    // Close modal when clicking outside
    registrantModal.addEventListener("click", (e) => {
        if (e.target === registrantModal) {
            closeRegistrantModal();
        }
    });
    
    // Clear form
    function clearModalForm() {
        document.getElementById("modalRegistrantName").value = "";
        document.getElementById("modalRegistrantEmail").value = "";
        document.getElementById("modalRegistrantPhone").value = "";
        document.getElementById("modalAccessCode").value = "";
    }
    
    // Add registrant
    function addRegistrant() {
        const name = document.getElementById("modalRegistrantName").value.trim();
        const email = document.getElementById("modalRegistrantEmail").value.trim();
        const phone = document.getElementById("modalRegistrantPhone").value.trim();
        const accessCode = document.getElementById("modalAccessCode").value.trim();
        
        // Validation
        if (!name || !email || !phone) {
            alert("Please fill in name, email, and phone fields.");
            return false;
        }
        
        // Email validation
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            alert("Please enter a valid email address.");
            return false;
        }
        
        // Check for duplicate emails
        if (registrants.some(r => r.email === email)) {
            alert("This email is already added.");
            return false;
        }
        
        // Add registrant
        registrants.push({
            name: name,
            email: email,
            phone: phone,
            access_code: accessCode || generateAccessCode()
        });
        
        updateRegistrantsList();
        clearModalForm();
        return true;
    }
    
    // Update registrants list display
    function updateRegistrantsList() {
        if (registrants.length === 0) {
            registrantsList.style.display = "none";
            registrantCount.style.display = "none";
        } else {
            registrantsList.style.display = "block";
            registrantCount.style.display = "block";
            registrantCountText.textContent = registrants.length;
            
            let html = "";
            registrants.forEach((registrant, index) => {
                html += `
                    <div class="registrant-item" style="display: flex; justify-content: space-between; align-items: center; padding: 10px; border: 1px solid #e5e7eb; border-radius: 6px; margin-bottom: 8px;">
                        <div>
                            <strong>${registrant.name}</strong><br>
                            <small>${registrant.email} | ${registrant.phone}</small>
                            ${registrant.access_code ? `<br><small>Code: <code>${registrant.access_code}</code></small>` : ''}
                        </div>
                        <button type="button" onclick="removeRegistrant(${index})" style="background: #ef4444; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer;">Remove</button>
                    </div>
                `;
            });
            registrantsListContent.innerHTML = html;
        }
        
        // Update hidden field
        registrant_list.value = JSON.stringify(registrants);
    }
    
    // Remove registrant
    window.removeRegistrant = function(index) {
        registrants.splice(index, 1);
        updateRegistrantsList();
    };
    
    // Add another registrant
    if (addAnotherRegistrant) {
        addAnotherRegistrant.addEventListener("click", () => {
            if (addRegistrant()) {
                // Keep modal open for adding more
            }
        });
    }
    
    // Save all registrants
    if (saveRegistrants) {
        saveRegistrants.addEventListener("click", () => {
            if (addRegistrant()) {
                closeRegistrantModal();
            }
        });
    }
    
    // Generate access code function (if not already defined)
    function generateAccessCode() {
        const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        let code = '';
        for (let i = 0; i < 8; i++) {
            code += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        return code;
    }
})();
</script>
<?php if ($hasMapLocation): ?>
<script>
document.addEventListener("DOMContentLoaded", function() {
    if (typeof LocationPicker === "undefined") {
        return;
    }
    LocationPicker.registerDisplay({
        mapElement: "eventLocationMap",
        lat: <?php echo json_encode((float) $eventCoords['lat']); ?>,
        lng: <?php echo json_encode((float) $eventCoords['lng']); ?>,
        zoom: 16,
        interactive: false
    });
});
</script>
<?php endif; ?>
<?php renderAppShellEnd("events"); ?>
