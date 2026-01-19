<?php
session_start();
include 'db.php';

/* ===============================
   1. AUTO CLEANUP (24 HOURS)
================================ */
$conn->query("DELETE FROM ip_logs WHERE created_at < NOW() - INTERVAL 1 DAY");

/* ===============================
   2. LOGIN LOGIC
================================ */
if (isset($_POST['login'])) {
    if ($_POST['admin_pass'] === "RAKIBPAPA9012") {
        $_SESSION['is_admin'] = true;
        header("Location: admin.php");
        exit;
    } else {
        $error = "ACCESS DENIED!";
    }
}

/* ===============================
   3. LOGOUT
================================ */
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: admin.php");
    exit;
}

/* ===============================
   4. CREATE VIP KEY
================================ */
if (isset($_POST['create_key']) && isset($_SESSION['is_admin'])) {

    $days = max(1, (int)$_POST['custom_days']);
    $max_dev = max(1, (int)$_POST['max_devices']);
    $custom = trim($_POST['custom_key_input']);

    $new_key = $custom ?: "VIP-" . strtoupper(bin2hex(random_bytes(3))) . "-RAKIB";
    $expiry = date("Y-m-d H:i:s", strtotime("+$days days"));

    $chk = $conn->query("SELECT id FROM premium_keys WHERE key_code='$new_key'");
    if ($chk->num_rows > 0) {
        $_SESSION['msg'] = "Key Already Exists!";
        $_SESSION['msg_type'] = "error";
    } else {
        $stmt = $conn->prepare(
            "INSERT INTO premium_keys (key_code, max_devices, expiry_date) VALUES (?, ?, ?)"
        );
        $stmt->bind_param("sis", $new_key, $max_dev, $expiry);
        $stmt->execute();

        $_SESSION['newly_created_key'] = $new_key;
        $_SESSION['msg'] = "VIP KEY GENERATED!";
        $_SESSION['msg_type'] = "success";
    }
    header("Location: admin.php");
    exit;
}

/* ===============================
   5. DELETE SELECTED FREE KEYS
================================ */
if (isset($_POST['delete_selected']) && isset($_SESSION['is_admin'])) {
    if (!empty($_POST['check_list'])) {
        $ids = implode(',', array_map('intval', $_POST['check_list']));
        $conn->query("DELETE FROM ip_logs WHERE id IN ($ids)");
        $_SESSION['msg'] = "Selected Keys Deleted!";
        $_SESSION['msg_type'] = "success";
    } else {
        $_SESSION['msg'] = "No Keys Selected!";
        $_SESSION['msg_type'] = "error";
    }
    header("Location: admin.php");
    exit;
}

/* ===============================
   6. DELETE ALL FREE KEYS
================================ */
if (isset($_POST['del_all_free']) && isset($_SESSION['is_admin'])) {
    $conn->query("DELETE FROM ip_logs");
    $_SESSION['msg'] = "ALL FREE KEYS DELETED!";
    $_SESSION['msg_type'] = "success";
    header("Location: admin.php");
    exit;
}

/* ===============================
   7. INDIVIDUAL DELETE
================================ */
if (isset($_GET['del_vip']) && isset($_SESSION['is_admin'])) {
    $conn->query("DELETE FROM premium_keys WHERE id=".(int)$_GET['del_vip']);
    header("Location: admin.php");
    exit;
}
if (isset($_GET['del_free']) && isset($_SESSION['is_admin'])) {
    $conn->query("DELETE FROM ip_logs WHERE id=".(int)$_GET['del_free']);
    header("Location: admin.php");
    exit;
}

/* ===============================
   8. MASTER RESET (DELETE ALL KEYS)
================================ */
if (isset($_POST['master_reset']) && isset($_SESSION['is_admin'])) {
    $conn->query("DELETE FROM ip_logs");
    $conn->query("DELETE FROM premium_keys");
    $_SESSION['msg'] = "MASTER RESET DONE! ALL KEYS CLEARED!";
    $_SESSION['msg_type'] = "success";
    header("Location: admin.php");
    exit;
}

/* ===============================
   9. MAINTENANCE MODE TOGGLE
================================ */
if (isset($_POST['toggle_maintenance']) && isset($_SESSION['is_admin'])) {
    $current = file_exists("maintenance.flag");
    if ($current) {
        unlink("maintenance.flag");
        $_SESSION['msg'] = "Maintenance Mode: OFF";
    } else {
        file_put_contents("maintenance.flag","1");
        $_SESSION['msg'] = "Maintenance Mode: ON";
    }
    $_SESSION['msg_type'] = "success";
    header("Location: admin.php");
    exit;
}

/* ===============================
   10. STATS
================================ */
$total_keys = $today_keys = 0;
$maintenance = file_exists("maintenance.flag") ? true : false;
if (isset($_SESSION['is_admin'])) {
    $total_keys = $conn->query("SELECT COUNT(*) c FROM ip_logs")->fetch_assoc()['c'];
    $today_keys = $conn->query(
        "SELECT COUNT(*) c FROM ip_logs WHERE DATE(created_at)=CURDATE()"
    )->fetch_assoc()['c'];
}
?>

<!DOCTYPE html>
<html>
<head>
<title>RAKIB PAPA | ADMIN</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@600&display=swap" rel="stylesheet">

