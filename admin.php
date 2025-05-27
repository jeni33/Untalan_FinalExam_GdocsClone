<?php
require_once 'core/dbConfig.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    die("Access denied. Admins only.");
}

$users = $pdo->query("SELECT * FROM users WHERE role = 'user'")->fetchAll();

$documents = $pdo->query("
    SELECT d.*, u.name AS owner FROM documents d
    JOIN users u ON d.owner_id = u.id
")->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="css/style.css" />

    <script>
    function toggleSuspend(userId, checkbox) {
        const xhr = new XMLHttpRequest();
        xhr.open("POST", "core/handleForms.php");
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        xhr.send("action=toggle_suspend&user_id=" + userId + "&status=" + (checkbox.checked ? 1 : 0));
    }
    </script>
</head>
<body>
    <h1>Admin Dashboard</h1>

    <h2>User Accounts</h2>
    <table border="1">
        <tr><th>Name</th><th>Suspended</th></tr>
        <?php foreach ($users as $user): ?>
            <tr>
                <td><?= htmlspecialchars($user['name']) ?></td>
                <td>
                    <input type="checkbox" <?= $user['is_suspended'] ? 'checked' : '' ?>
                           onchange="toggleSuspend(<?= $user['id'] ?>, this)">
                </td>
            </tr>
        <?php endforeach; ?>
    </table>

    <h2>All Documents</h2>
    <ul>
        <?php foreach ($documents as $doc): ?>
            <li>
                <?= htmlspecialchars($doc['title']) ?> by <?= htmlspecialchars($doc['owner']) ?>
                - <a href="admindocument.php?id=<?= $doc['id'] ?>">View</a>
            </li>
        <?php endforeach; ?>
    </ul>
</body>
</html>