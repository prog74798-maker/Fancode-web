<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: /api/login.php");
    exit();
}

include 'config.php';

// Check if portal is configured
if (empty($host)) {
    header("Location: /api/config.php");
    exit();
}

$filter_file = $directories["filter"] . "/$host.json";

// Initialize stored data
$stored_data = [];
if (file_exists($filter_file)) {
    $stored_data = json_decode(file_get_contents($filter_file), true);
    if ($stored_data === null) {
        $stored_data = [];
    }
}

$show_popup = false;
$popup_message = '';
$popup_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['group'])) {
        $selected = $_POST['group'];
        $all_groups = group_title(true);

        if (empty($all_groups)) {
            $show_popup = true;
            $popup_message = 'Error: No groups found. Please check your portal configuration.';
            $popup_type = 'error';
        } else {
            foreach ($all_groups as $id => $title) {
                $stored_data[$id] = [
                    'id' => $id,
                    'title' => $title,
                    'filter' => in_array($id, $selected)
                ];
            }

            $result = file_put_contents($filter_file, json_encode($stored_data, JSON_PRETTY_PRINT));

            $show_popup = true;
            if ($result === false) {
                $popup_message = 'Error: Unable to save settings.';
                $popup_type = 'error';
            } else {
                // Delete cached playlist to force regeneration
                $playlist_file = $directories["playlist"] . "/{$host}.m3u";
                if (file_exists($playlist_file)) {
                    unlink($playlist_file);
                }
                $popup_message = 'Settings saved successfully! Playlist will be regenerated.';
                $popup_type = 'success';
            }
        }
    }
}

// Get groups with error handling
try {
    $groups = group_title(true);
    if (empty($groups)) {
        throw new Exception("No groups available from portal");
    }
} catch (Exception $e) {
    $groups = [];
    $show_popup = true;
    $popup_message = 'Error: Unable to fetch groups from portal. Please check your configuration.';
    $popup_type = 'error';
}

