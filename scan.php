<?php
include("includes/auth.php");
include("includes/db.php");
include("includes/app.php");

ensureEventSchema($conn);
appRequireRole('attendee');

$user_id = (int) $_SESSION['user_id'];
$event_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$event = $conn->query("SELECT * FROM events WHERE id=$event_id AND deleted = FALSE LIMIT 1")->fetch_assoc();

// Get user's registered events for the modal
$userEvents = $conn->query("
    SELECT e.*, p.access_code
    FROM events e
    LEFT JOIN participants p ON e.id = p.event_id AND p.user_id = $user_id
    WHERE e.deleted = FALSE
    AND (e.created_by = $user_id OR p.user_id = $user_id)
    ORDER BY e.date DESC, e.time DESC
");

$showEventModal = false;
if (!$event && $userEvents && $userEvents->num_rows > 0) {
    $showEventModal = true;
} elseif (!$event) {
    die("No events available. Please join an event first.");
}

// Only process event data if event exists
$isAdmin = false;
$isRegistered = false;
$windowState = 'closed';
$attendanceOpenTime = '';

if ($event) {
    $isAdmin = (int) $event['created_by'] === $user_id;
    $isRegistered = registrationExists($conn, $user_id, $event_id) || $isAdmin;
    $windowState = attendanceWindowState($event);
    [$attendanceOpenTime] = appAutomaticAttendanceWindow($event['time'] ?? '');
}
?>
<?php
$pageCss = <<<'CSS'
<script src="https://unpkg.com/html5-qrcode"></script>
<style>
.shell{
    background:#fff;
    border:1px solid #dce5f1;
    border-radius:24px;
    padding:24px;
    box-shadow:0 20px 40px rgba(15, 23, 42, 0.08);
}

h1{
    margin:0 0 8px;
    font-size:34px;
}

p{
    margin:0;
    color:#64748b;
}

.notice{
    margin-top:18px;
    padding:14px 16px;
    border-radius:14px;
    font-weight:700;
    background:#eff6ff;
    color:#1d4ed8;
}

.error{
    background:#fef2f2;
    color:#b91c1c;
}

#reader{
    width:min(100%, 420px);
    margin:14px auto 0;
    display:none;
}

.scan-actions{
    margin-top:18px;
    display:flex;
    gap:10px;
    flex-wrap:wrap;
}

.scan-actions button{
    height:44px;
    border:none;
    border-radius:12px;
    padding:0 18px;
    font-weight:800;
    background:#0f172a;
    color:#fff;
    cursor:pointer;
}

.manual-box{
    margin-top:22px;
    border-top:1px solid #e5eaf3;
    padding-top:18px;
}

.manual-box h2{
    margin:0 0 8px;
    font-size:20px;
}

.manual-box p{
    margin:0 0 12px;
}

.manual-row{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
}

.manual-row input{
    flex:1;
    min-width:220px;
    height:44px;
    border:1px solid #dce5f1;
    border-radius:12px;
    padding:0 14px;
    font-size:14px;
    background:#fff;
}

.manual-row button{
    height:44px;
    border:none;
    border-radius:12px;
    padding:0 18px;
    font-weight:800;
    background:#1d4ed8;
    color:#fff;
    cursor:pointer;
}

.upload-row{
    margin-top:12px;
}

.upload-row input{
    width:100%;
    border:1px solid #dce5f1;
    border-radius:12px;
    padding:10px 12px;
    background:#fff;
}

#result{
    margin-top:18px;
    padding:14px 16px;
    border-radius:14px;
    background:#f8fafc;
    min-height:52px;
}

a{
    display:inline-block;
    margin-top:20px;
    color:#1d4ed8;
    text-decoration:none;
    font-weight:700;
}

.event-modal-overlay{
    position:fixed;
    top:0;
    left:0;
    width:100%;
    height:100%;
    background:rgba(0,0,0,0.5);
    display:flex;
    align-items:center;
    justify-content:center;
    z-index:9999;
}

.event-modal{
    background:#fff;
    border-radius:16px;
    padding:24px;
    max-width:500px;
    width:90%;
    max-height:80vh;
    overflow-y:auto;
    box-shadow:0 20px 40px rgba(0,0,0,0.2);
}

.event-modal-header{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:16px;
    margin-bottom:16px;
}

