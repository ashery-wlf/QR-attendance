<?php
include("includes/auth.php");
include("includes/db.php");
include("includes/app.php");

ensureEventSchema($conn);
ensureSystemSettingsSchema($conn);
appRequireRole(['super_admin']);

$user_id = (int) $_SESSION['user_id'];
$org_id = isset($_GET['org_id']) ? (int) $_GET['org_id'] : 0;
$message = "";
$message_type = "";
$system_settings = appGetSystemSettings($conn);
$system_default_logo = trim((string) ($system_settings['logo_path'] ?? 'logo.png'));
if ($system_default_logo === '') {
    $system_default_logo = 'logo.png';
}

function deleteOrganizationBrandingLogo($logo_path)
{
    $logo_path = str_replace('\\', '/', trim((string) $logo_path));
    if ($logo_path === '' || strpos($logo_path, 'uploads/org_branding/') !== 0) {
        return;
    }

    $full_path = __DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $logo_path);
    if (is_file($full_path)) {
        @unlink($full_path);
    }
}

// Get all organizations
$organizations_result = $conn->query("SELECT id, name, logo, brand_color, background_color FROM organizations ORDER BY name ASC");
$organizations = [];
if ($organizations_result) {
    while ($row = $organizations_result->fetch_assoc()) {
        $organizations[] = $row;
    }
}

// If organization selected, get its details
$organization = null;
if ($org_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM organizations WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $org_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $organization = $result->fetch_assoc();
    $stmt->close();
    
    if (!$organization) {
        $message = "Organization not found.";
        $message_type = "error";
        $org_id = 0;
    }
}

// Handle branding update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_branding') {
    if (!appVerifyCsrf()) {
        $message = "Security check failed.";
        $message_type = "error";
    } else if ($org_id <= 0) {
        $message = "Please select an organization.";
        $message_type = "error";
    } else {
        $brand_color = trim($_POST['brand_color'] ?? '#2563ff');
        $background_color = trim($_POST['background_color'] ?? '#ffffff');
        
        // Validate colors
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $brand_color)) {
            $brand_color = '#2563ff';
        }
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $background_color)) {
            $background_color = '#ffffff';
        }
        
        $logo_path = $organization['logo'];
        $use_default_logo = isset($_POST['use_default_logo']) && $_POST['use_default_logo'] === '1';
        
        if ($use_default_logo) {
            deleteOrganizationBrandingLogo($organization['logo']);
            $logo_path = $system_default_logo;
            $message = "Organization logo removed. System default logo will be used.";
            $message_type = "success";
        } else if (!empty($_FILES['logo_file']['name']) && is_uploaded_file($_FILES['logo_file']['tmp_name'])) {
            $allowedTypes = [
                'image/png' => 'png',
                'image/jpeg' => 'jpg',
                'image/webp' => 'webp',
                'image/gif' => 'gif',
            ];
            
            $mimeType = mime_content_type($_FILES['logo_file']['tmp_name']);
            
            if (isset($allowedTypes[$mimeType])) {
                $upload_dir = "uploads/org_branding/";
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0775, true);
                }
                
                // Delete old logo if exists
                deleteOrganizationBrandingLogo($organization['logo']);
                
                $logo_filename = "org_" . $org_id . "_logo_" . time() . "." . $allowedTypes[$mimeType];
                $logo_path = $upload_dir . $logo_filename;
                
                if (move_uploaded_file($_FILES['logo_file']['tmp_name'], $logo_path)) {
                    $message = "Logo uploaded successfully.";
                    $message_type = "success";
                } else {
                    $message = "Failed to upload logo. Please try again.";
                    $message_type = "error";
                }
            } else {
                $message = "Invalid file type. Only PNG, JPG, WebP, and GIF are allowed.";
                $message_type = "error";
            }
        }
        
        // Update organization branding
        $stmt = $conn->prepare("
            UPDATE organizations 
            SET brand_color = ?, background_color = ?, logo = ? 
            WHERE id = ?
        ");
        $stmt->bind_param("sssi", $brand_color, $background_color, $logo_path, $org_id);
        
        if ($stmt->execute()) {
            if (empty($message)) {
                $message = "Branding settings updated successfully!";
                $message_type = "success";
            }
            
            // Refresh organization data
            $stmt2 = $conn->prepare("SELECT * FROM organizations WHERE id = ? LIMIT 1");
            $stmt2->bind_param("i", $org_id);
            $stmt2->execute();
            $organization = $stmt2->get_result()->fetch_assoc();
            $stmt2->close();
        } else {
            $message = "Failed to update branding settings. Please try again.";
            $message_type = "error";
        }
        
        $stmt->close();
    }
}

