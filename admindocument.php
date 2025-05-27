<?php
require_once 'core/dbConfig.php';
echo '<pre>'; print_r($_SESSION); echo '</pre>';
// Admin session check
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    die("Access denied. Admins only.");
}

// Get doc ID from GET or POST
$docId = $_GET['id'] ?? $_POST['doc_id'] ?? null;
if (!$docId) {
    die("No document specified.");
}

// Fetch document and owner
$docQuery = $pdo->prepare("
    SELECT d.*, u.name AS owner_name
    FROM documents d
    JOIN users u ON d.owner_id = u.id
    WHERE d.id = ?
");
$docQuery->execute([$docId]);
$doc = $docQuery->fetch();

if (!$doc) {
    die("Document not found.");
}

// Admins can always edit
$canEdit = true;
?>
<!DOCTYPE html>
<html>
<head>
    <title><?= htmlspecialchars($doc['title']) ?> (Admin)</title>
    <link rel="stylesheet" href="css/style.css"/>
</head>
<body>
    <h1><?= htmlspecialchars($doc['title']) ?></h1>
    <p>Owner: <?= htmlspecialchars($doc['owner_name']) ?></p>

    <?php if ($canEdit): ?>
        <label for="headingSelect">Choose Text Style:</label>
        <select id="headingSelect">
            <option value="">Normal Text</option>
            <option value="h1">Heading 1 (H1)</option>
            <option value="h2">Heading 2 (H2)</option>
            <option value="h3">Heading 3 (H3)</option>
        </select>
        <button type="button" onclick="applyHeading()">Apply</button>

        <form method="POST" action="../core/handleForms.php" onsubmit="return prepareSubmission()">
            <div id="editor" contenteditable="true" spellcheck="true"><?= $doc['content'] ?></div>
            <input type="hidden" name="content" id="hiddenContent">
            <input type="hidden" name="action" value="admin_save_doc">
            <input type="hidden" name="doc_id" value="<?= htmlspecialchars($docId) ?>">
            <br>
            <button type="submit">Save</button>
        </form>

        <script>
        function applyHeading() {
            const headingType = document.getElementById("headingSelect").value;
            const selection = window.getSelection();

            if (!selection.rangeCount) return;

            const range = selection.getRangeAt(0);
            const editor = document.getElementById("editor");

            // Check if selection is inside the #editor
            if (!editor.contains(range.commonAncestorContainer)) {
                alert("Please select text *inside* the document to format.");
                return;
            }

            const selectedText = range.toString();
            if (selectedText.trim() === "") {
                alert("Please select some text to format.");
                return;
            }

            const newNode = document.createElement(headingType || "span");
            newNode.textContent = selectedText;

            range.deleteContents();
            range.insertNode(newNode);
        }

        function prepareSubmission() {
            document.getElementById('hiddenContent').value = document.getElementById('editor').innerHTML;
            return true;
        }
        </script>

    <?php else: ?>
        <div style="border:1px solid #ccc; padding:10px; font-family: Arial, sans-serif;">
            <?= htmlspecialchars($doc['content']) ?>
        </div>
        <p><i>(You don't have permission to edit this document)</i></p>
    <?php endif; ?>
</body>
</html>