.event-modal h2{
    margin:0;
    font-size:24px;
    color:#0f172a;
}

.event-modal-close{
    width:36px;
    height:36px;
    border:none;
    border-radius:10px;
    background:#f1f5f9;
    color:#0f172a;
    font-size:24px;
    line-height:1;
    cursor:pointer;
}

.event-modal-close:hover{
    background:#e2e8f0;
}

.event-modal p{
    margin:0 0 20px;
    color:#64748b;
}

.event-list{
    display:flex;
    flex-direction:column;
    gap:12px;
}

.event-modal-actions{
    display:flex;
    justify-content:flex-end;
    margin-top:18px;
}

.event-modal-cancel{
    height:40px;
    border:1px solid #cbd5e1;
    border-radius:10px;
    padding:0 18px;
    background:#fff;
    color:#0f172a;
    font-weight:700;
    cursor:pointer;
}

.event-modal-cancel:hover{
    background:#f8fafc;
}

.event-item{
    border:1px solid #e2e8f0;
    border-radius:12px;
    padding:16px;
    cursor:pointer;
    transition:all 0.2s;
}

.event-item:hover{
    border-color:#2563ff;
    background:#f8fafc;
}

.event-item-name{
    font-weight:600;
    color:#0f172a;
    margin-bottom:4px;
}

.event-item-details{
    font-size:13px;
    color:#64748b;
}

.event-item-status{
    display:inline-block;
    padding:4px 12px;
    border-radius:20px;
    font-size:12px;
    font-weight:600;
    margin-top:8px;
}

.status-live{
    background:#fff3e0;
    color:#e65100;
}

.status-upcoming{
    background:#e3f2fd;
    color:#1565c0;
}

.status-ended{
    background:#e8f5e9;
    color:#2e7d32;
}
</style>
CSS;

renderAppShellStart($conn, [
    "title" => "Scan Attendance",
    "active" => "attendance",
    "page_title" => "Scan Attendance",
    "page_subtitle" => "Open the camera and scan the admin live QR code during the attendance window.",
    "search_placeholder" => "Search events...",
    "extra_head" => $pageCss,
]);
?>

<?php if ($showEventModal): ?>
<div class="event-modal-overlay" id="eventModal">
    <div class="event-modal">
        <div class="event-modal-header">
            <h2>Select an Event</h2>
            <button type="button" class="event-modal-close" onclick="closeEventModal()" aria-label="Cancel event selection">&times;</button>
        </div>
        <p>Choose an event to scan attendance for:</p>
        <div class="event-list">
            <?php while ($evt = $userEvents->fetch_assoc()): ?>
                <?php
                $status = eventLifecycleStatus($evt);
                $statusClass = 'status-' . $status;
                $statusLabel = ucfirst($status);
                ?>
                <div class="event-item" onclick="selectEvent(<?php echo (int)$evt['id']; ?>)">
                    <div class="event-item-name"><?php echo h($evt['name']); ?></div>
                    <div class="event-item-details">
                        <?php echo h(formatEventDate($evt['date'])); ?> • <?php echo h(formatEventTime($evt['time'])); ?>
                    </div>
                    <div class="event-item-status <?php echo $statusClass; ?>"><?php echo $statusLabel; ?></div>
                </div>
            <?php endwhile; ?>
        </div>
        <div class="event-modal-actions">
            <button type="button" class="event-modal-cancel" onclick="closeEventModal()">Cancel</button>
        </div>
    </div>
</div>
<script>
function closeEventModal() {
    const modal = document.getElementById("eventModal");
    if (modal) {
        modal.style.display = "none";
    }
}

function selectEvent(eventId) {
    window.location.href = "scan.php?id=" + eventId;
}

document.getElementById("eventModal").addEventListener("click", function(event) {
    if (event.target === this) {
        closeEventModal();
    }
});

document.addEventListener("keydown", function(event) {
    if (event.key === "Escape") {
        closeEventModal();
    }
});
</script>
<?php endif; ?>