// Generate playlist URL
$currentUrl = "https://" . $_SERVER['HTTP_HOST'] . "/api/playlist.php";
$playlistUrl = $currentUrl;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <title>Group Filter - mac2m3u</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            font-family: 'Segoe UI', Arial, sans-serif;
            background: linear-gradient(135deg, #1a1a2e, #16213e);
            display: flex;
            justify-content: center;
            align-items: center;           
            margin: 0;
            padding: 20px;
            color: #e0e0e0;
        }

        .container {
            background: rgba(34, 40, 49, 0.95);
            border-radius: 20px;
            padding: 30px;
            width: 100%;
            max-width: 500px;
            text-align: center;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        h2 {
            color: #00d4ff;
            text-shadow: 0 0 10px rgba(0, 212, 255, 0.5);
            margin: 0 0 25px 0;
        }

        .checkbox-container {
            max-height: 350px;
            overflow-y: auto;
            padding: 15px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            margin-bottom: 20px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .form-group {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin: 12px 0;
            padding: 12px;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.03);
            transition: all 0.3s ease;
        }

        .form-group:hover {
            background: rgba(255, 255, 255, 0.08);
            transform: translateY(-1px);
        }

        .form-group label {
            flex: 1;
            text-align: left;
            font-weight: 500;
            color: #e0e0e0;
            padding-left: 10px;
            cursor: pointer;
            margin: 0;
        }

        .form-group input[type="checkbox"] {
            transform: scale(1.3);
            cursor: pointer;
            margin-right: 10px;
            accent-color: #00d4ff;
        }

        button.save-btn {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            background: linear-gradient(45deg, #0077b6, #023e8a);
            color: white;
            margin-bottom: 20px;
        }

        button.save-btn:hover:not(:disabled) {
            background: linear-gradient(45deg, #0096c7, #0353a4);
            box-shadow: 0 6px 20px rgba(0, 150, 199, 0.4);
            transform: translateY(-2px);
        }

        button.save-btn:disabled {
            background: #6c757d;
            cursor: not-allowed;
            transform: none;
        }

        .btn {
            padding: 10px 15px;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.1);
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            color: #e0e0e0;
            text-decoration: none;
            font-weight: 600;
        }

        .btn:hover {
            background: rgba(0, 212, 255, 0.2);
            border-color: #00d4ff;
            box-shadow: 0 0 10px rgba(0, 212, 255, 0.3);
        }

        .btn i {
            margin-right: 8px;
        }

        .popup {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(34, 40, 49, 0.98);
            padding: 25px 30px;
            border-radius: 15px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.6);
            z-index: 1000;
            display: none;
            width: 90%;
            max-width: 400px;
            text-align: center;
            border: 2px solid;
        }

        .popup.success {
            border-color: #28a745;
        }

        .popup.error {
            border-color: #dc3545;
        }

        .popup button {
            padding: 10px 25px;
            margin: 15px auto 0;
            display: block;
            background: linear-gradient(45deg, #0077b6, #023e8a);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .popup button:hover {
            background: linear-gradient(45deg, #0096c7, #0353a4);
            transform: translateY(-2px);
        }

        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            display: none;
            z-index: 999;
        }

        .search-container {
            margin-bottom: 20px;
            position: relative;
        }

        .search-input {
            width: 100%;
            padding: 12px 45px 12px 15px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.08);
            color: #e0e0e0;
            font-size: 14px;
        }

        .search-input:focus {
            outline: none;
            border-color: #00d4ff;
            box-shadow: 0 0 8px rgba(0, 212, 255, 0.4);
        }

        .search-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #a0a0a0;
        }

        .playlist-container {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 20px 0;
            background: rgba(255, 255, 255, 0.05);
            padding: 15px;
            border-radius: 10px;
        }

        .playlist-container label {
            font-weight: 600;
            color: #00d4ff;
            white-space: nowrap;
        }

        .playlist-container input {
            flex: 1;
            padding: 10px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.05);
            color: #e0e0e0;
            font-size: 14px;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .action-buttons .btn {
            padding: 10px;
            width: 40px;
            height: 40px;
        }

        .nav-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .nav-buttons .btn {
            flex: 1;
            justify-content: center;
        }

        .footer {
            text-align: center;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            font-size: 16px;
            color: #a0a0a0;
        }

        .no-groups {
            text-align: center;
            padding: 40px 20px;
            color: #a0a0a0;
        }

        .no-groups i {
            font-size: 48px;
            margin-bottom: 15px;
            color: #6c757d;
        }

        @media (max-width: 480px) {
            .container {
                padding: 20px;
            }

            h2 {
                font-size: 1.4em;
            }

            .playlist-container {
                flex-direction: column;
                align-items: stretch;
            }

            .action-buttons {
                justify-content: center;
            }

            .nav-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>📺 Group Filter</h2>
        
        <?php if (empty($groups)): ?>
            <div class="no-groups">
                <i class="fas fa-exclamation-triangle"></i>
                <h3>No Groups Available</h3>
                <p>Unable to fetch channel groups from the portal.</p>
                <p>Please check your configuration and try again.</p>
                <a href="/api/config.php" class="btn" style="margin-top: 15px;">
                    <i class="fas fa-cog"></i> Configure Portal
                </a>
            </div>
        <?php else: ?>
            <form method="post">
                <div class="search-container">
                    <input type="text" id="groupSearch" class="search-input" placeholder="🔍 Search groups...">
                    <div class="search-icon"></div>
                </div>
                
                <div class="checkbox-container">
                    <div class="form-group">
                        <label>
                            <input type="checkbox" id="checkAll" onclick="toggleCheckboxes(this)"> 
                            <strong>Select All Groups</strong>
                        </label>
                    </div>
                    <?php foreach ($groups as $id => $title): ?>
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="group[]" value="<?= htmlspecialchars($id) ?>" 
                                    <?= (!empty($stored_data[$id]['filter']) || !isset($stored_data[$id])) ? 'checked' : '' ?> 
                                    onchange="updateCheckAll()">
                                <?= htmlspecialchars($title) ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <button type="submit" class="save-btn" id="saveBtn">
                    <i class="fas fa-save"></i> Save Filter Settings
                </button>
            </form>
        <?php endif; ?>

        <div class="playlist-container">
            <label>Playlist URL:</label>
            <input type="text" id="playlist_url" value="<?= htmlspecialchars($playlistUrl) ?>" readonly>
            <div class="action-buttons">                    
                <button class="btn" onclick="copyToClipboard()" title="Copy URL">
                    <i class="fas fa-copy"></i>
                </button>
            </div>
        </div>

        <div class="nav-buttons">
            <a href="/api/config.php" class="btn">
                <i class="fas fa-cog"></i> Configuration
            </a>
            <a href="/api/index.php" class="btn">
                <i class="fas fa-home"></i> Home
            </a>
            <a href="/api/logout.php" class="btn">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>

        <div class="footer">
            <strong>Coded with ❤️ by RKDYIPTV</strong>
        </div>
    </div>

    <!-- Popup and Overlay -->
    <div id="overlay" class="overlay" onclick="hidePopup()"></div>
    <div id="popup" class="popup <?= $popup_type ?>">
        <p id="popup-message"><?= htmlspecialchars($popup_message) ?></p>
        <div id="popup-buttons">
            <button onclick="hidePopup()">OK</button>
        </div>
    </div>

    <script>
        function toggleCheckboxes(source) {
            let checkboxes = document.querySelectorAll('.form-group:not([style*="display: none"]) input[name="group[]"]');
            checkboxes.forEach(checkbox => {
                checkbox.checked = source.checked;
            });
            updateSaveButton();
        }

        function updateCheckAll() {
            let visibleCheckboxes = document.querySelectorAll('.form-group:not([style*="display: none"]) input[name="group[]"]');
            let checkAllBox = document.getElementById('checkAll');
            let checkedCount = Array.from(visibleCheckboxes).filter(cb => cb.checked).length;
            
            if (visibleCheckboxes.length === 0) {
                checkAllBox.checked = false;
                checkAllBox.indeterminate = false;
            } else if (checkedCount === visibleCheckboxes.length) {
                checkAllBox.checked = true;
                checkAllBox.indeterminate = false;
            } else if (checkedCount > 0) {
                checkAllBox.checked = false;
                checkAllBox.indeterminate = true;
            } else {
                checkAllBox.checked = false;
                checkAllBox.indeterminate = false;
            }
            updateSaveButton();
        }

        function filterGroups() {
            let input = document.getElementById('groupSearch').value.toLowerCase();
            let groups = document.getElementsByClassName('form-group');
            let visibleCount = 0;

            for (let group of groups) {
                if (group.querySelector('#checkAll')) continue;

                let label = group.querySelector('label');
                let text = label.textContent.toLowerCase().trim();

                if (text.includes(input)) {
                    group.style.display = '';
                    visibleCount++;
                } else {
                    group.style.display = 'none';
                }
            }
            updateCheckAll();
        }

        function updateSaveButton() {
            const saveBtn = document.getElementById('saveBtn');
            const checkedCount = document.querySelectorAll('input[name="group[]"]:checked').length;
            
            if (checkedCount === 0) {
                saveBtn.disabled = true;
                saveBtn.innerHTML = '<i class="fas fa-exclamation-circle"></i> Select at least one group';
            } else {
                saveBtn.disabled = false;
                saveBtn.innerHTML = `<i class="fas fa-save"></i> Save (${checkedCount} groups selected)`;
            }
        }

        function copyToClipboard() {
            const copyText = document.getElementById("playlist_url");
            copyText.select();
            copyText.setSelectionRange(0, 99999);
            
            try {
                navigator.clipboard.writeText(copyText.value).then(() => {
                    showPopup('URL copied to clipboard!', 'success');
                });
            } catch (err) {
                document.execCommand("copy");
                showPopup('URL copied to clipboard!', 'success');
            }
        }

        function showPopup(message, type = 'success') {
            const popup = document.getElementById('popup');
            const messageEl = document.getElementById('popup-message');
            const overlay = document.getElementById('overlay');
            
            popup.className = `popup ${type}`;
            messageEl.textContent = message;
            popup.style.display = 'block';
            overlay.style.display = 'block';
        }

        function hidePopup() {
            document.getElementById('popup').style.display = 'none';
            document.getElementById('overlay').style.display = 'none';
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', function () {
            document.getElementById('groupSearch').addEventListener('input', filterGroups);
            
            const checkboxes = document.querySelectorAll('input[name="group[]"]');
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', updateCheckAll);
            });

            updateCheckAll();
            updateSaveButton();
            
            <?php if ($show_popup): ?>
                showPopup('<?= addslashes($popup_message) ?>', '<?= $popup_type ?>');
            <?php endif; ?>

            if (window.history.replaceState) {
                window.history.replaceState(null, null, window.location.href);
            }
        });
    </script>
</body>
</html>