<style>
body{background:#050505;color:#fff;font-family:Orbitron;margin:0}
.container{max-width:1100px;margin:auto;padding:20px;display:flex;flex-direction:column;gap:20px;}
.card{background:#111;border:1px solid #333;border-radius:12px;padding:20px;margin-bottom:20px;position:relative;overflow:hidden;box-shadow:0 0 20px rgba(0,255,255,0.2);}
.card::before{content:"";position:absolute;inset:-2px;background:linear-gradient(45deg,#00f2ff,#ff00ff,#ff0055);filter:blur(8px);z-index:-1;border-radius:14px;}
.rgb{animation:rgb 4s infinite alternate}
@keyframes rgb{0%{box-shadow:0 0 10px #f00}50%{box-shadow:0 0 10px #0f0}100%{box-shadow:0 0 10px #0ff}}
input,button,select{background:#000;border:1px solid #333;color:#fff;padding:8px;border-radius:6px}
button{cursor:pointer}
.btn{background:linear-gradient(90deg,#00f2ff,#ff00ff);border:none;padding:10px;font-weight:700;transition:0.3s;box-shadow:0 0 15px #ff00ff;}
.btn:hover{transform:scale(1.05);box-shadow:0 0 25px #00ffdd;}
.btn-danger{background:#400}
.btn-warn{background:#432}
table{width:100%;border-collapse:collapse}
th,td{border-bottom:1px solid #333;padding:8px;font-size:13px}
th{color:#aaa}
.chk{transform:scale(1.4)}
.glow{color:#0ff;text-shadow:0 0 5px #0ff,0 0 15px #0ff;}
</style>

<script>
function copyText(txt){navigator.clipboard.writeText(txt);alert("Copied: "+txt);}
</script>
</head>

<body>
<div class="container">

<?php if (!isset($_SESSION['is_admin'])): ?>
<div class="card rgb" style="max-width:400px;margin:auto;text-align:center">
<img src="logo.png" width="80"><br><br>
<form method="post">
<input type="password" name="admin_pass" placeholder="ADMIN PASSWORD"><br><br>
<button name="login" class="btn">LOGIN</button>
</form>
<?php if(isset($error)) echo "<p style='color:red'>$error</p>"; ?>
</div>

<?php else: ?>

<div class="card rgb" style="display:flex;justify-content:space-between;align-items:center;">
<b>RAKIB PAPA ADMIN</b>
<div style="display:flex;gap:10px;">
<form method="post" style="display:inline;"><button name="toggle_maintenance" class="btn"><?= $maintenance ? "Maintenance: ON" : "Maintenance: OFF" ?></button></form>
<form method="post" style="display:inline;"><button name="master_reset" class="btn btn-danger">MASTER RESET</button></form>
<a href="?logout=true" style="color:red;font-weight:bold;text-decoration:none;">LOGOUT</a>
</div>
</div>

<?php if(isset($_SESSION['newly_created_key'])): ?>
<div class="card" style="border-color:#0f0">
NEW VIP KEY:
<b class="glow"><?=$_SESSION['newly_created_key']?></b>
</div>
<?php unset($_SESSION['newly_created_key']); endif; ?>

<?php
if(isset($_SESSION['msg'])){
$c=$_SESSION['msg_type']=="error"?"red":"lime";
echo "<div class='card' style='color:$c'>".$_SESSION['msg']."</div>";
unset($_SESSION['msg']);
}
?>

<div class="card">
<form method="post">
<input name="custom_key_input" placeholder="Custom VIP Key">
<input type="number" name="max_devices" value="1" min="1">
<input type="number" name="custom_days" placeholder="Days" required>
<button name="create_key" class="btn">CREATE VIP KEY</button>
</form>
</div>

<div class="card">
<b>VIP KEYS</b>
<table>
<tr><th>KEY</th><th>DEV</th><th>EXP</th><th>DEL</th></tr>
<?php
$r=$conn->query("SELECT * FROM premium_keys ORDER BY id DESC");
while($row=$r->fetch_assoc()){
$days=floor((strtotime($row['expiry_date'])-time())/86400);
echo "<tr>
<td onclick=\"copyText('{$row['key_code']}')\" style='cursor:pointer;color:#0f0'>{$row['key_code']}</td>
<td>{$row['devices_used']}/{$row['max_devices']}</td>
<td>".($days<0?"<span style=color:red>Expired</span>":"$days d")."</td>
<td><a href='?del_vip={$row['id']}' style='color:red'>X</a></td>
</tr>";
}
?>
</table>
</div>

<div class="card">
<b>FREE KEYS (Today <?=$today_keys?> | Total <?=$total_keys?>)</b>
<form method="post">
<table>
<tr>
<th><input type="checkbox" onclick="document.querySelectorAll('.chk').forEach(c=>c.checked=this.checked)"></th>
<th>IP</th><th>KEY</th><th>TIME</th><th>DEL</th>
</tr>
<?php
$r=$conn->query("SELECT * FROM ip_logs ORDER BY id DESC LIMIT 100");
while($row=$r->fetch_assoc()){
echo "<tr>
<td><input type='checkbox' class='chk' name='check_list[]' value='{$row['id']}'></td>
<td>{$row['user_ip']}</td>
<td onclick=\"copyText('{$row['generated_key']}')\" style='cursor:pointer;color:#0ff'>{$row['generated_key']}</td>
<td>{$row['created_at']}</td>
<td><a href='?del_free={$row['id']}' style='color:red'>X</a></td>
</tr>";
}
?>
</table><br>
<button name="delete_selected" class="btn-warn">DELETE SELECTED</button>
<button name="del_all_free" class="btn-danger">DELETE ALL FREE KEYS</button>
</form>
</div>

<?php endif; ?>
</div>
</body>
</html>
S