<?php if ($event): ?>
<div class="shell">
    <h1>Scan Attendance</h1>
    <p><?php echo h($event['name']); ?> • <?php echo h(formatEventDate($event['date'])); ?> • <?php echo h(formatEventTime($event['time'])); ?></p>

    <?php if (!$isRegistered): ?>
        <div class="notice error">You must join this event before scanning attendance.</div>
    <?php elseif ($windowState === 'before'): ?>
        <div class="notice">Attendance has not opened yet. Scan starts at <?php echo h(formatEventTime($attendanceOpenTime)); ?>.</div>
    <?php elseif ($windowState === 'closed'): ?>
        <div class="notice error">Attendance window is already closed for this event.</div>
    <?php else: ?>
        <div class="notice">Camera scan will start automatically when your browser allows it. If it is blocked on a local IP, use HTTPS or localhost.</div>
        <div class="scan-actions">
            <button type="button" id="startCamera">Start Camera Scan</button>
        </div>
        <div id="reader"></div>
        <div class="manual-box">
            <h2>Fallback options</h2>
            <p>If camera access fails on local network, upload a QR image.</p>
            <div class="upload-row">
                <input type="file" id="qrImageInput" accept="image/*">
            </div>
        </div>
        <div id="result">Waiting for QR scan...</div>
    <?php endif; ?>

    <a href="event.php?id=<?php echo $event_id; ?>">Back to event</a>
</div>
<?php endif; ?>

<?php if ($isRegistered && $windowState === 'open'): ?>
<script>
let scannerBusy = false;
let scannerInstance = null;
let scannerStopped = false;
let scannerStarted = false;
let scanGeo = { lat: "", lng: "", address: "" };

function setResult(message, isError) {
    const box = document.getElementById("result");
    box.textContent = message;
    box.className = isError ? "notice error" : "notice";
}

function stopScanner() {
    if (!scannerInstance || scannerStopped) {
        return Promise.resolve();
    }

    scannerStopped = true;
    if (scannerStarted && typeof scannerInstance.stop === "function") {
        return scannerInstance.stop().then(() => scannerInstance.clear()).catch(() => {});
    }

    return scannerInstance.clear().catch(() => {});
}

function getDeviceInfo() {
    const userAgent = navigator.userAgent;
    let browser = "Unknown";
    let deviceType = "Desktop";
    let osName = "Unknown";
    
    // Detect OS
    if (userAgent.indexOf("Android") > -1) osName = "Android";
    else if (userAgent.indexOf("iPhone") > -1 || userAgent.indexOf("iPad") > -1) osName = "iOS";
    else if (userAgent.indexOf("Windows") > -1) osName = "Windows";
    else if (userAgent.indexOf("Mac") > -1) osName = "macOS";
    else if (userAgent.indexOf("Linux") > -1) osName = "Linux";
    
    // Detect browser with version
    if (userAgent.indexOf("Chrome") > -1 && userAgent.indexOf("Edg") === -1) {
        const match = userAgent.match(/Chrome\/(\d+)/);
        browser = "Chrome" + (match ? " v" + match[1] : "");
    }
    else if (userAgent.indexOf("Safari") > -1 && userAgent.indexOf("Chrome") === -1) {
        const match = userAgent.match(/Version\/(\d+)/);
        browser = "Safari" + (match ? " v" + match[1] : "");
    }
    else if (userAgent.indexOf("Firefox") > -1) {
        const match = userAgent.match(/Firefox\/(\d+)/);
        browser = "Firefox" + (match ? " v" + match[1] : "");
    }
    else if (userAgent.indexOf("Edg") > -1) {
        const match = userAgent.match(/Edg\/(\d+)/);
        browser = "Edge" + (match ? " v" + match[1] : "");
    }
    else if (userAgent.indexOf("Opera") > -1) {
        const match = userAgent.match(/Opera\/(\d+)/);
        browser = "Opera" + (match ? " v" + match[1] : "");
    }
    
    // Detect device type
    if (/Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(userAgent)) {
        deviceType = "Mobile";
        if (/iPad/i.test(userAgent)) deviceType = "Tablet";
    }
    
    // Get detailed device profiling
    const deviceProfile = {
        // Browser Info
        browser: browser,
        browser_name: browser.split(" ")[0],
        
        // OS Info
        os_name: osName,
        platform: navigator.platform,
        
        // Device Type
        device_type: deviceType,
        
        // Screen/Display
        screen_resolution: screen.width + "x" + screen.height,
        screen_dpi: (window.devicePixelRatio || 1) + "x",
        
        // Device Hardware
        device_memory: navigator.deviceMemory ? navigator.deviceMemory + " GB" : "Unknown",
        cpu_cores: navigator.hardwareConcurrency || "Unknown",
        
        // Connection Info
        connection_type: navigator.connection ? navigator.connection.effectiveType : "Unknown",
        
        // Language & Timezone
        language: navigator.language,
        timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
        
        // Browser Features
        cookies_enabled: navigator.cookieEnabled ? "Yes" : "No",
        online_status: navigator.onLine ? "Online" : "Offline",
        
        // Complete User Agent
        user_agent: userAgent
    };
    
    return deviceProfile;
}


