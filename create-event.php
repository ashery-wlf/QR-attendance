<?php
include("includes/auth.php");
include("includes/db.php");
include("includes/app.php");

ensureEventSchema($conn);
appRequireRole('event_organizer');

$user_id = (int) $_SESSION['user_id'];
$organization_id = appCurrentOrganizationId();
$message = "";
$error = "";

if ($organization_id <= 0) {
    die("Your organizer account is not assigned to an organization.");
}

if (isset($_POST['submit'])) {
    if (!appVerifyCsrf()) {
        $error = "Security check failed. Please try again.";
    } else {
    $name = trim($_POST['name'] ?? "");
    $description = trim($_POST['description'] ?? "");
    $date = $_POST['date'] ?? "";
    $time = $_POST['time'] ?? "";
    $end_time = $_POST['end_time'] ?? "";
    $attendance_start = $_POST['attendance_start'] ?? "";
    $attendance_end = $_POST['attendance_end'] ?? "";
    $registration_mode = $_POST['registration_mode'] ?? "self";
    $venue_id = (int) ($_POST['venue_id'] ?? 0);
    $venue_name = "";
    $venue_location = "";
    $location_lat = null;
    $location_lng = null;
    $max_distance_km = trim($_POST['max_distance_km'] ?? "");
    $target_audience = trim($_POST['target_audience'] ?? "all");
    if ($target_audience === "") {
        $target_audience = "all";
    }
    $access_code = trim($_POST['access_code'] ?? "");
    $registrant_list = trim($_POST['registrant_list'] ?? "");

    if ($name === "" || $date === "" || $time === "" || $venue_id <= 0) {
        $error = "Please fill the required event details and choose a venue.";
    } else {
        $scheduleError = appEventScheduleError($date, $time, $end_time, $attendance_start, $attendance_end);
        if ($scheduleError !== "") {
            $error = $scheduleError;
        }

        $venueStmt = $conn->prepare("SELECT id, name, address, city, latitude, longitude FROM venues WHERE id = ? AND organization_id = ? LIMIT 1");
        $venueStmt->bind_param("ii", $venue_id, $organization_id);
        $venueStmt->execute();
        $venueResult = $venueStmt->get_result();

        if (!$venueResult || $venueResult->num_rows === 0) {
            $error = "Choose a valid venue from your organization.";
        } else {
            $selectedVenue = $venueResult->fetch_assoc();
            if (!is_numeric($selectedVenue['latitude']) || !is_numeric($selectedVenue['longitude'])) {
                $error = "Selected venue does not have an exact map location yet. Ask the Organization Admin to update it.";
            }
            $venue_name = $selectedVenue['name'];
            $venue_location = trim(implode(', ', array_filter([$selectedVenue['address'] ?? '', $selectedVenue['city'] ?? ''])));
            [$location_lat, $location_lng] = appNormalizeLatLng(
                (float) $selectedVenue['latitude'],
                (float) $selectedVenue['longitude']
            );
        }

        $imagePath = "logo.png";

        if ($error === "" && !empty($_FILES['image']['name'])) {
            $uploadError = "";
            $uploadedPath = appUploadedImagePath($_FILES['image'], 'event_' . $user_id, $uploadError);
            if ($uploadedPath === false) {
                $error = $uploadError;
            } else {
                $imagePath = $uploadedPath;
            }
        }

        if ($error === "" && !in_array($target_audience, ['all', 'student', 'staff', 'guest'], true)) {
            $error = "Choose a valid target attendee type.";
        }

        if ($error === "" && $registration_mode === "code" && $access_code === "") {
            $access_code = generateAccessCode();
        }
        
        // Set access_code to NULL for self-registration mode to avoid unique constraint issues
        if ($registration_mode === "self") {
            $access_code = null;
        }

        if ($error === "") {
        $attendanceStartValue = appTimeValue($attendance_start) !== '' ? appTimeValue($attendance_start) : null;
        $attendanceEndValue = appTimeValue($attendance_end) !== '' ? appTimeValue($attendance_end) : null;
        [$locationLatValue, $locationLngValue] = appNormalizeLatLng(
            is_numeric($location_lat) ? (float) $location_lat : null,
            is_numeric($location_lng) ? (float) $location_lng : null
        );
        $maxDistanceValue = is_numeric($max_distance_km) && (float) $max_distance_km >= 0 ? (float) $max_distance_km : null;
        $registrants = parseRegistrantLines($registrant_list);
        $invitedEmailsText = implode("\n", array_map(function ($item) {
            return $item['email'];
        }, $registrants));
        $venue_id_value = $venue_id > 0 ? $venue_id : null;

        // Build query with conditional access_code handling
        if ($access_code === null) {
            $stmt = $conn->prepare("
                INSERT INTO events(
                    name, description, date, time, end_time, attendance_start, attendance_end,
                    image, venue_id, venue_name, venue_location, location_lat, location_lng, max_distance_km, target_audience, organization_id, created_by, type, registration_mode, access_code, invited_emails
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'online', ?, NULL, ?)
            ");

            $stmt->bind_param(
                "ssssssssissdddsiiis",
                $name,
                $description,
                $date,
                $time,
                $end_time,
                $attendanceStartValue,
                $attendanceEndValue,
                $imagePath,
                $venue_id_value,
                $venue_name,
                $venue_location,
                $locationLatValue,
                $locationLngValue,
                $maxDistanceValue,
                $target_audience,
                $organization_id,
                $user_id,
                $registration_mode,
                $invitedEmailsText
            );
        } else {
            $stmt = $conn->prepare("
                INSERT INTO events(
                    name, description, date, time, end_time, attendance_start, attendance_end,
                    image, venue_id, venue_name, venue_location, location_lat, location_lng, max_distance_km, target_audience, organization_id, created_by, type, registration_mode, access_code, invited_emails
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'online', ?, ?, ?)
            ");

            $stmt->bind_param(
                "ssssssssissdddsiisss",
                $name,
                $description,
                $date,
                $time,
                $end_time,
                $attendanceStartValue,
                $attendanceEndValue,
                $imagePath,
                $venue_id_value,
                $venue_name,
                $venue_location,
                $locationLatValue,
                $locationLngValue,
                $maxDistanceValue,
                $target_audience,
                $organization_id,
                $user_id,
                $registration_mode,
                $access_code,
                $invitedEmailsText
            );
        }

        if ($stmt->execute()) {
            $eventId = $stmt->insert_id;
            registerUserToEvent($conn, $user_id, $eventId);
            if ($registration_mode === "code" && !empty($registrants)) {
                foreach ($registrants as $registrant) {
                    upsertPrivateRegistrant($conn, $eventId, $registrant);
                    sendEventAccessCodeEmail($registrant['name'], $registrant['email'], [
                        'name' => $name,
                        'date' => $date,
                        'time' => $time,
                        'venue_name' => $venue_name,
                    ], $access_code);
                }
            }
            header("Location: event.php?id=" . $eventId);
            exit();
        }

        $error = "Event could not be created right now.";
        }
    }
    }
}
?>
<?php
$pageCss = appLocationMapsHeadHtml($conn) . '<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script><style>
.back{
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding:12px 16px;
    border-radius:12px;
    background:#ffffff;
    border:1px solid #dbe5f1;
    color:#1d4ed8;
    font-weight:700;
}

.card{
    background:#fff;
    border:1px solid #e2e8f0;
    border-radius:22px;
    box-shadow:0 20px 40px rgba(15, 23, 42, 0.08);
    overflow:hidden;
}

.card-head{
    padding:22px 24px 0;
}

.card-head h2{
    margin:0;
    font-size:22px;
}

.card-head p{
    margin:8px 0 0;
    color:#64748b;
}

.form{
    padding:24px;
    display:grid;
    grid-template-columns:repeat(2, minmax(0, 1fr));
    gap:18px;
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
    border:1px solid #dbe3ef;
    border-radius:14px;
    padding:13px 14px;
    font-size:14px;
    background:#f8fbff;
    color:#0f172a;
    outline:none;
}

textarea{
    min-height:120px;
    resize:vertical;
}

.inline-note{
    font-size:12px;
    color:#64748b;
}

.invite-box{
    grid-column:1 / -1;
    border:1px solid #dbe3ef;
    border-radius:18px;
    padding:16px;
    background:#f8fbff;
}

.mode-box{
    grid-column:1 / -1;
    display:grid;
    grid-template-columns:repeat(2, minmax(0, 1fr));
    gap:14px;
}

.mode-option{
    border:1px solid #dbe3ef;
    border-radius:18px;
    padding:16px;
    background:#f8fbff;
}

.mode-option strong{
    display:block;
    margin-bottom:6px;
}

.message, .error{
    margin:18px 24px 0;
    padding:14px 16px;
    border-radius:14px;
    font-weight:600;
}

.message{
    background:#ecfdf5;
    color:#166534;
}

.error{
    background:#fef2f2;
    color:#b91c1c;
}

.actions{
    grid-column:1 / -1;
    display:flex;
    justify-content:flex-end;
    gap:12px;
    padding-top:4px;
}

button{
    border:none;
    border-radius:14px;
    padding:14px 22px;
    font-size:15px;
    font-weight:800;
    cursor:pointer;
}

.primary{
    background:linear-gradient(180deg, #2563eb 0%, #1d4ed8 100%);
    color:#fff;
    box-shadow:0 16px 30px rgba(37, 99, 235, 0.28);
}

.secondary{
    background:#e2e8f0;
    color:#334155;
}

/* Modal Styles */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.modal-overlay.active {
    display: flex;
}

.modal-content {
    background: white;
    border-radius: 20px;
    padding: 24px;
    max-width: 500px;
    width: 90%;
    max-height: 80vh;
    overflow-y: auto;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.modal-title {
    font-size: 20px;
    font-weight: 700;
    color: #0f172a;
    margin: 0;
}

.modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #64748b;
    padding: 0;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    transition: background 0.2s;
}

.modal-close:hover {
    background: #f1f5f9;
}

.modal-body {
    color: #334155;
}

.modal-form {
    display: flex;
    flex-direction: column;
    gap: 16px;
    margin-top: 16px;
}

.modal-field {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.modal-field label {
    font-size: 14px;
    font-weight: 600;
    color: #374151;
}

.modal-field textarea {
    border: 1px solid #d1d5db;
    border-radius: 8px;
    padding: 10px;
    font-size: 14px;
    resize: vertical;
    min-height: 100px;
}

.modal-field input {
    border: 1px solid #d1d5db;
    border-radius: 8px;
    padding: 10px;
    font-size: 14px;
}

.modal-help {
    font-size: 12px;
    color: #6b7280;
    margin-top: 4px;
}

.modal-actions {
    display: flex;
    gap: 12px;
    justify-content: flex-end;
    margin-top: 20px;
}

.modal-btn {
    padding: 10px 16px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    border: none;
    transition: all 0.2s;
}

.modal-btn-primary {
    background: #2563eb;
    color: white;
}

.modal-btn-primary:hover {
    background: #1d4ed8;
}

.modal-btn-secondary {
    background: #f3f4f6;
    color: #374151;
}

.modal-btn-secondary:hover {
    background: #e5e7eb;
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

.location-toolbar .actions{
    grid-column:auto;
    padding-top:0;
    justify-content:flex-end;
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
    min-height:320px;
    background:#fff;
}

.inline-map #adminEventMap{
    width:100%;
    height:320px;
}

.map-hint{
    margin:10px 0 0;
    color:#64748b;
    font-size:12px;
}


@media (max-width: 760px){
    .form, .mode-box{
        grid-template-columns:1fr;
    }

    .location-toolbar{
        grid-template-columns:1fr;
    }

    .location-actions{
        width:100%;
    }

    .ghost-button{
        width:100%;
    }

    .modal-content {
        margin: 20px;
        width: calc(100% - 40px);
    }
}
</style>
';

renderAppShellStart($conn, [
    "title" => "Create Event",
    "active" => "create-event",
    "page_title" => "Create Event",
    "page_subtitle" => "Set event time, attendance window, access method, venue information, and poster image in one place.",
    "search_placeholder" => "Search events...",
    "page_actions" => '<a href="dashboard.php" class="back">Back to dashboard</a>',
    "show_page_head" => false,
    "extra_head" => $pageCss,
]);
?>
    <div class="card">
        <div class="card-head">
            <h2>Phase One Event Setup</h2>
            <p>All users can see events online. Access to join depends on the mode you choose here.</p>
        </div>

        <?php if ($message !== ""): ?>
            <div class="message"><?php echo h($message); ?></div>
        <?php endif; ?>

        <?php if ($error !== ""): ?>
            <div class="error"><?php echo h($error); ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="form">
            <?php echo appCsrfInput(); ?>
            <div class="field full">
                <label for="name">Event Name</label>
                <input id="name" type="text" name="name" required value="<?php echo h($_POST['name'] ?? ''); ?>">
            </div>

            <div class="field full">
                <label for="description">Event Description</label>
                <textarea id="description" name="description" placeholder="What is this event about?"><?php echo h($_POST['description'] ?? ''); ?></textarea>
            </div>

            <div class="field">
                <label for="date">Event Date</label>
                <input id="date" type="date" name="date" required min="<?php echo h(date('Y-m-d')); ?>" value="<?php echo h($_POST['date'] ?? ''); ?>">
            </div>

            <div class="field">
                <label for="time">Start Time</label>
                <input id="time" type="time" name="time" required value="<?php echo h($_POST['time'] ?? ''); ?>">
            </div>

            <div class="field">
                <label for="end_time">End Time</label>
                <input id="end_time" type="time" name="end_time" required value="<?php echo h($_POST['end_time'] ?? ''); ?>">
            </div>

            <div class="field">
                <label for="target_audience">Target Attendees</label>
                <?php $selectedAudience = $_POST['target_audience'] ?? 'all'; ?>
                <select id="target_audience" name="target_audience" required>
                    <option value="all" <?php echo $selectedAudience === 'all' ? 'selected' : ''; ?>>All attendees</option>
                    <option value="student" <?php echo $selectedAudience === 'student' ? 'selected' : ''; ?>>Students only</option>
                    <option value="staff" <?php echo $selectedAudience === 'staff' ? 'selected' : ''; ?>>Staff only</option>
                    <option value="guest" <?php echo $selectedAudience === 'guest' ? 'selected' : ''; ?>>Guests only</option>
                </select>
            </div>

            <div class="field">
                <label for="attendance_start">Attendance Opens</label>
                <input id="attendance_start" type="time" name="attendance_start" value="<?php echo h($_POST['attendance_start'] ?? ''); ?>">
                <div class="inline-note">If empty, attendance opens at the event start time.</div>
            </div>

            <div class="field">
                <label for="attendance_end">Attendance Closes</label>
                <input id="attendance_end" type="time" name="attendance_end" value="<?php echo h($_POST['attendance_end'] ?? ''); ?>">
                <div class="inline-note">If empty, attendance closes at the end time.</div>
            </div>

            <div class="field">
                <label for="venue_id">Select Venue (from your organization)</label>
                <select id="venue_id" name="venue_id" required>
                    <option value="">-- Choose venue --</option>
                    <?php 
                    $venuesQuery = $conn->prepare("SELECT id, name, address, city, latitude, longitude FROM venues WHERE organization_id = ? ORDER BY name ASC");
                    $venuesQuery->bind_param("i", $organization_id);
                    $venuesQuery->execute();
                    $venuesResult = $venuesQuery->get_result();
                    while ($venue = $venuesResult->fetch_assoc()) {
                        $displayText = h($venue['name']);
                        if (!empty($venue['address'])) {
                            $displayText .= ' - ' . h($venue['address']);
                        }
                        if (!empty($venue['city'])) {
                            $displayText .= ', ' . h($venue['city']);
                        }
                        $selected = (int) ($_POST['venue_id'] ?? 0) === (int) $venue['id'] ? 'selected' : '';
                        $addressParts = array_filter([$venue['address'] ?? '', $venue['city'] ?? '']);
                        $address = implode(', ', $addressParts);
                        echo "<option value=\"" . (int) $venue['id'] . "\" "
                            . "data-name=\"" . h($venue['name']) . "\" "
                            . "data-address=\"" . h($address) . "\" "
                            . "data-lat=\"" . h($venue['latitude'] ?? '') . "\" "
                            . "data-lng=\"" . h($venue['longitude'] ?? '') . "\" "
                            . "$selected>$displayText</option>";
                    }
                    ?>
                </select>
                <div class="inline-note">Venue location is set by your Organization Admin.</div>
                <p id="venueSelectMessage" class="inline-note" style="display:none;margin-top:8px;color:#b91c1c;font-weight:700;"></p>
            </div>

            <div class="field">
                <label for="venue_name">Venue Name</label>
                <input id="venue_name" type="text" name="venue_name" placeholder="Choose a venue above" value="<?php echo h($_POST['venue_name'] ?? ''); ?>" readonly>
                <div class="inline-note">This is filled automatically from the selected venue.</div>
            </div>

            <div class="field">
                <label for="venue_location">Venue Address (optional)</label>
                <input id="venue_location" type="text" name="venue_location" placeholder="Choose a venue above" value="<?php echo h($_POST['venue_location'] ?? ''); ?>" readonly>
                <div class="inline-note">Attendees will see this location and directions on the event page.</div>
            </div>

            <div class="field">
                <label for="max_distance_km">Allowed Scan Radius (km)</label>
                <input id="max_distance_km" type="number" name="max_distance_km" min="0" step="0.01" placeholder="Example: 0.20" value="<?php echo h($_POST['max_distance_km'] ?? ''); ?>">
                <div class="inline-note">Leave empty or 0 to allow scans from any distance.</div>
            </div>

            
            <div id="venueMapPreview" class="location-tools" style="display:none;">
                <label>Venue Location Preview</label>
                <p style="margin-top:8px;">Read-only map for the venue you selected. Location is managed by your Organization Admin.</p>
                <div id="locationStatus" class="message" style="display:block;margin-top:14px;background:#eff6ff;color:#1d4ed8;"></div>
                <div class="inline-map">
                    <div id="adminEventMap" class="google-map-canvas"></div>
                </div>
                <p class="map-hint">Event coordinates are copied automatically from the selected venue.</p>
            </div>

            <div class="field full">
                <label for="image">Venue / Event Poster</label>
                <input id="image" type="file" name="image" accept="image/*">
            </div>

            <div class="mode-box">
                <div class="mode-option">
                    <strong>Self Registration</strong>
                    <label><input type="radio" name="registration_mode" value="self" <?php echo (($_POST['registration_mode'] ?? 'self') === 'self') ? 'checked' : ''; ?>> People register themselves from the event page</label>
                </div>

                <div class="mode-option">
                    <strong>Access Code</strong>
                    <label><input type="radio" name="registration_mode" value="code" <?php echo (($_POST['registration_mode'] ?? '') === 'code') ? 'checked' : ''; ?>> People must enter a special code before joining</label>
                </div>
            </div>

            <div class="field full">
                <label for="access_code">Access Code</label>
                <input id="access_code" type="text" name="access_code" placeholder="Auto-generated for private events" value="<?php echo h($_POST['access_code'] ?? ''); ?>">
                <div class="inline-note">If this event is private and you leave it blank, the system creates the code automatically.</div>
            </div>

            <div class="invite-box">
                <label>Private Event Registrants</label>
                <div class="inline-note">Add specific people who can register for this private event.</div>
                <button type="button" class="ghost-button" id="openRegistrantModal" style="width: 100%; margin-top: 10px;">
                    + Add Private Registrants
                </button>
                <div id="registrantCount" class="inline-note" style="margin-top: 8px; display: none;">
                    <span id="registrantCountText">0</span> registrant(s) added
                </div>
            </div>

            <div class="actions">
                <a href="events.php" class="secondary" style="display:inline-flex;align-items:center;">Cancel</a>
                <button class="primary" type="submit" name="submit">Create Event</button>
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

    <input type="hidden" id="registrant_list" name="registrant_list" value="<?php echo h($_POST['registrant_list'] ?? ''); ?>">
<script>
document.addEventListener('DOMContentLoaded', function() {
    const dateInput = document.getElementById("date");
    const startInput = document.getElementById("time");
    const endInput = document.getElementById("end_time");
    const attendanceStartInput = document.getElementById("attendance_start");
    const attendanceEndInput = document.getElementById("attendance_end");

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

    const status = document.getElementById("locationStatus");
    const venueSelect = document.getElementById("venue_id");
    const venueNameInput = document.getElementById("venue_name");
    const venueLocationInput = document.getElementById("venue_location");
    const mapPreview = document.getElementById("venueMapPreview");
    const venueSelectMessage = document.getElementById("venueSelectMessage");

    function setStatus(message) {
        if (status) {
            status.textContent = message;
        }
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

        setStatus("Showing location for " + venueName + " (read-only).");
    }

    function applySelectedVenue() {
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
        const venueCoords = appNormalizeLatLng(selected.dataset.lat, selected.dataset.lng);
        const lat = venueCoords.lat;
        const lng = venueCoords.lng;

        if (venueNameInput) {
            venueNameInput.value = venueName;
        }
        if (venueLocationInput) {
            venueLocationInput.value = venueAddress;
        }

        if (!Number.isNaN(lat) && !Number.isNaN(lng) && lat !== 0 && lng !== 0) {
            showVenueMap(lat, lng, venueName);
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
        venueSelect.addEventListener("change", applySelectedVenue);
        if (venueSelect.value) {
            applySelectedVenue();
        }
    }
    
    // Private Event Registrants Modal Functionality
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
    
    // Open modal
    if (openRegistrantModal) {
        openRegistrantModal.addEventListener("click", function() {
            registrantModal.style.display = "flex";
            document.body.style.overflow = "hidden";
        });
    }
    
    // Close modal functions
    function closeRegistrantModal() {
        registrantModal.style.display = "none";
        document.body.style.overflow = "auto";
    }
    
    if (closeModal) {
        closeModal.addEventListener("click", closeRegistrantModal);
    }
    
    if (cancelModal) {
        cancelModal.addEventListener("click", closeRegistrantModal);
    }
    
    // Close modal when clicking outside
    registrantModal.addEventListener("click", function(e) {
        if (e.target === registrantModal) {
            closeRegistrantModal();
        }
    });
    
    // Add registrant function
    function addRegistrant() {
        const name = document.getElementById("modalRegistrantName").value.trim();
        const email = document.getElementById("modalRegistrantEmail").value.trim();
        const phone = document.getElementById("modalRegistrantPhone").value.trim();
        const accessCode = document.getElementById("modalAccessCode").value.trim();
        
        if (!name || !email || !phone) {
            alert("Please fill in all required fields (Name, Email, Phone)");
            return;
        }
        
        if (!validateEmail(email)) {
            alert("Please enter a valid email address");
            return;
        }
        
        // Check for duplicate email
        if (registrants.some(r => r.email === email)) {
            alert("This email is already added");
            return;
        }
        
        const registrant = {
            name: name,
            email: email,
            phone: phone,
            accessCode: accessCode || generateAccessCode()
        };
        
        registrants.push(registrant);
        updateRegistrantsList();
        clearModalForm();
        
        // Show registrants list
        if (registrants.length > 0) {
            registrantsList.style.display = "block";
        }
    }
    
    // Update registrants list display
    function updateRegistrantsList() {
        registrantsListContent.innerHTML = "";
        registrants.forEach((registrant, index) => {
            const registrantDiv = document.createElement("div");
            registrantDiv.className = "registrant-item";
            registrantDiv.innerHTML = `
                <div class="registrant-info">
                    <strong>${registrant.name}</strong><br>
                    <small>${registrant.email} | ${registrant.phone}</small>
                    ${registrant.accessCode ? `<br><small>Access Code: <strong>${registrant.accessCode}</strong></small>` : ''}
                </div>
                <button type="button" class="remove-registrant" onclick="removeRegistrant(${index})">&times;</button>
            `;
            registrantsListContent.appendChild(registrantDiv);
        });
        
        // Update count
        registrantCountText.textContent = registrants.length;
        registrantCount.style.display = "block";
        
        // Update hidden field
        registrant_list.value = JSON.stringify(registrants);
    }
    
    // Remove registrant
    function removeRegistrant(index) {
        registrants.splice(index, 1);
        updateRegistrantsList();
        
        if (registrants.length === 0) {
            registrantsList.style.display = "none";
        }
    }
    
    // Clear modal form
    function clearModalForm() {
        document.getElementById("modalRegistrantName").value = "";
        document.getElementById("modalRegistrantEmail").value = "";
        document.getElementById("modalRegistrantPhone").value = "";
        document.getElementById("modalAccessCode").value = "";
    }
    
    // Email validation
    function validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }
    
    // Generate access code
    function generateAccessCode() {
        const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        let result = '';
        for (let i = 0; i < 8; i++) {
            result += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        return result;
    }
    
    // Add another registrant
    if (addAnotherRegistrant) {
        addAnotherRegistrant.addEventListener("click", addRegistrant);
    }
    
    // Save all registrants
    if (saveRegistrants) {
        saveRegistrants.addEventListener("click", function() {
            if (registrants.length === 0) {
                alert("Please add at least one registrant");
                return;
            }
            closeRegistrantModal();
        });
    }
    
    // Load existing registrants if any
    const existingRegistrants = registrant_list.value;
    if (existingRegistrants) {
        try {
            registrants = JSON.parse(existingRegistrants);
            updateRegistrantsList();
        } catch (e) {
            console.log("No existing registrants found");
        }
    }
});
</script>
<?php renderAppShellEnd("create-event"); ?>
