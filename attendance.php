<?php
include("includes/auth.php");
include("includes/db.php");
include("includes/app.php");

ensureEventSchema($conn);
appRequireRole(['organization_admin', 'event_organizer', 'attendee']);

$user_id = (int) $_SESSION['user_id'];
$user_role = $_SESSION['user_role'] ?? 'attendee';
$user_org_id = isset($_SESSION['organization_id']) ? (int) $_SESSION['organization_id'] : 0;
$view = $user_role === 'attendee' ? 'attended' : 'created';
$event_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$canViewAllAttendance = false;

function attendanceExcelText($value)
{
    $value = trim((string) $value);
    if ($value !== '' && preg_match('/^[=+\-@]/', $value)) {
        $value = "'" . $value;
    }
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function attendanceExcelFilename($eventName, $reportDate, $attendeeType)
{
    $base = preg_replace('/[^A-Za-z0-9_-]+/', '_', strtolower((string) $eventName));
    $base = trim($base, '_');
    if ($base === '') {
        $base = 'attendance';
    }

    $datePart = $reportDate !== '' ? $reportDate : 'all_dates';
    $typePart = $attendeeType !== 'all' ? $attendeeType : 'all_attendees';
    return $base . '_' . $datePart . '_' . $typePart . '.xls';
}

$attendedEvents = null;
if ($user_role === 'attendee') {
    $attendedEvents = $conn->query("
        SELECT e.*, a.time as check_in_time, a.attendance_status, a.scan_address, a.scan_ip, a.browser_info, a.distance_from_venue
        FROM events e
        JOIN attendance a ON e.id = a.event_id
        WHERE a.user_id = $user_id AND e.deleted = FALSE
        ORDER BY a.time DESC
    ");
}

// Event records are visible only to the user who created that event.
$createdEvents = null;
if ($user_role !== 'attendee') {
    $managedEventCondition = $user_role === 'organization_admin'
        ? "e.organization_id = $user_org_id"
        : "e.created_by = $user_id";
    $createdEvents = $conn->query("
        SELECT e.*, COUNT(a.id) as attendance_count
        FROM events e
        LEFT JOIN attendance a ON e.id = a.event_id
        WHERE $managedEventCondition AND e.deleted = FALSE
        GROUP BY e.id
        ORDER BY e.date DESC, e.time DESC
    ");
}

// Get specific event details if event_id is provided
$event = null;
$attendanceData = null;
if ($event_id > 0) {
    $event = $conn->query("SELECT * FROM events WHERE id=$event_id AND deleted = FALSE LIMIT 1")->fetch_assoc();
    
    if ($event) {
        $isEventCreator = (int) $event['created_by'] === $user_id;
        $isOrganizationAdminForEvent = $user_role === 'organization_admin' && (int) $event['organization_id'] === $user_org_id;
        $canViewAllAttendance = $isEventCreator || $isOrganizationAdminForEvent;
        $ownAttendanceResult = $conn->query("SELECT id FROM attendance WHERE event_id=$event_id AND user_id=$user_id LIMIT 1");
        $hasOwnAttendance = $ownAttendanceResult && $ownAttendanceResult->num_rows > 0;
        $hasAccess = $canViewAllAttendance || $hasOwnAttendance;
        
        if (!$hasAccess) {
            die("Access denied: You don't have permission to view this event's attendance.");
        }

        if ($canViewAllAttendance && isset($_GET['export']) && $_GET['export'] === 'excel') {
            $attendeeTypeFilter = trim($_GET['attendee_type'] ?? 'all');
            $allowedTypes = ['all', 'student', 'staff', 'guest'];

            if (!in_array($attendeeTypeFilter, $allowedTypes, true)) {
                $attendeeTypeFilter = 'all';
            }

            $reportDate = $event['date'] ?? '';
            $exportConditions = ["a.event_id=$event_id"];
            if ($attendeeTypeFilter !== 'all') {
                $safeAttendeeType = $conn->real_escape_string($attendeeTypeFilter);
                $exportConditions[] = "u.attendee_type='$safeAttendeeType'";
            }

            $exportRecords = $conn->query("
                SELECT a.*, u.attendee_type, u.reg_no, u.department
                FROM attendance a
                LEFT JOIN users u ON u.id = a.user_id
                WHERE " . implode(' AND ', $exportConditions) . "
                ORDER BY a.time ASC
            ");

            $filename = attendanceExcelFilename($event['name'], $reportDate, $attendeeTypeFilter);
            header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            echo "\xEF\xBB\xBF";
            ?>
            <html>
            <head>
                <meta charset="UTF-8">
                <style>
                    table { border-collapse: collapse; font-family: Arial, sans-serif; font-size: 12px; }
                    th { background: #2563eb; color: #ffffff; font-weight: bold; }
                    th, td { border: 1px solid #cbd5e1; padding: 7px; vertical-align: top; }
                    .title { font-size: 18px; font-weight: bold; background: #e0f2fe; color: #0f172a; }
                    .meta { background: #f8fafc; font-weight: bold; }
                </style>
            </head>
            <body>
                <table>
                    <tr><td colspan="16" class="title">Attendance Report</td></tr>
                    <tr><td class="meta">Event</td><td colspan="15"><?php echo attendanceExcelText($event['name']); ?></td></tr>
                    <tr><td class="meta">Event Date</td><td colspan="15"><?php echo attendanceExcelText($event['date']); ?></td></tr>
                    <tr><td class="meta">Report Date</td><td colspan="15"><?php echo attendanceExcelText($reportDate !== '' ? $reportDate : 'All dates'); ?></td></tr>
                    <tr><td class="meta">Attendee Type</td><td colspan="15"><?php echo attendanceExcelText($attendeeTypeFilter === 'all' ? 'All attendees' : ucfirst($attendeeTypeFilter)); ?></td></tr>
                    <tr></tr>
                    <tr>
                        <th>No.</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Attendee Type</th>
                        <th>Reg No</th>
                        <th>Department</th>
                        <th>Status</th>
                        <th>Check-in Date</th>
                        <th>Check-in Time</th>
                        <th>Distance From Venue</th>
                        <th>Location</th>
                        <th>IP Address</th>
                        <th>Device</th>
                        <th>Browser</th>
                        <th>Verification</th>
                    </tr>
                    <?php
                    $rowNo = 1;
                    if ($exportRecords && $exportRecords->num_rows > 0):
                        while ($row = $exportRecords->fetch_assoc()):
                            $deviceInfo = !empty($row['device_info']) ? json_decode($row['device_info'], true) : [];
                            $browserInfo = !empty($row['browser_info']) ? json_decode($row['browser_info'], true) : [];
                            $rowAttendeeType = $row['attendee_type'] ?: 'unknown';
                            $checkInTimestamp = strtotime($row['time']);
                            ?>
                            <tr>
                                <td><?php echo $rowNo++; ?></td>
                                <td><?php echo attendanceExcelText($row['user_name']); ?></td>
                                <td><?php echo attendanceExcelText($row['user_email']); ?></td>
                                <td><?php echo attendanceExcelText($row['user_phone']); ?></td>
                                <td><?php echo attendanceExcelText(ucfirst($rowAttendeeType)); ?></td>
                                <td><?php echo attendanceExcelText($row['reg_no']); ?></td>
                                <td><?php echo attendanceExcelText($row['department']); ?></td>
                                <td><?php echo attendanceExcelText(ucfirst($row['attendance_status'] ?? 'present')); ?></td>
                                <td><?php echo attendanceExcelText($checkInTimestamp ? date('Y-m-d', $checkInTimestamp) : ''); ?></td>
                                <td><?php echo attendanceExcelText($checkInTimestamp ? date('H:i:s', $checkInTimestamp) : ''); ?></td>
                                <td><?php echo attendanceExcelText($row['distance_from_venue'] !== null ? number_format((float) $row['distance_from_venue'], 2) . ' km' : ''); ?></td>
                                <td><?php echo attendanceExcelText($row['scan_address']); ?></td>
                                <td><?php echo attendanceExcelText($row['scan_ip']); ?></td>
                                <td><?php echo attendanceExcelText($deviceInfo['device_type'] ?? 'Unknown'); ?></td>
                                <td><?php echo attendanceExcelText($deviceInfo['browser'] ?? ($browserInfo['name'] ?? 'Unknown')); ?></td>
                                <td><?php echo attendanceExcelText($row['verification_method']); ?></td>
                            </tr>
                        <?php endwhile;
                    else: ?>
                        <tr><td colspan="16">No attendance records found for the selected filters.</td></tr>
                    <?php endif; ?>
                </table>
            </body>
            </html>
            <?php
            exit();
        }
        
        if ($canViewAllAttendance) {
            // Event organizers and organization admins can view records for managed events.
            $attendanceData = $conn->query("
                SELECT a.id, a.user_name, a.user_email, a.user_phone, a.device_info, a.time, a.attendance_status, a.scan_address, a.scan_ip, a.browser_info, a.distance_from_venue, a.phone_matched, a.verification_method, a.check_in_time, a.notes,
                       u.attendee_type, u.reg_no, u.department
                FROM attendance a
                LEFT JOIN users u ON u.id = a.user_id
                WHERE a.event_id = $event_id
                ORDER BY a.time DESC
            ");
        } else {
            // Non-creators can only see their own attendance record.
            $attendanceData = $conn->query("
                SELECT a.id, a.user_name, a.user_email, a.user_phone, a.device_info, a.time, a.attendance_status, a.scan_address, a.scan_ip, a.browser_info, a.distance_from_venue, a.phone_matched, a.verification_method, a.check_in_time, a.notes,
                       u.attendee_type, u.reg_no, u.department
                FROM attendance a
                LEFT JOIN users u ON u.id = a.user_id
                WHERE a.event_id = $event_id AND a.user_id = $user_id
                ORDER BY a.time DESC
            ");
        }
    } else {
        die("Event not found.");
    }
}

$pageCss = <<<'CSS'
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
* { box-sizing: border-box; }

/* Mobile-first responsive design */
.container { max-width: 1200px; margin: 0 auto; padding: 0 15px; }

/* Category Section */
.category-section {
    margin-bottom: 30px;
}

.section-title {
    font-size: 24px;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 20px;
    text-align: center;
}

/* View Toggle Buttons */
.view-toggle {
    display: flex;
    gap: 15px;
    margin-bottom: 30px;
    background: #f8fafc;
    padding: 8px;
    border-radius: 16px;
    border: 1px solid #e2e8f0;
}

.view-btn {
    flex: 1;
    padding: 20px;
    border: none;
    background: transparent;
    color: #64748b;
    border-radius: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 15px;
    text-align: left;
}

.view-btn.active {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.view-btn:hover:not(.active) {
    background: #e2e8f0;
    transform: translateY(-2px);
}

.view-btn i {
    font-size: 24px;
    min-width: 24px;
}

.btn-text {
    flex: 1;
}

.btn-text strong {
    display: block;
    font-size: 16px;
    margin-bottom: 4px;
}

.btn-text span {
    font-size: 13px;
    opacity: 0.8;
    font-weight: 400;
}

.count-badge {
    background: rgba(255, 255, 255, 0.2);
    color: white;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 14px;
    font-weight: 700;
    min-width: 30px;
    text-align: center;
}

.view-btn:not(.active) .count-badge {
    background: #e2e8f0;
    color: #64748b;
}

/* Event Cards */
.events-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.event-card {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 20px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    transition: all 0.3s ease;
    cursor: pointer;
}

.event-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
}

.event-card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 15px;
}

.event-title {
    font-size: 18px;
    font-weight: 700;
    color: #1f2937;
    margin: 0 0 5px 0;
}

.event-date {
    display: flex;
    align-items: center;
    gap: 6px;
    color: #6b7280;
    font-size: 14px;
}

.event-status {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.status-attended {
    background: #dcfce7;
    color: #166534;
}

.status-created {
    background: #dbeafe;
    color: #1d4ed8;
}

.event-details {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
    margin-top: 15px;
}

.detail-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    color: #6b7280;
}

.detail-item i {
    width: 16px;
    color: #667eea;
}

/* Device Information Row */
.device-row {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 12px;
    padding: 16px;
    margin-bottom: 20px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.device-row:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
}

.device-summary {
    display: flex;
    align-items: center;
    gap: 12px;
    color: white;
}

.device-summary i:first-child {
    font-size: 24px;
}

.device-info {
    flex: 1;
}

.device-info strong {
    display: block;
    font-size: 16px;
    font-weight: 600;
}

.device-info span {
    font-size: 14px;
    opacity: 0.8;
}

.device-summary i:last-child {
    font-size: 16px;
    opacity: 0.7;
}

/* Device Info Cell */
.device-info-cell {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: #1d4ed8;
    font-size: 13px;
    cursor: pointer;
    padding: 6px 10px;
    border: 1px solid #bfdbfe;
    border-radius: 6px;
    background: #f0f9ff;
    transition: all 0.2s ease;
    font-weight: 600;
}

.device-info-cell:hover {
    background-color: #e0f2fe;
    color: #0369a1;
    border-color: #0ea5e9;
    box-shadow: 0 2px 6px rgba(29, 78, 216, 0.15);
}

.device-info-cell:focus {
    outline: 2px solid #1d4ed8;
    outline-offset: 2px;
}

.device-info-cell:active {
    transform: scale(0.98);
    background: #dbeafe;
}

/* Device Tooltip */
.device-tooltip {
    position: absolute;
    background: #111827;
    color: white;
    padding: 14px;
    border-radius: 8px;
    font-size: 12px;
    z-index: 1000;
    max-width: 380px;
    max-height: 400px;
    overflow-y: auto;
    box-shadow: 0 12px 26px rgba(15, 23, 42, 0.24);
    pointer-events: none;
    border: 1px solid #374151;
}

.device-tooltip-item {
    display: flex;
    gap: 12px;
    justify-content: space-between;
    margin-bottom: 8px;
    padding-bottom: 6px;
    border-bottom: 1px solid #374151;
    align-items: flex-start;
}

.device-tooltip-item:last-child {
    margin-bottom: 0;
    border-bottom: none;
    padding-bottom: 0;
}

.device-tooltip-label {
    color: #60a5fa;
    font-weight: 700;
    white-space: nowrap;
    flex-shrink: 0;
}

.device-tooltip-value {
    word-break: break-word;
    text-align: right;
    color: #e5e7eb;
}

.device-details-panel {
    display: none;
    position: fixed;
    top: 50%;
    left: 50%;
    z-index: 2000;
    width: min(92vw, 420px);
    max-height: min(78vh, 560px);
    border: 1px solid #dbeafe;
    border-radius: 12px;
    background: #ffffff;
    box-shadow: 0 24px 70px rgba(15, 23, 42, 0.28);
    overflow-y: auto;
    transform: translate(-50%, -50%);
}

.device-details-panel.show {
    display: block;
}

.device-detail-row {
    display: grid;
    grid-template-columns: 120px minmax(0, 1fr);
    gap: 10px;
    padding: 10px 12px;
    border-bottom: 1px solid #eef2ff;
    font-size: 12px;
    background: #fff;
}

.device-detail-row:last-child {
    border-bottom: 0;
}

.device-detail-label {
    color: #475569;
    font-weight: 800;
}

.device-detail-value {
    color: #0f172a;
    word-break: break-word;
}

.device-detail-toggle {
    margin-top: 6px;
    border: 0;
    background: transparent;
    color: #1d4ed8;
    font-size: 12px;
    font-weight: 800;
    cursor: pointer;
    padding: 0;
}

.device-popup-backdrop {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 1999;
    background: rgba(15, 23, 42, 0.38);
}

.device-popup-backdrop.show {
    display: block;
}

/* Attendance Table */
.table-card {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 20px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    overflow-x: auto;
}

.table-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    flex-wrap: wrap;
    gap: 15px;
}

.table-title {
    font-size: 20px;
    font-weight: 700;
    color: #1f2937;
    margin: 0;
}

.table-stats {
    display: flex;
    gap: 15px;
    align-items: center;
}

.attendance-export {
    margin: 0 0 18px;
    padding: 14px;
    border: 1px solid #dbe4f0;
    border-radius: 14px;
    background: #f8fafc;
}

.attendance-export form {
    display: grid;
    grid-template-columns: minmax(180px, 1fr) auto;
    gap: 10px;
    align-items: end;
}

.attendance-export label {
    display: grid;
    gap: 6px;
    font-size: 13px;
    font-weight: 700;
    color: #334155;
}

.attendance-export input,
.attendance-export select {
    width: 100%;
    height: 40px;
    border: 1px solid #cbd5e1;
    border-radius: 10px;
    padding: 0 10px;
    background: #ffffff;
    color: #0f172a;
}

.attendance-export button {
    height: 40px;
    border: none;
    border-radius: 10px;
    padding: 0 16px;
    background: #16a34a;
    color: #ffffff;
    font-weight: 800;
    cursor: pointer;
}

.attendance-export button:hover {
    background: #15803d;
}

.stat-badge {
    background: #f3f4f6;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 14px;
    font-weight: 600;
    color: #374151;
}

/* Responsive Table */
.attendance-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 600px;
}

.attendance-table th,
.attendance-table td {
    padding: 12px 8px;
    text-align: left;
    border-bottom: 1px solid #f3f4f6;
}

.attendance-table th {
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #6b7280;
    background: #f9fafb;
    font-weight: 600;
}

.attendance-table tr:hover {
    background: #f9fafb;
}

/* Badges */
.badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.badge-present { background: #dcfce7; color: #166534; }
.badge-late { background: #fef3c7; color: #92400e; }
.badge-absent { background: #fee2e2; color: #991b1b; }
.badge-browser { background: #dbeafe; color: #1d4ed8; }
.badge-device { background: #dcfce7; color: #166534; }

/* Mobile Responsive */
@media (max-width: 768px) {
    .container { padding: 0 10px; }
    
    .section-title {
        font-size: 20px;
    }
    
    .view-toggle {
        flex-direction: column;
        gap: 10px;
    }
    
    .view-btn {
        padding: 15px;
        gap: 12px;
    }
    
    .view-btn i {
        font-size: 20px;
    }
    
    .btn-text strong {
        font-size: 14px;
    }
    
    .btn-text span {
        font-size: 12px;
    }
    
    .events-grid {
        grid-template-columns: 1fr;
    }
    
    .device-grid {
        grid-template-columns: 1fr;
    }
    
    .table-header {
        flex-direction: column;
        align-items: flex-start;
    }

    .attendance-export form {
        grid-template-columns: 1fr;
    }
    
    .attendance-table {
        font-size: 14px;
    }
    
    .attendance-table th,
    .attendance-table td {
        padding: 8px 4px;
    }
    
    .event-details {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 480px) {
    .event-card {
        padding: 15px;
    }
    
    .device-section {
        padding: 20px;
    }
    
    .table-card {
        padding: 15px;
    }
}

/* Chart Container */
.chart-container {
    background: white;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    position: relative;
    height: 300px;
}

.chart-title {
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 15px;
    color: #333;
}

.chart-btn {
    background: #f0f0f0;
    border: none;
    padding: 8px 16px;
    margin: 0 5px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.chart-btn:hover {
    background: #e0e0e0;
    transform: translateY(-2px);
}

.chart-btn.active {
    background: #667eea;
    color: white;
}

.chart-btn.active:hover {
    background: #5a6fd8;
}

.back-link {
    display: inline-flex;
    align-items: center;
    padding: 12px 16px;
    border-radius: 12px;
    background: #e2e8f0;
    color: #334155;
    font-weight: 700;
    text-decoration: none;
    transition: 0.2s;
}

.back-link:hover {
    background: #cbd5e1;
}
.badge-late {
    background-color: #fff3e0;
    color: #e65100;
}
.chart-container {
    background: white;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    position: relative;
    height: 300px;
}
.chart-title {
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 15px;
    color: #333;
}
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}
.stat-card {
    background: white;
    border-radius: 12px;
    padding: 15px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    text-align: center;
}
.stat-card h4 {
    margin: 0 0 10px 0;
    color: #667eea;
    font-size: 12px;
    text-transform: uppercase;
}
.stat-card .number {
    font-size: 24px;
    font-weight: bold;
    color: #333;
}
tr:hover {
    background-color: #f9f9f9;
}

.chart-btn {
    background: #f0f0f0;
    border: none;
    padding: 8px 16px;
    margin: 0 5px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.chart-btn:hover {
    background: #e0e0e0;
    transform: translateY(-2px);
}

.chart-btn.active {
    background: #667eea;
    color: white;
}

.chart-btn.active:hover {
    background: #5a6fd8;
}
</style>
CSS;

// Calculate statistics
$attendedCount = $attendedEvents ? $attendedEvents->num_rows : 0;
$createdCount = $createdEvents ? $createdEvents->num_rows : 0;
$totalAttendees = $attendanceData ? $attendanceData->num_rows : 0;

// Get device statistics for current event
$deviceStats = [];
$browserStats = [];
$locationStats = [];
$attendeeTypeStats = ['student' => 0, 'staff' => 0, 'guest' => 0, 'unknown' => 0];

if ($attendanceData && $attendanceData->num_rows > 0) {
    while ($row = $attendanceData->fetch_assoc()) {
        $attendeeType = $row['attendee_type'] ?? '';
        if (!in_array($attendeeType, ['student', 'staff', 'guest'], true)) {
            $attendeeType = 'unknown';
        }
        $attendeeTypeStats[$attendeeType]++;

        if (!empty($row['device_info'])) {
            $info = json_decode($row['device_info'], true);
            if ($info) {
                $browser = $info['browser'] ?? 'Unknown';
                $deviceType = $info['device_type'] ?? 'Unknown';
                
                if (!isset($browserStats[$browser])) {
                    $browserStats[$browser] = 0;
                }
                $browserStats[$browser]++;
                
                if (!isset($deviceStats[$deviceType])) {
                    $deviceStats[$deviceType] = 0;
                }
                $deviceStats[$deviceType]++;
            }
        }
        
        if (!empty($row['scan_address'])) {
            $location = $row['scan_address'];
            if (!isset($locationStats[$location])) {
                $locationStats[$location] = 0;
            }
            $locationStats[$location]++;
        }
    }
    // Reset pointer for later use
    $attendanceData->data_seek(0);
}

renderAppShellStart($conn, [
    "title" => "Attendance Hub",
    "active" => "attendance",
    "page_title" => "Attendance Hub",
    "page_subtitle" => $user_role === 'attendee'
        ? "View events you attended"
        : "View attendance records for events you manage",
    "search_placeholder" => "Search attendance...",
    "extra_head" => $pageCss,
]);
?>

<div class="container">
    <?php if ($event_id > 0): ?>
        <!-- Single Event View -->
        <div style="margin-bottom: 20px;">
            <a href="attendance.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Back to All Events
            </a>
        </div>

        
        <!-- Attendance Table -->
        <div class="table-card">
            <div class="table-header">
                <h2 class="table-title"><?php echo h($event['name']); ?></h2>
                <div class="table-stats">
                    <?php if ($canViewAllAttendance): ?>
                        <span class="stat-badge">
                            <i class="fas fa-users"></i> <?php echo $totalAttendees; ?> Attendees
                        </span>
                        <span class="stat-badge">Students: <?php echo (int) $attendeeTypeStats['student']; ?></span>
                        <span class="stat-badge">Staff: <?php echo (int) $attendeeTypeStats['staff']; ?></span>
                        <span class="stat-badge">Guests: <?php echo (int) $attendeeTypeStats['guest']; ?></span>
                    <?php else: ?>
                        <span class="stat-badge">
                            <i class="fas fa-user-check"></i> My Check-in
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($canViewAllAttendance): ?>
                <div class="attendance-export">
                    <form method="GET" action="attendance.php">
                        <input type="hidden" name="id" value="<?php echo (int) $event_id; ?>">
                        <input type="hidden" name="export" value="excel">
                        <label>
                            Attendee Type
                            <?php $selectedExportType = $_GET['attendee_type'] ?? 'all'; ?>
                            <select name="attendee_type">
                                <option value="all" <?php echo $selectedExportType === 'all' ? 'selected' : ''; ?>>All attendees</option>
                                <option value="student" <?php echo $selectedExportType === 'student' ? 'selected' : ''; ?>>Students only</option>
                                <option value="staff" <?php echo $selectedExportType === 'staff' ? 'selected' : ''; ?>>Staff only</option>
                                <option value="guest" <?php echo $selectedExportType === 'guest' ? 'selected' : ''; ?>>Guests only</option>
                            </select>
                        </label>
                        <button type="submit">Download Excel</button>
                    </form>
                </div>
            <?php endif; ?>

            <div style="overflow-x: auto;">
                <table class="attendance-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Attendee Type</th>
                            <th>Type Info</th>
                            <th>Status</th>
                            <th>Device Info</th>
                            <th>Check-in Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($attendanceData && $attendanceData->num_rows > 0): ?>
                            <?php while ($row = $attendanceData->fetch_assoc()): ?>
                                <?php 
                                $deviceInfo = [];
                                if (!empty($row['device_info'])) {
                                    $deviceInfo = json_decode($row['device_info'], true);
                                }
                                $status = $row['attendance_status'] ?? 'present';
                                $attendeeType = $row['attendee_type'] ?: 'unknown';
                                $typeInfo = 'N/A';
                                if ($attendeeType === 'student') {
                                    $typeInfo = $row['reg_no'] ? 'Reg No: ' . $row['reg_no'] : 'Reg No: Not set';
                                } elseif ($attendeeType === 'staff') {
                                    $typeInfo = $row['department'] ? 'Department: ' . $row['department'] : 'Department: Not set';
                                } elseif ($attendeeType === 'guest') {
                                    $typeInfo = 'Guest';
                                }
                                $browserInfo = [];
                                if (!empty($row['browser_info'])) {
                                    $browserInfo = json_decode($row['browser_info'], true);
                                }
                                
                                $deviceText = ($deviceInfo['device_type'] ?? 'Unknown') . ' - ' . ($deviceInfo['browser'] ?? ($browserInfo['name'] ?? 'Unknown'));

                                // Generate unique ID for this row
                                $rowId = 'device_' . $row['id'] . '_' . bin2hex(random_bytes(4));
                                
                                $deviceDetails = [
                                    'IP Address' => $row['scan_ip'] ?: 'Not captured',
                                    'Browser' => $deviceInfo['browser'] ?? ($browserInfo['name'] ?? 'Unknown'),
                                    'OS' => $deviceInfo['os_name'] ?? ($browserInfo['platform'] ?? 'Unknown'),
                                    'Device Type' => $deviceInfo['device_type'] ?? 'Unknown',
                                    'Platform' => $deviceInfo['platform'] ?? ($browserInfo['platform'] ?? 'Unknown'),
                                    'Screen' => $deviceInfo['screen_resolution'] ?? 'Unknown',
                                    'DPI' => $deviceInfo['screen_dpi'] ?? 'N/A',
                                    'Memory' => $deviceInfo['device_memory'] ?? 'Unknown',
                                    'CPU Cores' => $deviceInfo['cpu_cores'] ?? 'Unknown',
                                    'Connection' => $deviceInfo['connection_type'] ?? 'Unknown',
                                    'Language' => $deviceInfo['language'] ?? 'Unknown',
                                    'Timezone' => $deviceInfo['timezone'] ?? 'Unknown',
                                    'Location' => $row['scan_address'] ?: 'Not captured'
                                ];
                                ?>
                                <tr>
                                    <td>
                                        <div style="font-weight: 600;"><?php echo h($row['user_name']); ?></div>
                                    </td>
                                    <td><?php echo h($row['user_email']); ?></td>
                                    <td><?php echo h($row['user_phone']); ?></td>
                                    <td><?php echo h(ucfirst($attendeeType)); ?></td>
                                    <td><?php echo h($typeInfo); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $status; ?>">
                                            <?php echo ucfirst($status); ?>
                                            <?php if ($row['phone_matched']): ?> ✅<?php endif; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="device-info-cell" id="<?php echo $rowId; ?>">
                                            <i class="fas fa-circle-info" aria-hidden="true"></i>
                                            <span><?php echo h($deviceText); ?></span>
                                        </div>
                                        <button type="button"
                                                class="device-detail-toggle"
                                                aria-expanded="false"
                                                aria-controls="<?php echo $rowId; ?>_details"
                                                onclick="toggleDeviceDetails('<?php echo $rowId; ?>_details', this)">
                                            View details
                                        </button>
                                        <div class="device-details-panel" id="<?php echo $rowId; ?>_details">
                                            <?php foreach ($deviceDetails as $label => $value): ?>
                                                <div class="device-detail-row">
                                                    <span class="device-detail-label"><?php echo h($label); ?></span>
                                                    <span class="device-detail-value"><?php echo h($value); ?></span>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div><?php echo h(date('M j, Y H:i', strtotime($row['time']))); ?></div>
                                        <?php if (!empty($row['check_in_time'])): ?>
                                            <small style="color: #6b7280;"><?php echo h($row['check_in_time']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php if (!empty($row['notes'])): ?>
                                <tr>
                                    <td colspan="8" style="background: #f9fafb; padding: 8px;">
                                        <small><strong>Notes:</strong> <?php echo h($row['notes']); ?></small>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 40px;">
                                    <i class="fas fa-users" style="font-size: 48px; color: #d1d5db; margin-bottom: 15px; display: block;"></i>
                                    <div style="color: #6b7280;">No attendance records yet.</div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <div id="devicePopupBackdrop" class="device-popup-backdrop" onclick="closeDeviceDetails()"></div>
                <!-- Device Tooltip -->
                <div id="deviceTooltip" class="device-tooltip" style="display: none;"></div>
            </div>
        </div>

        <!-- Charts Section -->
        <?php if ($canViewAllAttendance && count($browserStats) > 0): ?>
        <div class="chart-container">
            <div class="chart-title">🌐 Browser Distribution</div>
            <canvas id="browserChart"></canvas>
        </div>
        <?php endif; ?>

    <?php else: ?>
        <!-- Events Overview View -->
        <?php if ($user_role !== 'attendee'): ?>
            <div class="view-toggle">
                <div class="view-btn active">
                    <i class="fas fa-calendar-check"></i>
                    <span class="btn-text">
                        <strong>Managed Events</strong>
                        <span>View attendance records only</span>
                    </span>
                    <span class="count-badge"><?php echo (int) $createdCount; ?></span>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($view === 'attended'): ?>
            <!-- Events Attended -->
            <div class="events-grid">
                <?php if ($attendedEvents && $attendedEvents->num_rows > 0): ?>
                    <?php while ($event = $attendedEvents->fetch_assoc()): ?>
                        <div class="event-card" onclick="window.location.href='?id=<?php echo $event['id']; ?>'">
                            <div class="event-card-header">
                                <div>
                                    <h3 class="event-title"><?php echo h($event['name']); ?></h3>
                                    <div class="event-date">
                                        <i class="fas fa-calendar"></i>
                                        <?php echo h(date('M j, Y', strtotime($event['date']))); ?>
                                        <i class="fas fa-clock"></i>
                                        <?php echo h(date('H:i', strtotime($event['time']))); ?>
                                    </div>
                                </div>
                                <span class="event-status status-attended">Attended</span>
                            </div>
                            <div class="event-details">
                                <div class="detail-item">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span><?php echo h($event['venue_name'] ?: 'No venue'); ?></span>
                                </div>
                                <div class="detail-item">
                                    <i class="fas fa-check-circle"></i>
                                    <span><?php echo ucfirst(h($event['attendance_status'] ?? 'present')); ?></span>
                                </div>
                                <div class="detail-item">
                                    <i class="fas fa-clock"></i>
                                    <span><?php echo h(date('H:i', strtotime($event['check_in_time']))); ?></span>
                                </div>
                                <div class="detail-item">
                                    <i class="fas fa-map"></i>
                                    <span><?php echo h(substr($event['scan_address'] ?: 'Not captured', 0, 30)); ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div style="text-align: center; padding: 60px 20px; grid-column: 1 / -1;">
                        <i class="fas fa-user-check" style="font-size: 64px; color: #d1d5db; margin-bottom: 20px; display: block;"></i>
                        <h3 style="color: #374151; margin-bottom: 10px;">No Events Attended</h3>
                        <p style="color: #6b7280;">You haven't attended any events yet. Start scanning QR codes to check in!</p>
                    </div>
                <?php endif; ?>
            </div>

        <?php else: ?>
            <!-- Events Created -->
            <div class="events-grid">
                <?php if ($createdEvents && $createdEvents->num_rows > 0): ?>
                    <?php while ($event = $createdEvents->fetch_assoc()): ?>
                        <div class="event-card" onclick="window.location.href='?id=<?php echo $event['id']; ?>'">
                            <div class="event-card-header">
                                <div>
                                    <h3 class="event-title"><?php echo h($event['name']); ?></h3>
                                    <div class="event-date">
                                        <i class="fas fa-calendar"></i>
                                        <?php echo h(date('M j, Y', strtotime($event['date']))); ?>
                                        <i class="fas fa-clock"></i>
                                        <?php echo h(date('H:i', strtotime($event['time']))); ?>
                                    </div>
                                </div>
                                <span class="event-status status-created">Created</span>
                            </div>
                            <div class="event-details">
                                <div class="detail-item">
                                    <i class="fas fa-users"></i>
                                    <span><?php echo $event['attendance_count']; ?> Attendees</span>
                                </div>
                                <div class="detail-item">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span><?php echo h($event['venue_name'] ?: 'No venue'); ?></span>
                                </div>
                                <div class="detail-item">
                                    <i class="fas fa-cog"></i>
                                    <span><?php echo ucfirst(h($event['registration_mode'] ?? 'self')); ?></span>
                                </div>
                                <div class="detail-item">
                                    <i class="fas fa-eye"></i>
                                    <span><?php echo $event['deleted'] ? 'Deleted' : 'Active'; ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div style="text-align: center; padding: 60px 20px; grid-column: 1 / -1;">
                        <i class="fas fa-calendar-plus" style="font-size: 64px; color: #d1d5db; margin-bottom: 20px; display: block;"></i>
                        <h3 style="color: #374151; margin-bottom: 10px;">No Events Created</h3>
                        <p style="color: #6b7280;">You haven't created any events yet. Start organizing your first event!</p>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php if ($canViewAllAttendance && count($browserStats) > 0): ?>
<script>
// Initialize browser chart
let browserChart;
const browserCtx = document.getElementById('browserChart').getContext('2d');
const chartData = {
    labels: <?php echo json_encode(array_keys($browserStats)); ?>,
    datasets: [{
        data: <?php echo json_encode(array_values($browserStats)); ?>,
        backgroundColor: [
            '#667eea',
            '#764ba2', 
            '#f093fb',
            '#4facfe',
            '#00f2fe',
            '#43e97b',
            '#fa709a',
            '#fee140'
        ],
        borderColor: [
            '#667eea',
            '#764ba2',
            '#f093fb', 
            '#4facfe',
            '#00f2fe',
            '#43e97b',
            '#fa709a',
            '#fee140'
        ],
        borderWidth: 2,
        borderRadius: 8
    }]
};

// Initialize with line chart
browserChart = new Chart(browserCtx, {
    type: 'line',
    data: chartData,
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    padding: 15,
                    font: {
                        size: 12
                    }
                }
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const label = context.label || '';
                        const value = context.parsed.y || 0;
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const percentage = ((value / total) * 100).toFixed(1);
                        return label + ': ' + value + ' (' + percentage + '%)';
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: {
                    color: 'rgba(0, 0, 0, 0.05)'
                },
                ticks: {
                    font: {
                        size: 11
                    }
                }
            },
            x: {
                grid: {
                    display: false
                },
                ticks: {
                    font: {
                        size: 11
                    }
                }
            }
        }
    }
});

// Add real-time device information updates
function updateDeviceInformation() {
    // This function can be enhanced to show real-time updates
    // For now, it displays the static information beautifully
    const deviceItems = document.querySelectorAll('.device-item');
    deviceItems.forEach((item, index) => {
        item.style.opacity = '0';
        item.style.transform = 'translateY(20px)';
        setTimeout(() => {
            item.style.transition = 'all 0.5s ease';
            item.style.opacity = '1';
            item.style.transform = 'translateY(0)';
        }, index * 100);
    });
}

// Initialize animations when page loads
document.addEventListener('DOMContentLoaded', function() {
    updateDeviceInformation();
    
    // Animate event cards
    const eventCards = document.querySelectorAll('.event-card');
    eventCards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(30px)';
        setTimeout(() => {
            card.style.transition = 'all 0.6s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 150);
    });
    
    // Animate stats
    const statNumbers = document.querySelectorAll('.stat-badge');
    statNumbers.forEach(stat => {
        const finalNumber = stat.textContent.match(/\d+/)[0];
        let currentNumber = 0;
        const increment = Math.ceil(finalNumber / 20);
        const timer = setInterval(() => {
            currentNumber += increment;
            if (currentNumber >= finalNumber) {
                currentNumber = finalNumber;
                clearInterval(timer);
            }
            stat.innerHTML = stat.innerHTML.replace(/\d+/, currentNumber);
        }, 50);
    });
});

// Device Tooltip Functions
let attendanceDeviceDetailsMap = {};

function toggleDeviceDetails(panelId, button) {
    const panel = document.getElementById(panelId);
    if (!panel) {
        return;
    }

    const shouldOpen = !panel.classList.contains('show');
    closeDeviceDetails();

    if (shouldOpen) {
        panel.classList.add('show');
        const backdrop = document.getElementById('devicePopupBackdrop');
        if (backdrop) {
            backdrop.classList.add('show');
        }
        if (button) {
            button.setAttribute('aria-expanded', 'true');
            button.textContent = 'Hide details';
        }
    }
}

function closeDeviceDetails() {
    document.querySelectorAll('.device-details-panel.show').forEach(function(panel) {
        panel.classList.remove('show');
    });
    document.querySelectorAll('.device-detail-toggle[aria-expanded="true"]').forEach(function(button) {
        button.setAttribute('aria-expanded', 'false');
        button.textContent = 'View details';
    });

    const backdrop = document.getElementById('devicePopupBackdrop');
    if (backdrop) {
        backdrop.classList.remove('show');
    }
}

function escapeHtmlAttendance(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}

function showAttendanceDeviceTooltip(element, rowId) {
    const details = attendanceDeviceDetailsMap[rowId];
    if (!details) {
        console.warn('No device details found for ID:', rowId);
        return;
    }
    
    const tooltip = document.getElementById('deviceTooltip');
    if (!tooltip) return;
    
    let tooltipContent = '';
    for (const [key, value] of Object.entries(details)) {
        tooltipContent += '<div class="device-tooltip-item">' +
            '<span class="device-tooltip-label">' + escapeHtmlAttendance(String(key)) + ':</span>' +
            '<span class="device-tooltip-value">' + escapeHtmlAttendance(String(value)) + '</span>' +
            '</div>';
    }
    
    tooltip.innerHTML = tooltipContent;
    tooltip.style.display = 'block';
    
    // Position tooltip
    const rect = element.getBoundingClientRect();
    const tooltipRect = tooltip.getBoundingClientRect();
    
    let left = rect.left + window.scrollX;
    let top = rect.bottom + window.scrollY + 5;
    
    // Adjust if tooltip goes off screen
    if (left + tooltipRect.width > window.innerWidth) {
        left = window.innerWidth - tooltipRect.width - 10;
    }
    
    if (top + tooltipRect.height > window.innerHeight + window.scrollY) {
        top = rect.top + window.scrollY - tooltipRect.height - 5;
    }
    
    tooltip.style.left = left + 'px';
    tooltip.style.top = top + 'px';
}

function hideAttendanceDeviceTooltip() {
    const tooltip = document.getElementById('deviceTooltip');
    if (tooltip) {
        tooltip.style.display = 'none';
    }
}

function toggleAttendanceDeviceTooltip(element, rowId) {
    const tooltip = document.getElementById('deviceTooltip');
    if (tooltip && tooltip.style.display === 'block') {
        tooltip.style.display = 'none';
    } else {
        showAttendanceDeviceTooltip(element, rowId);
    }
}

function showDeviceTooltip(event, deviceDetails) {
    const tooltip = document.getElementById('deviceTooltip');
    if (!tooltip) return;
    
    let tooltipContent = '';
    for (const [key, value] of Object.entries(deviceDetails)) {
        tooltipContent += '<div class="device-tooltip-item">' +
            '<span class="device-tooltip-label">' + escapeHtmlAttendance(String(key)) + ':</span>' +
            '<span class="device-tooltip-value">' + escapeHtmlAttendance(String(value)) + '</span>' +
            '</div>';
    }
    
    tooltip.innerHTML = tooltipContent;
    tooltip.style.display = 'block';
    
    // Position tooltip
    const rect = event.target.getBoundingClientRect();
    const tooltipRect = tooltip.getBoundingClientRect();
    
    let left = rect.left + window.scrollX;
    let top = rect.bottom + window.scrollY + 5;
    
    // Adjust if tooltip goes off screen
    if (left + tooltipRect.width > window.innerWidth) {
        left = window.innerWidth - tooltipRect.width - 10;
    }
    
    if (top + tooltipRect.height > window.innerHeight + window.scrollY) {
        top = rect.top + window.scrollY - tooltipRect.height - 5;
    }
    
    tooltip.style.left = left + 'px';
    tooltip.style.top = top + 'px';
}

function hideDeviceTooltip() {
    const tooltip = document.getElementById('deviceTooltip');
    if (tooltip) {
        tooltip.style.display = 'none';
    }
}

// Keyboard support for device info cells
document.addEventListener('DOMContentLoaded', function() {
    const deviceCells = document.querySelectorAll('.device-info-cell');
    deviceCells.forEach(function(cell) {
        cell.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                const button = this.parentElement.querySelector('.device-detail-toggle');
                if (button) {
                    button.click();
                }
            }
        });
    });
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeDeviceDetails();
    }
});

// Mobile touch interactions
if ('ontouchstart' in window) {
    document.querySelectorAll('.event-card').forEach(card => {
        card.addEventListener('touchstart', function() {
            this.style.transform = 'scale(0.98)';
        });
        card.addEventListener('touchend', function() {
            this.style.transform = 'scale(1)';
        });
    });
    
    // Touch interactions for device info cells
    document.querySelectorAll('.device-info-cell').forEach(cell => {
        cell.addEventListener('touchstart', function(e) {
            e.preventDefault();
            this.style.backgroundColor = '#f3f4f6';
        });
        cell.addEventListener('touchend', function(e) {
            e.preventDefault();
            this.style.backgroundColor = '';
            // Hide tooltip after a short delay on mobile
            setTimeout(() => hideDeviceTooltip(), 2000);
        });
    });
}
</script>
<?php endif; ?>

<?php if ($event_id > 0): ?>
<script>
window.toggleDeviceDetails = window.toggleDeviceDetails || function(panelId, button) {
    const panel = document.getElementById(panelId);
    if (!panel) {
        return;
    }

    const shouldOpen = !panel.classList.contains('show');
    window.closeDeviceDetails();

    if (shouldOpen) {
        panel.classList.add('show');
        const backdrop = document.getElementById('devicePopupBackdrop');
        if (backdrop) {
            backdrop.classList.add('show');
        }
        if (button) {
            button.setAttribute('aria-expanded', 'true');
            button.textContent = 'Hide details';
        }
    }
};

window.closeDeviceDetails = window.closeDeviceDetails || function() {
    document.querySelectorAll('.device-details-panel.show').forEach(function(panel) {
        panel.classList.remove('show');
    });
    document.querySelectorAll('.device-detail-toggle[aria-expanded="true"]').forEach(function(button) {
        button.setAttribute('aria-expanded', 'false');
        button.textContent = 'View details';
    });

    const backdrop = document.getElementById('devicePopupBackdrop');
    if (backdrop) {
        backdrop.classList.remove('show');
    }
};

document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        window.closeDeviceDetails();
    }
});
</script>
<?php endif; ?>

<?php renderAppShellEnd("attendance"); ?>