function getBrowserInfo() {
    const userAgent = navigator.userAgent;
    const browserInfo = {
        name: "Unknown",
        version: "Unknown",
        engine: "Unknown",
        platform: navigator.platform,
        language: navigator.language,
        languages: navigator.languages,
        cookie_enabled: navigator.cookieEnabled,
        on_line: navigator.onLine,
        java_enabled: navigator.javaEnabled(),
        do_not_track: navigator.doNotTrack,
        screen: {
            width: screen.width,
            height: screen.height,
            avail_width: screen.availWidth,
            avail_height: screen.availHeight,
            color_depth: screen.colorDepth,
            pixel_depth: screen.pixelDepth
        },
        window: {
            inner_width: window.innerWidth,
            inner_height: window.innerHeight,
            outer_width: window.outerWidth,
            outer_height: window.outerHeight
        },
        device_memory: navigator.deviceMemory || "Unknown",
        hardware_concurrency: navigator.hardwareConcurrency || "Unknown",
        connection: navigator.connection ? {
            effective_type: navigator.connection.effectiveType,
            downlink: navigator.connection.downlink,
            rtt: navigator.connection.rtt
        } : "Unknown"
    };
    
    // Detailed browser detection
    if (userAgent.indexOf("Chrome") > -1 && userAgent.indexOf("Edg") === -1) {
        const match = userAgent.match(/Chrome\/(\d+\.\d+)/);
        browserInfo.name = "Chrome";
        browserInfo.version = match ? match[1] : "Unknown";
        browserInfo.engine = "Blink";
    }
    else if (userAgent.indexOf("Safari") > -1 && userAgent.indexOf("Chrome") === -1) {
        const match = userAgent.match(/Version\/(\d+\.\d+)/);
        browserInfo.name = "Safari";
        browserInfo.version = match ? match[1] : "Unknown";
        browserInfo.engine = "WebKit";
    }
    else if (userAgent.indexOf("Firefox") > -1) {
        const match = userAgent.match(/Firefox\/(\d+\.\d+)/);
        browserInfo.name = "Firefox";
        browserInfo.version = match ? match[1] : "Unknown";
        browserInfo.engine = "Gecko";
    }
    else if (userAgent.indexOf("Edg") > -1) {
        const match = userAgent.match(/Edg\/(\d+\.\d+)/);
        browserInfo.name = "Edge";
        browserInfo.version = match ? match[1] : "Unknown";
        browserInfo.engine = "Blink";
    }
    else if (userAgent.indexOf("Opera") > -1 || userAgent.indexOf("OPR") > -1) {
        const match = userAgent.match(/(?:Opera|OPR)\/(\d+\.\d+)/);
        browserInfo.name = "Opera";
        browserInfo.version = match ? match[1] : "Unknown";
        browserInfo.engine = "Blink";
    }
    
    return browserInfo;
}

async function submitAttendance(token) {
    if (scannerBusy) {
        return;
    }
    scannerBusy = true;
    
    await ensureScanLocation();
    const deviceInfo = getDeviceInfo();
    const browserInfo = getBrowserInfo();

    fetch("attendance-submit.php", {
        method: "POST",
        headers: {"Content-Type": "application/x-www-form-urlencoded"},
        body: "event_id=<?php echo $event_id; ?>&qr_payload=" + encodeURIComponent(token)
            + "&scan_lat=" + encodeURIComponent(scanGeo.lat)
            + "&scan_lng=" + encodeURIComponent(scanGeo.lng)
            + "&scan_address=" + encodeURIComponent(scanGeo.address)
            + "&device_info=" + encodeURIComponent(JSON.stringify(deviceInfo))
            + "&browser_info=" + encodeURIComponent(JSON.stringify(browserInfo))
    })
    .then(res => res.text())
    .then(data => {
        const isError = data.toLowerCase().includes("not") || data.toLowerCase().includes("wrong") || data.toLowerCase().includes("closed") || data.toLowerCase().includes("already");
        setResult(data, isError);

        if (!isError || data.toLowerCase().includes("already")) {
            stopScanner();
            return;
        }

        scannerBusy = false;
    })
    .catch(() => {
        setResult("Could not submit attendance right now.", true);
        scannerBusy = false;
    });
}

