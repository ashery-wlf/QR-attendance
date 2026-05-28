<?php
include("includes/auth.php");
include("includes/db.php");
include("includes/app.php");

ensureEventSchema($conn);
appRequireRole('super_admin');

$userId = (int) ($_SESSION['user_id'] ?? 0);
$error = '';
$action = $_POST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!appVerifyCsrf()) {
        die("Security check failed.");
    }

    if ($action === 'create') {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $website = trim($_POST['website'] ?? '');
        $contactEmail = trim($_POST['contact_email'] ?? '');

        if ($name === '') {
            $error = "Organization name is required.";
        } elseif ($contactEmail !== '' && !filter_var($contactEmail, FILTER_VALIDATE_EMAIL)) {
            $error = "Contact email is not valid.";
        } else {
            $stmt = $conn->prepare("
                INSERT INTO organizations(name, description, website, contact_email, is_active, created_by)
                VALUES(?, ?, ?, ?, 1, ?)
            ");
            $stmt->bind_param("ssssi", $name, $description, $website, $contactEmail, $userId);
            $stmt->execute();
            appSetFlash("Organization created.", 'success');
            header("Location: organizations.php");
            exit();
        }
    }

    if ($action === 'update') {
        $organizationId = (int) ($_POST['organization_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $website = trim($_POST['website'] ?? '');
        $contactEmail = trim($_POST['contact_email'] ?? '');

        if ($organizationId <= 0 || $name === '') {
            $error = "Choose an organization and enter its name.";
        } elseif ($contactEmail !== '' && !filter_var($contactEmail, FILTER_VALIDATE_EMAIL)) {
            $error = "Contact email is not valid.";
        } else {
            $stmt = $conn->prepare("
                UPDATE organizations
                SET name = ?, description = ?, website = ?, contact_email = ?
                WHERE id = ?
            ");
            $stmt->bind_param("ssssi", $name, $description, $website, $contactEmail, $organizationId);
            $stmt->execute();
            appSetFlash("Organization updated.", 'success');
            header("Location: organizations.php");
            exit();
        }
    }

    if ($action === 'toggle') {
        $organizationId = (int) ($_POST['organization_id'] ?? 0);
        $targetStatus = (int) ($_POST['target_status'] ?? 0) === 1 ? 1 : 0;

        if ($organizationId > 0) {
            $stmt = $conn->prepare("UPDATE organizations SET is_active = ? WHERE id = ?");
            $stmt->bind_param("ii", $targetStatus, $organizationId);
            $stmt->execute();
            appSetFlash($targetStatus === 1 ? "Organization activated." : "Organization deactivated.", 'success');
            header("Location: organizations.php");
            exit();
        }
    }

    if ($action === 'create_org_admin') {
        $adminName = trim($_POST['admin_name'] ?? '');
        $adminEmail = trim($_POST['admin_email'] ?? '');
        $adminPhone = trim($_POST['admin_phone'] ?? '');
        $adminPasswordRaw = $_POST['admin_password'] ?? '';
        $organizationId = (int) ($_POST['admin_organization_id'] ?? 0);

        if ($adminName === '' || $adminEmail === '' || $adminPhone === '' || $adminPasswordRaw === '' || $organizationId <= 0) {
            $error = "Fill all Organization Admin details.";
        } elseif (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
            $error = "Admin email is not valid.";
        } elseif (strlen($adminPasswordRaw) < 6) {
            $error = "Admin password must be at least 6 characters.";
        } else {
            $orgStmt = $conn->prepare("SELECT id FROM organizations WHERE id = ? AND is_active = 1 LIMIT 1");
            $orgStmt->bind_param("i", $organizationId);
            $orgStmt->execute();
            $orgResult = $orgStmt->get_result();

            $emailStmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
            $emailStmt->bind_param("s", $adminEmail);
            $emailStmt->execute();
            $emailResult = $emailStmt->get_result();

            if (!$orgResult || $orgResult->num_rows === 0) {
                $error = "Choose an active organization.";
            } elseif ($emailResult && $emailResult->num_rows > 0) {
                $error = "A user with that email already exists.";
            } else {
                $adminPassword = password_hash($adminPasswordRaw, PASSWORD_DEFAULT);
                $role = 'organization_admin';
                $status = 'active';
                $userColumns = appTableColumns($conn, 'users');

                if (isset($userColumns['password_hash'])) {
                    $stmt = $conn->prepare("
                        INSERT INTO users(name, email, phone, password, password_hash, role, organization_id, status, is_active)
                        VALUES(?, ?, ?, ?, ?, ?, ?, ?, 1)
                    ");
                    $stmt->bind_param("ssssssis", $adminName, $adminEmail, $adminPhone, $adminPassword, $adminPassword, $role, $organizationId, $status);
                } else {
                    $stmt = $conn->prepare("
                        INSERT INTO users(name, email, phone, password, role, organization_id, status, is_active)
                        VALUES(?, ?, ?, ?, ?, ?, ?, 1)
                    ");
                    $stmt->bind_param("sssssis", $adminName, $adminEmail, $adminPhone, $adminPassword, $role, $organizationId, $status);
                }

                $stmt->execute();
                appSetFlash("Organization Admin created.", 'success');
                header("Location: organizations.php");
                exit();
            }
        }
    }

    if ($action === 'toggle_user') {
        $targetUserId = (int) ($_POST['user_id'] ?? 0);
        $targetStatus = ($_POST['target_status'] ?? 'disabled') === 'active' ? 'active' : 'disabled';
        $isActive = $targetStatus === 'active' ? 1 : 0;

        if ($targetUserId > 0) {
            $stmt = $conn->prepare("UPDATE users SET status = ?, is_active = ? WHERE id = ? AND role = 'organization_admin'");
            $stmt->bind_param("sii", $targetStatus, $isActive, $targetUserId);
            $stmt->execute();
            appSetFlash($targetStatus === 'active' ? "Organization Admin activated." : "Organization Admin disabled.", 'success');
            header("Location: organizations.php");
            exit();
        }
    }
}

if ($error !== '') {
    appSetFlash($error, 'error');
}

$organizations = $conn->query("
    SELECT o.*,
           (SELECT COUNT(*) FROM users u WHERE u.organization_id = o.id) AS user_count,
           (SELECT COUNT(*) FROM events e WHERE e.organization_id = o.id AND e.deleted = FALSE) AS event_count,
           (SELECT COUNT(*) FROM venues v WHERE v.organization_id = o.id) AS venue_count
    FROM organizations o
    ORDER BY o.is_active DESC, o.name ASC
");

$totalOrgsResult = $conn->query("SELECT COUNT(*) AS total FROM organizations");
$activeOrgsResult = $conn->query("SELECT COUNT(*) AS total FROM organizations WHERE is_active = 1");
$orgAdminsResult = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role = 'organization_admin'");
$totalEventsResult = $conn->query("SELECT COUNT(*) AS total FROM events WHERE deleted = FALSE");
$totalOrgs = $totalOrgsResult ? (int) $totalOrgsResult->fetch_assoc()['total'] : 0;
$activeOrgs = $activeOrgsResult ? (int) $activeOrgsResult->fetch_assoc()['total'] : 0;
$orgAdmins = $orgAdminsResult ? (int) $orgAdminsResult->fetch_assoc()['total'] : 0;
$totalEvents = $totalEventsResult ? (int) $totalEventsResult->fetch_assoc()['total'] : 0;
$organizationOptions = $conn->query("SELECT id, name FROM organizations WHERE is_active = 1 ORDER BY name ASC");

$pageCss = <<<'CSS'
<style>
.admin-grid{
    display:grid;
    grid-template-columns:repeat(4, minmax(0, 1fr));
    gap:14px;
    margin-bottom:18px;
}
.metric,.panel,.org-card{
    background:#fff;
    border:1px solid #e2e8f0;
    border-radius:16px;
    box-shadow:0 12px 28px rgba(15, 23, 42, 0.06);
}
.metric{padding:16px;}
.metric span{
    display:block;
    color:#64748b;
    font-size:13px;
    font-weight:800;
}
.metric strong{
    display:block;
    margin-top:6px;
    font-size:30px;
    color:#0f172a;
}
.org-layout{
    display:grid;
    grid-template-columns:360px 1fr;
    gap:16px;
}
.panel{padding:18px;}
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
.form input,.form textarea,.form select{
    width:100%;
    border:1px solid #dbe4f0;
    border-radius:12px;
    padding:12px;
    font-size:14px;
    background:#f8fbff;
    color:#0f172a;
}
.form textarea{
    min-height:88px;
    resize:vertical;
}
.btn,.tiny-btn{
    border:none;
    border-radius:12px;
    font-weight:900;
    cursor:pointer;
}
.btn{padding:12px 14px;}
.tiny-btn{padding:8px 10px;font-size:12px;}
.primary{background:#2563ff;color:#fff;}
.muted{background:#e2e8f0;color:#334155;}
.danger{background:#fee2e2;color:#b91c1c;}
.success{background:#dcfce7;color:#166534;}
.org-list,.side-stack,.admin-list,.org-actions{
    display:grid;
    gap:12px;
}
.org-card{padding:14px;}
.org-head{
    display:flex;
    justify-content:space-between;
    gap:12px;
    align-items:flex-start;
}
.org-head h3{
    margin:0;
    font-size:18px;
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
.org-desc{
    margin:8px 0 0;
    color:#64748b;
    font-size:13px;
    line-height:1.45;
}
.org-stats{
    display:flex;
    gap:8px;
    flex-wrap:wrap;
    margin:12px 0;
}
.org-stat{
    background:#f8fafc;
    border:1px solid #e2e8f0;
    border-radius:10px;
    padding:8px 10px;
    font-size:12px;
    font-weight:800;
    color:#334155;
}
.edit-grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:10px;
}
.edit-grid label:first-child,
.edit-grid label:nth-child(2){
    grid-column:1 / -1;
}
.status-form{
    display:flex;
    justify-content:flex-end;
}
.admin-list{
    border-top:1px solid #e2e8f0;
    margin-top:12px;
    padding-top:12px;
    gap:8px;
}
.admin-list h4{
    margin:0;
    font-size:14px;
    color:#334155;
}
.admin-row{
    display:flex;
    justify-content:space-between;
    gap:10px;
    align-items:center;
    background:#f8fafc;
    border:1px solid #e2e8f0;
    border-radius:12px;
    padding:10px;
}
.admin-row strong{
    display:block;
    font-size:13px;
}
.admin-row span{
    display:block;
    margin-top:2px;
    font-size:12px;
    color:#64748b;
}
@media (max-width: 900px){
    .admin-grid{grid-template-columns:repeat(2, minmax(0, 1fr));}
    .org-layout{grid-template-columns:1fr;}
}
@media (max-width: 520px){
    .admin-grid,.edit-grid{grid-template-columns:1fr;}
}
</style>
CSS;

renderAppShellStart($conn, [
    "title" => "Organizations",
    "active" => "organizations",
    "page_title" => "Organizations",
    "page_subtitle" => "Manage organizations and create Organization Admin users here. Branding and help content are in System Settings.",
    "extra_head" => $pageCss,
]);
?>

<section class="admin-grid">
    <article class="metric"><span>Total Organizations</span><strong><?php echo $totalOrgs; ?></strong></article>
    <article class="metric"><span>Active Organizations</span><strong><?php echo $activeOrgs; ?></strong></article>
    <article class="metric"><span>Organization Admins</span><strong><?php echo $orgAdmins; ?></strong></article>
    <article class="metric"><span>Total Events</span><strong><?php echo $totalEvents; ?></strong></article>
</section>

<section class="org-layout">
    <aside class="side-stack">
        <div class="panel">
            <h2>Create Organization</h2>
            <form method="POST" class="form">
                <?php echo appCsrfInput(); ?>
                <input type="hidden" name="action" value="create">
                <label>Name <input type="text" name="name" required></label>
                <label>Description <textarea name="description"></textarea></label>
                <label>Website <input type="url" name="website" placeholder="https://example.com"></label>
                <label>Contact Email <input type="email" name="contact_email"></label>
                <button type="submit" class="btn primary">Create Organization</button>
            </form>
        </div>

        <div class="panel">
            <h2>Create Org Admin</h2>
            <form method="POST" class="form">
                <?php echo appCsrfInput(); ?>
                <input type="hidden" name="action" value="create_org_admin">
                <label>
                    Organization
                    <select name="admin_organization_id" required>
                        <option value="">Select organization</option>
                        <?php if ($organizationOptions): ?>
                            <?php while ($option = $organizationOptions->fetch_assoc()): ?>
                                <option value="<?php echo (int) $option['id']; ?>"><?php echo h($option['name']); ?></option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </label>
                <label>Full Name <input type="text" name="admin_name" required></label>
                <label>Email <input type="email" name="admin_email" required></label>
                <label>Phone <input type="tel" name="admin_phone" required></label>
                <label>Temporary Password <input type="password" name="admin_password" minlength="6" required></label>
                <button type="submit" class="btn primary">Create Org Admin</button>
            </form>
        </div>
    </aside>

    <div class="panel">
        <h2>Managed Organizations</h2>
        <div class="org-list">
            <?php if ($organizations && $organizations->num_rows > 0): ?>
                <?php while ($organization = $organizations->fetch_assoc()): ?>
                    <?php
                    $organizationId = (int) $organization['id'];
                    $adminRows = $conn->query("
                        SELECT id, name, email, phone, status
                        FROM users
                        WHERE organization_id = $organizationId
                        AND role = 'organization_admin'
                        ORDER BY status ASC, name ASC
                    ");
                    ?>
                    <article class="org-card">
                        <div class="org-head">
                            <div>
                                <h3><?php echo h($organization['name']); ?></h3>
                                <p class="org-desc"><?php echo h($organization['description'] ?: 'No description added.'); ?></p>
                            </div>
                            <span class="badge <?php echo (int) $organization['is_active'] === 1 ? 'active' : 'disabled'; ?>">
                                <?php echo (int) $organization['is_active'] === 1 ? 'Active' : 'Disabled'; ?>
                            </span>
                        </div>

                        <div class="org-stats">
                            <span class="org-stat"><?php echo (int) $organization['user_count']; ?> users</span>
                            <span class="org-stat"><?php echo (int) $organization['event_count']; ?> events</span>
                            <span class="org-stat"><?php echo (int) $organization['venue_count']; ?> venues</span>
                            <?php if (!empty($organization['contact_email'])): ?>
                                <span class="org-stat"><?php echo h($organization['contact_email']); ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="admin-list">
                            <h4>Organization Admins</h4>
                            <?php if ($adminRows && $adminRows->num_rows > 0): ?>
                                <?php while ($admin = $adminRows->fetch_assoc()): ?>
                                    <div class="admin-row">
                                        <div>
                                            <strong><?php echo h($admin['name']); ?></strong>
                                            <span><?php echo h($admin['email']); ?><?php echo !empty($admin['phone']) ? ' - ' . h($admin['phone']) : ''; ?></span>
                                        </div>
                                        <form method="POST">
                                            <?php echo appCsrfInput(); ?>
                                            <input type="hidden" name="action" value="toggle_user">
                                            <input type="hidden" name="user_id" value="<?php echo (int) $admin['id']; ?>">
                                            <?php if (($admin['status'] ?? 'active') === 'active'): ?>
                                                <input type="hidden" name="target_status" value="disabled">
                                                <button type="submit" class="tiny-btn danger">Disable</button>
                                            <?php else: ?>
                                                <input type="hidden" name="target_status" value="active">
                                                <button type="submit" class="tiny-btn success">Activate</button>
                                            <?php endif; ?>
                                        </form>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="org-desc">No organization admin assigned yet.</div>
                            <?php endif; ?>
                        </div>

                        <div class="org-actions">
                            <form method="POST" class="form edit-grid">
                                <?php echo appCsrfInput(); ?>
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="organization_id" value="<?php echo (int) $organization['id']; ?>">
                                <label>Name <input type="text" name="name" value="<?php echo h($organization['name']); ?>" required></label>
                                <label>Description <textarea name="description"><?php echo h($organization['description']); ?></textarea></label>
                                <label>Website <input type="url" name="website" value="<?php echo h($organization['website']); ?>"></label>
                                <label>Contact Email <input type="email" name="contact_email" value="<?php echo h($organization['contact_email']); ?>"></label>
                                <button type="submit" class="btn muted">Save Changes</button>
                            </form>

                            <form method="POST" class="status-form">
                                <?php echo appCsrfInput(); ?>
                                <input type="hidden" name="action" value="toggle">
                                <input type="hidden" name="organization_id" value="<?php echo (int) $organization['id']; ?>">
                                <?php if ((int) $organization['is_active'] === 1): ?>
                                    <input type="hidden" name="target_status" value="0">
                                    <button type="submit" class="btn danger">Deactivate</button>
                                <?php else: ?>
                                    <input type="hidden" name="target_status" value="1">
                                    <button type="submit" class="btn success">Activate</button>
                                <?php endif; ?>
                            </form>
                        </div>
                    </article>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="org-card">No organizations found.</div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php renderAppShellEnd("organizations"); ?>
