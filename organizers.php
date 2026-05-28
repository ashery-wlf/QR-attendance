<?php
include("includes/auth.php");
include("includes/db.php");
include("includes/app.php");

ensureEventSchema($conn);
appRequireRole('organization_admin');

$user_id = (int) $_SESSION['user_id'];
$organization_id = appCurrentOrganizationId();
$message = "";
$error = "";

if ($organization_id <= 0) {
    die("Your account is not assigned to an organization.");
}

$orgResult = $conn->query("SELECT * FROM organizations WHERE id=$organization_id LIMIT 1");
$organization = $orgResult && $orgResult->num_rows > 0 ? $orgResult->fetch_assoc() : null;
if (!$organization) {
    die("Organization not found.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!appVerifyCsrf()) {
        die("Security check failed.");
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $department = trim($_POST['department'] ?? '');
        $passwordRaw = $_POST['password'] ?? '';

        if ($name === '' || $email === '' || $phone === '' || $passwordRaw === '') {
            $error = "Fill all required organizer details.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Email is not valid.";
        } elseif (strlen($passwordRaw) < 6) {
            $error = "Password must be at least 6 characters.";
        } else {
            $emailStmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
            $emailStmt->bind_param("s", $email);
            $emailStmt->execute();
            $emailResult = $emailStmt->get_result();

            if ($emailResult && $emailResult->num_rows > 0) {
                $error = "A user with that email already exists.";
            } else {
                $password = password_hash($passwordRaw, PASSWORD_DEFAULT);
                $role = 'event_organizer';
                $status = 'active';
                $stmt = $conn->prepare("
                    INSERT INTO users(name, email, phone, password, password_hash, role, organization_id, department, status, is_active)
                    VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
                ");
                $stmt->bind_param("ssssssiss", $name, $email, $phone, $password, $password, $role, $organization_id, $department, $status);

                if ($stmt->execute()) {
                    $message = "Event Organizer created.";
                } else {
                    $error = "Event Organizer could not be created.";
                }
            }
        }
    }

    if ($action === 'toggle') {
        $targetUserId = (int) ($_POST['user_id'] ?? 0);
        $targetStatus = ($_POST['target_status'] ?? 'disabled') === 'active' ? 'active' : 'disabled';
        $isActive = $targetStatus === 'active' ? 1 : 0;

        $stmt = $conn->prepare("
            UPDATE users
            SET status = ?, is_active = ?
            WHERE id = ?
            AND organization_id = ?
            AND role = 'event_organizer'
        ");
        $stmt->bind_param("siii", $targetStatus, $isActive, $targetUserId, $organization_id);

        if ($stmt->execute()) {
            $message = $targetStatus === 'active' ? "Event Organizer activated." : "Event Organizer disabled.";
        } else {
            $error = "Organizer status could not be changed.";
        }
    }
}

$organizers = $conn->query("
    SELECT u.*,
           (SELECT COUNT(*) FROM events e WHERE e.created_by = u.id AND e.deleted = FALSE) AS event_count
    FROM users u
    WHERE u.organization_id = $organization_id
    AND u.role = 'event_organizer'
    ORDER BY u.status ASC, u.name ASC
");

$activeOrganizersResult = $conn->query("SELECT COUNT(*) AS total FROM users WHERE organization_id=$organization_id AND role='event_organizer' AND status='active'");
$disabledOrganizersResult = $conn->query("SELECT COUNT(*) AS total FROM users WHERE organization_id=$organization_id AND role='event_organizer' AND status='disabled'");
$orgEventsResult = $conn->query("SELECT COUNT(*) AS total FROM events WHERE organization_id=$organization_id AND deleted = FALSE");
$activeOrganizers = $activeOrganizersResult ? (int) $activeOrganizersResult->fetch_assoc()['total'] : 0;
$disabledOrganizers = $disabledOrganizersResult ? (int) $disabledOrganizersResult->fetch_assoc()['total'] : 0;
$orgEvents = $orgEventsResult ? (int) $orgEventsResult->fetch_assoc()['total'] : 0;

$pageCss = <<<'CSS'
<style>
.metrics{
    display:grid;
    grid-template-columns:repeat(3, minmax(0, 1fr));
    gap:14px;
    margin-bottom:18px;
}
.metric,.panel{
    background:#fff;
    border:1px solid #e2e8f0;
    border-radius:18px;
    box-shadow:0 12px 28px rgba(15, 23, 42, 0.06);
}
.metric{
    padding:16px;
}
.metric span{
    display:block;
    color:#64748b;
    font-size:13px;
    font-weight:800;
}
.metric strong{
    display:block;
    margin-top:6px;
    font-size:32px;
}
.layout{
    display:grid;
    grid-template-columns:360px 1fr;
    gap:16px;
}
.panel{
    padding:18px;
}
.panel h2{
    margin:0 0 12px;
    font-size:20px;
}
.form{
    display:grid;
    gap:12px;
}
.form label{
    display:grid;
    gap:6px;
    font-size:13px;
    font-weight:800;
    color:#334155;
}
.form input{
    width:100%;
    border:1px solid #dbe4f0;
    border-radius:12px;
    padding:12px;
    font-size:14px;
    background:#f8fbff;
    color:#0f172a;
}
.btn{
    border:none;
    border-radius:12px;
    padding:12px 14px;
    font-weight:900;
    cursor:pointer;
}
.primary{background:#2563ff;color:#fff;}
.danger{background:#fee2e2;color:#b91c1c;}
.success{background:#dcfce7;color:#166534;}
.message,.error{
    padding:12px 14px;
    border-radius:14px;
    margin-bottom:14px;
    font-weight:800;
}
.message{background:#ecfdf5;color:#166534;border:1px solid #bbf7d0;}
.error{background:#fef2f2;color:#b91c1c;border:1px solid #fecaca;}
.list{
    display:grid;
    gap:12px;
}
.organizer-card{
    border:1px solid #e2e8f0;
    border-radius:16px;
    background:#fff;
    padding:14px;
    display:grid;
    gap:12px;
}
.organizer-head{
    display:flex;
    justify-content:space-between;
    gap:12px;
    align-items:flex-start;
}
.organizer-head h3{
    margin:0;
    font-size:18px;
}
.organizer-head p{
    margin:5px 0 0;
    color:#64748b;
    font-size:13px;
}
.badge{
    display:inline-flex;
    align-items:center;
    border-radius:999px;
    padding:6px 10px;
    font-size:12px;
    font-weight:900;
}
.badge.active{background:#dcfce7;color:#166534;}
.badge.disabled{background:#f1f5f9;color:#64748b;}
.meta{
    display:flex;
    gap:8px;
    flex-wrap:wrap;
}
.meta span{
    background:#f8fafc;
    border:1px solid #e2e8f0;
    border-radius:10px;
    padding:8px 10px;
    font-size:12px;
    font-weight:800;
    color:#334155;
}
.actions{
    display:flex;
    justify-content:flex-end;
}
.empty{
    border:1px dashed #cbd5e1;
    border-radius:16px;
    padding:24px;
    color:#64748b;
    text-align:center;
    background:#f8fafc;
}
@media (max-width: 900px){
    .metrics{grid-template-columns:1fr;}
    .layout{grid-template-columns:1fr;}
}
</style>
CSS;

renderAppShellStart($conn, [
    "title" => "Organizers",
    "active" => "organizers",
    "page_title" => "Event Organizers",
    "page_subtitle" => "Create and control organizers for " . ($organization['name'] ?? 'your organization') . ".",
    "extra_head" => $pageCss,
]);
?>

<?php if ($message !== ''): ?>
    <div class="message"><?php echo h($message); ?></div>
<?php endif; ?>
<?php if ($error !== ''): ?>
    <div class="error"><?php echo h($error); ?></div>
<?php endif; ?>

<section class="metrics">
    <article class="metric"><span>Active Organizers</span><strong><?php echo $activeOrganizers; ?></strong></article>
    <article class="metric"><span>Disabled Organizers</span><strong><?php echo $disabledOrganizers; ?></strong></article>
    <article class="metric"><span>Organization Events</span><strong><?php echo $orgEvents; ?></strong></article>
</section>

<section class="layout">
    <aside class="panel">
        <h2>Create Organizer</h2>
        <form method="POST" class="form">
            <?php echo appCsrfInput(); ?>
            <input type="hidden" name="action" value="create">
            <label>
                Full Name
                <input type="text" name="name" required>
            </label>
            <label>
                Email
                <input type="email" name="email" required>
            </label>
            <label>
                Phone
                <input type="tel" name="phone" required>
            </label>
            <label>
                Department
                <input type="text" name="department">
            </label>
            <label>
                Temporary Password
                <input type="password" name="password" minlength="6" required>
            </label>
            <button type="submit" class="btn primary">Create Event Organizer</button>
        </form>
    </aside>

    <div class="panel">
        <h2>Organization Organizers</h2>
        <div class="list">
            <?php if ($organizers && $organizers->num_rows > 0): ?>
                <?php while ($organizer = $organizers->fetch_assoc()): ?>
                    <article class="organizer-card">
                        <div class="organizer-head">
                            <div>
                                <h3><?php echo h($organizer['name']); ?></h3>
                                <p><?php echo h($organizer['email']); ?><?php echo !empty($organizer['phone']) ? ' · ' . h($organizer['phone']) : ''; ?></p>
                            </div>
                            <span class="badge <?php echo ($organizer['status'] ?? 'active') === 'active' ? 'active' : 'disabled'; ?>">
                                <?php echo h(ucfirst($organizer['status'] ?? 'active')); ?>
                            </span>
                        </div>
                        <div class="meta">
                            <span><?php echo (int) $organizer['event_count']; ?> events</span>
                            <span><?php echo h($organizer['department'] ?: 'No department'); ?></span>
                            <span>Created <?php echo h(date('M j, Y', strtotime($organizer['created_at']))); ?></span>
                        </div>
                        <form method="POST" class="actions">
                            <?php echo appCsrfInput(); ?>
                            <input type="hidden" name="action" value="toggle">
                            <input type="hidden" name="user_id" value="<?php echo (int) $organizer['id']; ?>">
                            <?php if (($organizer['status'] ?? 'active') === 'active'): ?>
                                <input type="hidden" name="target_status" value="disabled">
                                <button type="submit" class="btn danger">Disable</button>
                            <?php else: ?>
                                <input type="hidden" name="target_status" value="active">
                                <button type="submit" class="btn success">Activate</button>
                            <?php endif; ?>
                        </form>
                    </article>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty">No event organizers have been created for this organization yet.</div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php renderAppShellEnd("organizers"); ?>
