<?php

require_once 'core/dbConfig.php';

if (!isset($_SESSION['user_id'])) {
    die("Access denied. Please login first.");
}

$userId = $_SESSION['user_id'];
$username = $_SESSION['user_name'] ?? 'User';

// Fetch owned documents
$ownedDocs = $pdo->prepare("SELECT * FROM documents WHERE owner_id = ?");
$ownedDocs->execute([$userId]);
$ownedList = $ownedDocs->fetchAll();

// Fetch shared documents
$sharedDocs = $pdo->prepare("
    SELECT d.* FROM documents d
    JOIN document_access da ON da.document_id = d.id
    WHERE da.user_id = ?
");
$sharedDocs->execute([$userId]);
$sharedList = $sharedDocs->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>User Dashboard</title>
    <link rel="stylesheet" href="css/style.css" />
</head>
<body>
    <header class="dashboard-header">
        <h1><span class="welcome-text">WELCOME, </span><?= htmlspecialchars($username) ?></h1>
        <form action="logout.php" method="POST" class="logout-form">
            <button type="submit" class="logout-button">Logout</button>
        </form>
    </header>

    <section>
        <h2>Create New Document</h2>
        <form method="POST" action="core/handleForms.php" class="create-doc-form">
            <input type="text" name="title" placeholder="Document Title" required />
            <input type="hidden" name="action" value="create_document" />
            <button type="submit">Create</button>
        </form>
    </section>

    <section>
        <h2>Your Documents</h2>
        <?php if (count($ownedList) > 0): ?>
            <ul>
                <?php foreach ($ownedList as $doc): ?>
                    <li>
                        <a href="userdocument.php?id=<?= urlencode($doc['id']) ?>">
                            <?= htmlspecialchars($doc['title']) ?>
                        </a> (Owned)
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>You do not own any documents yet.</p>
        <?php endif; ?>
    </section>

    <section>
        <h2>Shared With You</h2>
        <?php if (count($sharedList) > 0): ?>
            <ul>
                <?php foreach ($sharedList as $doc): ?>
                    <li>
                        <a href="userdocument.php?id=<?= urlencode($doc['id']) ?>">
                            <?= htmlspecialchars($doc['title']) ?>
                        </a> (Shared)
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>No documents are shared with you.</p>
        <?php endif; ?>
    </section>
</body>
</html>