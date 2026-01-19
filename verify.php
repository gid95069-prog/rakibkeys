<?php
session_start();
include 'db.php';

/* =======================
   SECURITY CONFIG
======================= */
$secret_code = "RAKIB";
$min_seconds = 200;

/* =======================
   ERROR FUNCTION
======================= */
function die_error($title, $msg){
    echo "<!DOCTYPE html><html><head><title>Error</title></head>
    <body style='background:#050505;color:#fff;font-family:Arial;display:flex;justify-content:center;align-items:center;height:100vh'>
    <div style='border:1px solid red;padding:25px;border-radius:12px;text-align:center;box-shadow:0 0 20px red'>
        <h2 style='color:red'>$title</h2>
        <p>$msg</p>
        <a href='index.php' style='color:#00ff88;text-decoration:none;border:1px solid #00ff88;padding:6px 12px;border-radius:6px'>GO BACK</a>
    </div></body></html>";
    exit;
}

/* =======================
   SECURITY CHECKS
======================= */
if (!isset($_GET['token']) || $_GET['token'] !== $secret_code) {
    die_error("INVALID TOKEN", "Shortlink token mismatch");
}

if (!isset($_SESSION['flow_started'])) {
    die_error("FLOW ERROR", "Start process from home page");
}

if ($_SESSION['secure_ip'] !== $_SERVER['REMOTE_ADDR']) {
    die_error("IP CHANGED", "VPN / Proxy detected");
}

if ($_SESSION['secure_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
    die_error("BROWSER CHANGED", "Browser mismatch");
}

$time_taken = time() - $_SESSION['start_time'];
if ($time_taken < $min_seconds) {
    die_error("BYPASS DETECTED", "Time bypass blocked");
}

/* =======================
   GENERATE FREE KEY
======================= */
$prefix = "RAKIB_PAPA_";
$random = random_int(100000, 999999999); // numbers only
$generated_key = $prefix . $random;

/* =======================
   SAVE KEY IN DATABASE
======================= */
$stmt = $conn->prepare(
    "INSERT INTO ip_logs (generated_key, device_id, created_at) VALUES (?, ?, NOW())"
);
$stmt->bind_param("ss", $generated_key, $_SESSION['secure_ip']);
$stmt->execute();

/* =======================
   CLEAN SESSION
======================= */
unset($_SESSION['flow_started']);
unset($_SESSION['start_time']);
unset($_SESSION['secure_ip']);
unset($_SESSION['secure_agent']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>RAKIB PAPA KEY</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>
body{
    margin:0;
    background:radial-gradient(circle at top,#050a2a,#02010a);
    font-family:'Poppins',sans-serif;
    color:#fff;
    display:flex;
    justify-content:center;
    align-items:center;
    height:100vh;
}
.card{
    background:#0b0f2a;
    border-radius:18px;
    padding:28px;
    width:100%;
    max-width:380px;
    text-align:center;
    border:1px solid rgba(0,255,255,.3);
    box-shadow:0 0 40px rgba(0,255,255,.25);
}
.logo img{
    width:80px;
    border-radius:50%;
    border:3px solid #00f2ff;
    box-shadow:0 0 25px #00f2ff;
}
h2{
    margin:15px 0;
    font-family:Orbitron, sans-serif;
    background:linear-gradient(90deg,#00f2ff,#ff00ff);
    -webkit-background-clip:text;
    -webkit-text-fill-color:transparent;
}
.keybox{
    margin-top:15px;
    background:#05081d;
    border:1px solid #2a2f55;
    padding:12px;
    border-radius:8px;
    color:#00ff88;
    font-family:monospace;
    font-size:15px;
    word-break:break-all;
}
.btn{
    margin-top:18px;
    width:100%;
    padding:12px;
    border:none;
    border-radius:8px;
    font-family:Orbitron,sans-serif;
    font-weight:700;
    color:#fff;
    cursor:pointer;
    background:linear-gradient(90deg,#00dbde,#fc00ff);
    box-shadow:0 0 25px rgba(252,0,255,.5);
}
.btn:hover{
    transform:scale(1.05);
}
</style>
</head>

<body>
<div class="card">
    <div class="logo">
        <img src="logo.png">
    </div>

    <h2>KEY GENERATED</h2>

    <div class="keybox">
        <?php echo htmlspecialchars($generated_key); ?>
    </div>

    <button class="btn" onclick="navigator.clipboard.writeText('<?php echo $generated_key; ?>')">
        COPY KEY
    </button>
</div>
</body>
</html>