$current_brand_color = $organization['brand_color'] ?? '#2563ff';
$current_bg_color = $organization['background_color'] ?? '#ffffff';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organization Branding - QR Attendance System</title>
    <link rel="stylesheet" href="includes/style.css">
    <style>
        :root {
            --primary: <?php echo htmlspecialchars($current_brand_color); ?>;
            --bg: <?php echo htmlspecialchars($current_bg_color); ?>;
        }
        
        body {
            background: linear-gradient(135deg, var(--primary) 0%, var(--bg) 100%);
            min-height: 100vh;
            margin: 0;
            padding: 20px;
            font-family: 'Inter', sans-serif;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            padding: 40px;
        }
        
        .header {
            margin-bottom: 30px;
        }
        
        h1 {
            color: var(--primary);
            margin: 0 0 10px 0;
            font-size: 2em;
        }
        
        .breadcrumb {
            color: #666;
            font-size: 0.9em;
            margin-bottom: 20px;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: none;
        }
        
        .alert.show {
            display: block;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .content-grid {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .org-list {
            background: #f9f9f9;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
            padding: 0;
            height: fit-content;
            max-height: 600px;
            overflow-y: auto;
        }
        
        .org-list-title {
            padding: 15px 20px;
            font-weight: 600;
            color: var(--primary);
            border-bottom: 2px solid var(--primary);
            background: #f5f5f5;
            position: sticky;
            top: 0;
        }
        
        .org-item {
            padding: 15px 20px;
            border-bottom: 1px solid #e0e0e0;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: #333;
        }
        
        .org-item:hover {
            background: #f0f0f0;
        }
        
        .org-item.active {
            background: var(--primary);
            color: white;
            border-bottom-color: var(--primary);
        }
        
        .org-item-color {
            width: 20px;
            height: 20px;
            border-radius: 4px;
            border: 1px solid #ddd;
            flex-shrink: 0;
        }
        
        .org-item-name {
            flex: 1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .form-section {
            margin-bottom: 30px;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 8px;
            border-left: 4px solid var(--primary);
        }
        
        .form-section h2,
        .form-section h3 {
            margin-top: 0;
            color: var(--primary);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            font-size: 0.95em;
        }
        
        input[type="text"],
        input[type="color"],
        input[type="file"],
        select,
        textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-family: inherit;
            font-size: 1em;
            box-sizing: border-box;
            transition: border-color 0.3s;
        }
        
        input[type="text"]:focus,
        input[type="color"]:focus,
        input[type="file"]:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        
        input[type="color"] {
            height: 50px;
            padding: 6px;
            cursor: pointer;
        }
        
        .color-preview {
            display: flex;
            gap: 15px;
            margin-top: 10px;
            flex-wrap: wrap;
        }
        
        .color-box {
            width: 100px;
            height: 100px;
            border-radius: 8px;
            border: 2px solid #ddd;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8em;
            font-weight: 600;
            color: #666;
        }
        
        .logo-preview {
            margin-top: 15px;
            padding: 20px;
            background: #f0f0f0;
            border-radius: 8px;
            text-align: center;
        }
        
        .logo-preview img {
            max-width: 150px;
            max-height: 150px;
            border-radius: 6px;
        }

        .logo-actions {
            margin-top: 14px;
            padding: 14px;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
        }

        .checkbox-row {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin: 0;
            font-weight: 600;
            color: #333;
        }

        .checkbox-row input {
            width: auto;
            margin-top: 3px;
        }

        .logo-source {
            display: inline-block;
            margin-top: 10px;
            padding: 5px 10px;
            border-radius: 999px;
            background: rgba(37, 99, 235, 0.1);
            color: var(--primary);
            font-size: 0.82em;
            font-weight: 700;
        }
        
        .file-input-label {
            display: inline-block;
            padding: 10px 20px;
            background: var(--primary);
            color: white;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: opacity 0.3s;
        }
        
        .file-input-label:hover {
            opacity: 0.9;
        }
        
        input[type="file"] {
            display: none;
        }
        
        .file-name {
            margin-top: 10px;
            font-size: 0.9em;
            color: #666;
        }
        
        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 30px;
            justify-content: flex-end;
        }
        
        button {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            font-size: 1em;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: var(--primary);
            color: white;
        }
        
        .btn-primary:hover {
            opacity: 0.9;
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(37, 99, 235, 0.3);
        }
        
        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        .help-text {
            font-size: 0.85em;
            color: #666;
            margin-top: 5px;
        }
        
        .input-group {
            display: flex;
            gap: 10px;
            align-items: flex-end;
        }
        
        .input-group input {
            flex: 1;
        }
        
        .back-link {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            margin-bottom: 20px;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        .no-org-selected {
            padding: 60px 20px;
            text-align: center;
            color: #999;
        }
        
        .no-org-selected svg {
            width: 64px;
            height: 64px;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        .org-info {
            padding: 15px 0;
            border-bottom: 1px solid #e0e0e0;
            margin-bottom: 20px;
        }
        
        .org-info-name {
            font-size: 1.2em;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 5px;
        }
        
        .org-info-meta {
            font-size: 0.85em;
            color: #666;
        }
        
        @media (max-width: 768px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
            
            .org-list {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
                max-height: none;
                padding: 10px;
                gap: 8px;
            }
            
            .org-item {
                padding: 10px;
                border: 1px solid #e0e0e0;
                border-radius: 6px;
                margin-bottom: 0;
                border-bottom: 1px solid #e0e0e0;
                flex-direction: column;
                text-align: center;
                justify-content: center;
            }
            
            .org-item.active {
                border: 2px solid var(--primary);
            }
            
            .org-item-name {
                font-size: 0.8em;
                white-space: normal;
            }
            
            .container {
                padding: 20px;
            }
            
            h1 {
                font-size: 1.5em;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="dashboard.php" class="back-link">← Back to Dashboard</a>
        
        <div class="header">
            <h1>Organization Branding Management</h1>
            <div class="breadcrumb">
                Dashboard / System Settings / Organization Branding
            </div>
        </div>
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo htmlspecialchars($message_type); ?> show">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <div class="content-grid">
            <!-- Organization List -->
            <div class="org-list">
                <div class="org-list-title">Organizations</div>
                <?php foreach ($organizations as $org): ?>
                    <a href="?org_id=<?php echo $org['id']; ?>" class="org-item <?php echo $org_id === (int)$org['id'] ? 'active' : ''; ?>">
                        <div class="org-item-color" style="background-color: <?php echo htmlspecialchars($org['brand_color'] ?: '#2563ff'); ?>;"></div>
                        <div class="org-item-name"><?php echo htmlspecialchars($org['name']); ?></div>
                    </a>
                <?php endforeach; ?>
            </div>
            
            <!-- Branding Editor -->
            <div>
                <?php if ($organization): ?>
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="update_branding">
                        <input type="hidden" name="csrf_token" value="<?php echo appGenerateCsrfToken(); ?>">
                        
                        <!-- Organization Title -->
                        <div class="org-info">
                            <div class="org-info-name">
                                <?php echo htmlspecialchars($organization['name']); ?>
                            </div>
                            <div class="org-info-meta">
                                ID: <?php echo $organization['id']; ?> • Created: <?php echo date('M d, Y', strtotime($organization['created_at'])); ?>
                            </div>
                        </div>
                        
                        <!-- Logo Section -->
                        <div class="form-section">
                            <h3>Organization Logo</h3>
                            
                            <div class="form-group">
                                <label for="logo_file">Upload Logo (PNG, JPG, WebP, GIF)</label>
                                <label for="logo_file" class="file-input-label">Choose File</label>
                                <input type="file" id="logo_file" name="logo_file" accept="image/png,image/jpeg,image/webp,image/gif">
                                <div class="file-name" id="fileName">No file chosen</div>
                                <p class="help-text">Recommended size: 200x200px. Max file size: 5MB</p>
                            </div>
                            
                            <div class="logo-actions">
                                <label class="checkbox-row" for="use_default_logo">
                                    <input type="checkbox" id="use_default_logo" name="use_default_logo" value="1">
                                    <span>Remove organization logo and use the system default logo</span>
                                </label>
                                <p class="help-text">This will replace this organization's custom logo with the default system logo.</p>
                            </div>

                            <?php
                                $raw_org_logo = trim((string) ($organization['logo'] ?? ''));
                                $preview_logo = $raw_org_logo !== '' ? $raw_org_logo : $system_default_logo;
                                $is_system_default_logo = $raw_org_logo === '' || $raw_org_logo === $system_default_logo;
                            ?>
                            <?php if (!empty($preview_logo) && file_exists($preview_logo)): ?>
                                <div class="logo-preview">
                                    <p style="margin: 0 0 10px 0; color: #666;">Current Logo:</p>
                                    <img src="<?php echo htmlspecialchars($preview_logo); ?>" alt="Organization Logo">
                                    <p style="margin: 10px 0 0 0; color: var(--primary); font-weight: 600;">
                                        <?php echo htmlspecialchars($organization['name']); ?>
                                    </p>
                                    <span class="logo-source">
                                        <?php echo $is_system_default_logo ? 'Using system default logo' : 'Using organization logo'; ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Color Section -->
                        <div class="form-section">
                            <h3>Brand Colors</h3>
                            
                            <div class="form-group">
                                <label for="brand_color">Primary Brand Color</label>
                                <div class="input-group">
                                    <input type="text" id="brand_color_text" name="brand_color" value="<?php echo htmlspecialchars($current_brand_color); ?>" placeholder="#2563ff" readonly>
                                    <input type="color" id="brand_color" value="<?php echo htmlspecialchars($current_brand_color); ?>" style="width: 60px;">
                                </div>
                                <p class="help-text">Used for buttons, links, and primary UI elements</p>
                            </div>
                            
                            <div class="form-group">
                                <label for="background_color">Background Color</label>
                                <div class="input-group">
                                    <input type="text" id="background_color_text" name="background_color" value="<?php echo htmlspecialchars($current_bg_color); ?>" placeholder="#ffffff" readonly>
                                    <input type="color" id="background_color" value="<?php echo htmlspecialchars($current_bg_color); ?>" style="width: 60px;">
                                </div>
                                <p class="help-text">Used for page backgrounds and neutral areas</p>
                            </div>
                            
                            <div class="color-preview">
                                <div class="color-box" id="brandColorPreview" style="background-color: <?php echo htmlspecialchars($current_brand_color); ?>;">
                                    <span style="color: white;">Brand</span>
                                </div>
                                <div class="color-box" id="bgColorPreview" style="background-color: <?php echo htmlspecialchars($current_bg_color); ?>;">
                                    <span>Background</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Buttons -->
                        <div class="button-group">
                            <button type="submit" class="btn-primary">Save Branding Settings</button>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="no-org-selected">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                            <polyline points="17 21 17 13 7 13 7 21"></polyline>
                            <polyline points="7 3 7 8 15 8"></polyline>
                        </svg>
                        <p><strong>Select an organization</strong> from the list to manage its branding settings</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        const brandColor = document.getElementById('brand_color');
        const backgroundColor = document.getElementById('background_color');
        const logoFile = document.getElementById('logo_file');
        const useDefaultLogo = document.getElementById('use_default_logo');

        if (brandColor) {
            brandColor.addEventListener('change', function() {
                document.getElementById('brand_color_text').value = this.value;
                document.getElementById('brandColorPreview').style.backgroundColor = this.value;
                document.documentElement.style.setProperty('--primary', this.value);
            });

            brandColor.addEventListener('input', function() {
                document.getElementById('brand_color_text').value = this.value;
            });
        }

        if (backgroundColor) {
            backgroundColor.addEventListener('change', function() {
                document.getElementById('background_color_text').value = this.value;
                document.getElementById('bgColorPreview').style.backgroundColor = this.value;
                document.documentElement.style.setProperty('--bg', this.value);
            });

            backgroundColor.addEventListener('input', function() {
                document.getElementById('background_color_text').value = this.value;
            });
        }

        if (logoFile) {
            logoFile.addEventListener('change', function(e) {
                const fileName = e.target.files[0]?.name || 'No file chosen';
                document.getElementById('fileName').textContent = fileName;
                if (e.target.files.length > 0 && useDefaultLogo) {
                    useDefaultLogo.checked = false;
                }
            });
        }

        if (useDefaultLogo && logoFile) {
            useDefaultLogo.addEventListener('change', function() {
                logoFile.disabled = this.checked;
                if (this.checked) {
                    logoFile.value = '';
                    document.getElementById('fileName').textContent = 'System default logo selected';
                } else {
                    document.getElementById('fileName').textContent = 'No file chosen';
                }
            });
        }
    </script>
</body>
</html>
