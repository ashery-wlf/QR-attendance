<?php
include("includes/auth.php");
include("includes/db.php");
include("includes/app.php");

ensureEventSchema($conn);
ensureSystemSettingsSchema($conn);
appRequireRole('super_admin');

$settings = appGetSystemSettings($conn);
$action = $_POST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'update_system') {
    if (!appVerifyCsrf()) {
        die("Security check failed.");
    }

    $systemName = trim($_POST['system_name'] ?? '');
    $brandColor = trim($_POST['brand_color'] ?? '#2563ff');
    $logoPath = trim($_POST['logo_path'] ?? '');
    $helpIntro = trim($_POST['help_intro'] ?? '');
    $faqContent = trim($_POST['faq_content'] ?? '');

    if ($systemName === '') {
        appSetFlash("System name is required.", 'error');
        header("Location: system-settings.php");
        exit();
    }

    if (!preg_match('/^#[0-9a-fA-F]{6}$/', $brandColor)) {
        $brandColor = '#2563ff';
    }

    if (!empty($_FILES['logo_file']['name']) && is_uploaded_file($_FILES['logo_file']['tmp_name'])) {
        $allowedTypes = [
            'image/png' => 'png',
            'image/jpeg' => 'jpg',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
        ];
        $mimeType = mime_content_type($_FILES['logo_file']['tmp_name']);

        if (isset($allowedTypes[$mimeType])) {
            if (!is_dir(__DIR__ . "/uploads")) {
                mkdir(__DIR__ . "/uploads", 0775, true);
            }
            $logoPath = "uploads/system-logo." . $allowedTypes[$mimeType];
            move_uploaded_file($_FILES['logo_file']['tmp_name'], __DIR__ . "/" . $logoPath);
        } else {
            appSetFlash("Logo must be PNG, JPG, WebP, or GIF.", 'error');
            header("Location: system-settings.php");
            exit();
        }
    }

    if ($logoPath === '') {
        $logoPath = 'logo.png';
    }

    $stmt = $conn->prepare("
        INSERT INTO system_settings(setting_key, setting_value)
        VALUES(?, ?)
        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
    ");

    $updates = [
        'system_name' => $systemName,
        'brand_color' => $brandColor,
        'logo_path' => $logoPath,
        'help_intro' => $helpIntro,
        'faq_content' => $faqContent,
    ];

    foreach ($updates as $key => $value) {
        $stmt->bind_param("ss", $key, $value);
        $stmt->execute();
    }

    appSetFlash("System settings updated.", 'success');
    header("Location: system-settings.php");
    exit();
}

$settings = appGetSystemSettings($conn);

$systemStats = [
    'organizations' => 0,
    'users' => 0,
    'events' => 0,
    'attendance' => 0,
];
$statQueries = [
    'organizations' => "SELECT COUNT(*) AS total FROM organizations",
    'users' => "SELECT COUNT(*) AS total FROM users",
    'events' => "SELECT COUNT(*) AS total FROM events WHERE deleted = FALSE",
    'attendance' => "SELECT COUNT(*) AS total FROM attendance",
];
foreach ($statQueries as $key => $sql) {
    $result = $conn->query($sql);
    $systemStats[$key] = $result ? (int) $result->fetch_assoc()['total'] : 0;
}

$faqBlocks = array_filter(array_map('trim', preg_split("/\R{2,}/", (string) $settings['faq_content'])));

$pageCss = <<<'CSS'
<style>
.settings-grid{
    display:grid;
    grid-template-columns:minmax(0, 1.25fr) minmax(280px, .75fr);
    gap:16px;
}
.panel{
    background:#fff;
    border:1px solid #e2e8f0;
    border-radius:16px;
    padding:18px;
    box-shadow:0 12px 28px rgba(15, 23, 42, 0.06);
}
.panel h2{
    margin:0 0 14px;
    font-size:20px;
}
.form{
    display:grid;
    gap:12px;
}
.form label{
    display:grid;
    gap:6px;
    color:#334155;
    font-size:13px;
    font-weight:800;
}
.form input,.form textarea{
    width:100%;
    border:1px solid #dbe4f0;
    border-radius:12px;
    padding:12px;
    font-size:14px;
    background:#f8fbff;
    color:#0f172a;
}
.form textarea{
    min-height:130px;
    resize:vertical;
}
.form-row{
    display:grid;
    grid-template-columns:1fr 130px;
    gap:12px;
}
.btn{
    border:none;
    border-radius:12px;
    padding:12px 14px;
    background:#2563ff;
    color:#fff;
    font-weight:900;
    cursor:pointer;
}
.preview{
    display:flex;
    align-items:center;
    gap:14px;
    background:#f8fafc;
    border:1px solid #e2e8f0;
    border-radius:14px;
    padding:14px;
    margin-bottom:14px;
}
.preview img{
    width:70px;
    height:70px;
    object-fit:contain;
    border-radius:14px;
    background:#fff;
    border:1px solid #e2e8f0;
}
.preview strong{
    display:block;
    font-size:18px;
}
.preview span{
    display:block;
    margin-top:4px;
    color:#64748b;
    font-size:13px;
}
.status-grid,.metric-grid{
    display:grid;
    gap:10px;
}
.status-item,.metric{
    display:flex;
    justify-content:space-between;
    gap:12px;
    align-items:center;
    background:#f8fafc;
    border:1px solid #e2e8f0;
    border-radius:12px;
    padding:12px;
}
.status-badge{
    border-radius:999px;
    padding:5px 9px;
    background:#dcfce7;
    color:#166534;
    font-size:12px;
    font-weight:900;
}
.metric strong{
    font-size:22px;
    color:#0f172a;
}
.faq-list{
    display:grid;
    gap:10px;
}
.faq-item{
    background:#f8fafc;
    border:1px solid #e2e8f0;
    border-radius:12px;
    padding:12px;
}
.faq-item h3{
    margin:0 0 6px;
    font-size:15px;
}
.faq-item p{
    margin:0;
    color:#64748b;
    line-height:1.5;
    font-size:13px;
}
@media (max-width: 900px){
    .settings-grid{grid-template-columns:1fr;}
    .form-row{grid-template-columns:1fr;}
}
</style>
CSS;

renderAppShellStart($conn, [
    'title' => 'System Settings',
    'active' => 'settings',
    'page_title' => 'System Settings',
    'page_subtitle' => 'Logo, brand, help content, FAQ, and system health live here. Organization creation is managed separately.',
    'extra_head' => $pageCss,
]);
?>

<section class="settings-grid">
    <div class="panel">
        <h2>Brand and Content</h2>
        <div class="preview">
            <img src="<?php echo h($settings['logo_path']); ?>" alt="<?php echo h($settings['system_name']); ?>">
            <div>
                <strong><?php echo h($settings['system_name']); ?></strong>
                <span><?php echo h($settings['logo_path']); ?></span>
            </div>
        </div>

        <form method="POST" enctype="multipart/form-data" class="form">
            <?php echo appCsrfInput(); ?>
            <input type="hidden" name="action" value="update_system">
            <div class="form-row">
                <label>
                    System Name
                    <input type="text" name="system_name" value="<?php echo h($settings['system_name']); ?>" required>
                </label>
                <label>
                    Brand Color
                    <input type="color" name="brand_color" value="<?php echo h($settings['brand_color']); ?>">
                </label>
            </div>
            <label>
                Logo Path or URL
                <input type="text" name="logo_path" value="<?php echo h($settings['logo_path']); ?>" placeholder="logo.png or https://example.com/logo.png">
            </label>
            <label>
                Upload Logo
                <input type="file" name="logo_file" accept="image/png,image/jpeg,image/webp,image/gif">
            </label>
            <label>
                Help Intro
                <textarea name="help_intro"><?php echo h($settings['help_intro']); ?></textarea>
            </label>
            <label>
                FAQ Content
                <textarea name="faq_content" placeholder="Question&#10;Answer&#10;&#10;Question&#10;Answer"><?php echo h($settings['faq_content']); ?></textarea>
            </label>
            <button type="submit" class="btn">Save System Settings</button>
        </form>
    </div>

    <aside class="status-grid">
        <div class="panel">
            <h2>System Status</h2>
            <div class="status-grid">
                <div class="status-item"><strong>Database</strong><span class="status-badge">Connected</span></div>
                <div class="status-item"><strong>Authentication</strong><span class="status-badge">Active</span></div>
                <div class="status-item"><strong>QR Generation</strong><span class="status-badge">Ready</span></div>
            </div>
        </div>

        <div class="panel">
            <h2>System Metrics</h2>
            <div class="metric-grid">
                <div class="metric"><span>Organizations</span><strong><?php echo $systemStats['organizations']; ?></strong></div>
                <div class="metric"><span>Users</span><strong><?php echo $systemStats['users']; ?></strong></div>
                <div class="metric"><span>Events</span><strong><?php echo $systemStats['events']; ?></strong></div>
                <div class="metric"><span>Attendance Records</span><strong><?php echo $systemStats['attendance']; ?></strong></div>
            </div>
        </div>
    </aside>
</section>

<section class="panel" style="margin-top:16px;">
    <h2>Help and FAQ Preview</h2>
    <p style="margin-top:0;color:#64748b;line-height:1.55;"><?php echo h($settings['help_intro']); ?></p>
    <div class="faq-list">
        <?php foreach ($faqBlocks as $block): ?>
            <?php
            $lines = array_values(array_filter(array_map('trim', preg_split("/\R/", $block))));
            $question = $lines[0] ?? 'FAQ';
            $answer = implode(' ', array_slice($lines, 1));
            ?>
            <article class="faq-item">
                <h3><?php echo h($question); ?></h3>
                <p><?php echo h($answer); ?></p>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<?php renderAppShellEnd('settings'); ?>