function onScanSuccess(decodedText) {
    submitAttendance(decodedText);
}

function cameraBlockedMessage(error) {
    const protocol = window.location.protocol;
    const host = window.location.hostname;
    const isLocalhost = host === "localhost" || host === "127.0.0.1" || host === "::1";

    if (!window.isSecureContext && protocol !== "https:" && !isLocalhost) {
        return "Camera cannot be forced on this address. Browsers allow camera only on HTTPS or localhost. Open this page with HTTPS, or scan from the same PC using localhost.";
    }

    if (error && (error.name === "NotAllowedError" || error.name === "PermissionDeniedError")) {
        return "Camera permission was denied. Allow camera access in the browser, then press Start Camera Scan again.";
    }

    if (error && (error.name === "NotFoundError" || error.name === "DevicesNotFoundError")) {
        return "No camera was found on this device.";
    }

    return "Camera could not start. Allow camera permission, close other apps using the camera, then try again.";
}

async function startCameraScan() {
    const reader = document.getElementById("reader");
    const startButton = document.getElementById("startCamera");
    reader.style.display = "block";

    if (scannerStarted) {
        setResult("Camera scanner is already running.", false);
        return;
    }

    if (typeof Html5Qrcode === "undefined") {
        setResult("QR scanner library could not load. Check your internet connection or use the live code.", true);
        return;
    }

    try {
        scannerStopped = false;
        scannerInstance = scannerInstance || new Html5Qrcode("reader");
        startButton.disabled = true;
        startButton.textContent = "Starting Camera...";

        await scannerInstance.start(
            { facingMode: "environment" },
            { fps: 12, qrbox: { width: 240, height: 240 }, aspectRatio: 1.0 },
            onScanSuccess,
            function() {}
        );

        scannerStarted = true;
        startButton.textContent = "Camera Running";
        setResult("Camera is open. Point it at the live QR code.", false);
    } catch (error) {
        scannerStarted = false;
        startButton.disabled = false;
        startButton.textContent = "Start Camera Scan";
        setResult(cameraBlockedMessage(error), true);
    }
}

function captureScanLocation() {
    if (!navigator.geolocation) {
        return Promise.resolve(false);
    }

    return new Promise(function(resolve) {
        navigator.geolocation.getCurrentPosition(function(position) {
            scanGeo.lat = Number(position.coords.latitude.toFixed(7));
            scanGeo.lng = Number(position.coords.longitude.toFixed(7));
            scanGeo.address = scanGeo.lat + ", " + scanGeo.lng;
            resolve(true);
        }, function() {
            resolve(false);
        }, {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 60000
        });
    });
}

async function ensureScanLocation() {
    if (scanGeo.lat && scanGeo.lng) {
        return true;
    }

    setResult("Getting your location before submitting attendance...", false);
    return captureScanLocation();
}

captureScanLocation();

function selectEvent(eventId) {
    window.location.href = 'scan.php?id=' + eventId;
}

document.getElementById("startCamera").addEventListener("click", startCameraScan);

document.addEventListener("DOMContentLoaded", function() {
    startCameraScan();
});

document.getElementById("qrImageInput").addEventListener("change", async (event) => {
    const file = event.target.files && event.target.files[0];
    if (!file) {
        return;
    }

    try {
        const tempId = "file-reader-temp";
        const tempNode = document.createElement("div");
        tempNode.id = tempId;
        tempNode.style.display = "none";
        document.body.appendChild(tempNode);
        const imageScanner = new Html5Qrcode(tempId);
        const decodedText = await imageScanner.scanFile(file, true);
        await imageScanner.clear().catch(() => {});
        tempNode.remove();
        submitAttendance(decodedText);
    } catch (error) {
        setResult("Could not read that QR image. Try another screenshot.", true);
        scannerBusy = false;
    }
});
</script>
<?php endif; ?>
<?php renderAppShellEnd("attendance"); ?>
