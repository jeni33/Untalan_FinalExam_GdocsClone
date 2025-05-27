<?php
session_start(); // Required for $_SESSION to work

require_once 'dbConfig.php';

// LOGIN HANDLING
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'login') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];

        header("Location: ../index.php");
        exit;
    } else {
        die("Invalid login credentials.");
    }
}

// BLOCK UNAUTHENTICATED USERS
if (!isset($_SESSION['user_id'])) {
    die("Access denied. Please login first.");
}

$userId = $_SESSION['user_id'];
$docId = $_GET['id'] ?? $_POST['doc_id'] ?? null;
$action = $_POST['action'] ?? '';
$doc = null;

// FETCH DOCUMENT IF NEEDED
if ($docId) {
    $docQuery = $pdo->prepare("
        SELECT d.*, u.name AS owner_name
        FROM documents d
        JOIN users u ON d.owner_id = u.id
        WHERE d.id = ? AND (d.owner_id = ? OR EXISTS (
            SELECT 1 FROM document_access da WHERE da.document_id = d.id AND da.user_id = ?
        ))
    ");
    $docQuery->execute([$docId, $userId, $userId]);
    $doc = $docQuery->fetch();

    if (!$doc) {
        die("You do not have access to this document.");
    }
}

// HANDLE POST ACTIONS
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($action) {
        case 'toggle_suspend':
            $targetUserId = $_POST['user_id'];
            $status = $_POST['status'] ?? 0;
            $stmt = $pdo->prepare("UPDATE users SET is_suspended = ? WHERE id = ?");
            $stmt->execute([$status, $targetUserId]);
            exit;

        case 'create_document':
            $title = $_POST['title'] ?? '';
            $content = $_POST['content'] ?? '';
            if (empty($title)) {
                die("Title is required.");
            }

            $stmt = $pdo->prepare("INSERT INTO documents (title, content, owner_id) VALUES (?, ?, ?)");
            $stmt->execute([$title, $content, $userId]);

            header("Location: ../index.php");
            exit;

        case 'admin_save_doc':
            $docId = $_POST['doc_id'];
            $content = $_POST['content'];
            $stmt = $pdo->prepare("UPDATE documents SET content = ? WHERE id = ?");
            $stmt->execute([$content, $docId]);

            $log = $pdo->prepare("INSERT INTO activity_logs (document_id, user_id, action) VALUES (?, ?, ?)");
            $log->execute([$docId, $userId, 'Admin updated document manually']);

            header("Location: ../admindocument.php?id=$docId");
            exit;

        case 'autosave':
            $docId = $_POST['doc_id'] ?? '';
            $content = $_POST['content'] ?? '';
            if (!empty($docId)) {
                $stmt = $pdo->prepare("UPDATE documents SET content = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$content, $docId]);

                $log = $pdo->prepare("INSERT INTO activity_logs (document_id, user_id, action) VALUES (?, ?, ?)");
                $log->execute([$docId, $userId, "Auto-saved content"]);

                echo "Saved";
            }
            exit;
    }

    // Handle chat message
    if (isset($_POST['message']) && $docId && $doc) {
        $msg = trim($_POST['message']);
        if (!empty($msg)) {
            $insertMsg = $pdo->prepare("INSERT INTO messages (document_id, user_id, message) VALUES (?, ?, ?)");
            $insertMsg->execute([$docId, $userId, $msg]);
            header("Location: userdocument.php?id=$docId");
            exit;
        }
    }

    // Handle manual save (not autosave)
    if (isset($_POST['content']) && !isset($_POST['autosave']) && $docId && $doc) {
        $newContent = $_POST['content'];

        $checkEdit = $pdo->prepare("SELECT 1 FROM document_access WHERE document_id = ? AND user_id = ?");
        $checkEdit->execute([$docId, $userId]);
        $hasAccess = $checkEdit->fetch() || $doc['owner_id'] == $userId;

        if ($hasAccess) {
            $updateDoc = $pdo->prepare("UPDATE documents SET content = ? WHERE id = ?");
            $updateDoc->execute([$newContent, $docId]);

            $logEdit = $pdo->prepare("INSERT INTO activity_logs (document_id, user_id, action) VALUES (?, ?, 'edited')");
            $logEdit->execute([$docId, $userId]);

            header("Location: userdocument.php?id=$docId");
            exit;
        } else {
            die("You do not have permission to edit this document.");
        }
    }

    // Handle sharing
    if (isset($_POST['share_user_id']) && $docId && $doc) {
        $shareTo = $_POST['share_user_id'];

        $check = $pdo->prepare("SELECT 1 FROM document_access WHERE document_id = ? AND user_id = ?");
        $check->execute([$docId, $shareTo]);
        if (!$check->fetch()) {
            $addAccess = $pdo->prepare("INSERT INTO document_access (document_id, user_id) VALUES (?, ?)");
            $addAccess->execute([$docId, $shareTo]);

            $logShare = $pdo->prepare("INSERT INTO activity_logs (document_id, user_id, action) VALUES (?, ?, ?)");
            $logShare->execute([$docId, $userId, "shared with user $shareTo"]);
        }

        header("Location: ../userdocument.php?id=$docId");
        exit;
    }
}

// If document is loaded, log view and prepare messages and logs
if ($docId && $doc) {
    $logView = $pdo->prepare("INSERT INTO activity_logs (document_id, user_id, action) VALUES (?, ?, 'viewed')");
    $logView->execute([$docId, $userId]);

    $msgs = $pdo->prepare("
        SELECT m.*, u.name AS sender 
        FROM messages m 
        JOIN users u ON u.id = m.user_id
        WHERE m.document_id = ?
        ORDER BY m.timestamp DESC
    ");
    $msgs->execute([$docId]);

    $logs = $pdo->prepare("
        SELECT a.*, u.name AS actor 
        FROM activity_logs a 
        JOIN users u ON u.id = a.user_id
        WHERE a.document_id = ?
        ORDER BY a.timestamp DESC
    ");
    $logs->execute([$docId]);

    $users = $pdo->query("SELECT id, name FROM users WHERE role = 'user'");
}
?>
