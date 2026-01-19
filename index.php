<?php
session_start();
include 'db.php';

/* =====================================================
   BASIC SETTINGS
===================================================== */
date_default_timezone_set("Asia/Kolkata");

/* =====================================================
   IP FUNCTION
===================================================== */
function getUserIP() {
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP']))
        return $_SERVER['HTTP_CF_CONNECTING_IP'];

    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ip[0]);
    }

    return $_SERVER['REMOTE_ADDR'];
}

$user_ip = getUserIP();

/* =====================================================
   KEY SYSTEM
===================================================== */
$show_key = false;
$final_key = "";
$status_text = "Waiting for Verification...";

$conn->query("DELETE FROM ip_logs WHERE created_at < NOW() - INTERVAL 1 DAY");

$check = $conn->query("SELECT * FROM ip_logs WHERE user_ip='$user_ip' LIMIT 1");
if ($check->num_rows > 0) {
    $row = $check->fetch_assoc();
    $show_key = true;
    $final_key = $row['generated_key'];
    $status_text = "Active Key Found";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>RAKIB PAPA KEY SYSTEM</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@500;700;800&display=swap" rel="stylesheet">

<style>

/* =====================================================
   GLOBAL STYLE
===================================================== */

body{
margin:0;
font-family:'Orbitron',sans-serif;
background:radial-gradient(circle at top,#050a2a,#02010a 70%);
color:#fff;
display:flex;
justify-content:center;
align-items:center;
min-height:100vh;
overflow:hidden;
}

.container{
width:100%;
max-width:480px;
padding:16px;
display:flex;
flex-direction:column;
gap:20px;
}

/* =====================================================
   CARD
===================================================== */

.card{
background:rgba(8,12,40,0.95);
border-radius:18px;
padding:22px;
position:relative;
border:1px solid rgba(0,255,255,0.28);
box-shadow:0 0 45px rgba(0,255,255,0.22);
}

.card::before{
content:"";
position:absolute;
inset:-2px;
border-radius:20px;
background:linear-gradient(135deg,#00f2ff,#7a00ff,#ff00c8);
filter:blur(6px);
z-index:-1;
}

/* =====================================================
   LOGO
===================================================== */

.logo{
width:90px;
height:90px;
border-radius:50%;
border:3px solid #00f2ff;
box-shadow:0 0 30px #00f2ff;
animation:pulse 2.5s infinite;
display:block;
margin:0 auto 12px;
}

@keyframes pulse{
0%{box-shadow:0 0 15px #00f2ff}
50%{box-shadow:0 0 40px #ff00ff}
100%{box-shadow:0 0 15px #00f2ff}
}

/* =====================================================
   TEXT
===================================================== */

h1{
text-align:center;
font-size:22px;
background:linear-gradient(90deg,#00f2ff,#ffffff,#ff00ff);
-webkit-background-clip:text;
-webkit-text-fill-color:transparent;
margin-bottom:10px;
}

p{
text-align:center;
font-size:12px;
color:#aaa;
}

/* =====================================================
   BUTTONS
===================================================== */

.btn{
width:100%;
padding:12px;
border:none;
border-radius:8px;
font-weight:bold;
background:linear-gradient(90deg,#00dbde,#fc00ff);
color:white;
cursor:pointer;
box-shadow:0 0 15px #ff00ff;
margin-top:12px;
}

/* =====================================================
   INPUT
===================================================== */

.select{
width:100%;
padding:12px;
border-radius:8px;
border:1px solid #2a2f55;
background:#05081d;
color:white;
margin-top:10px;
}

/* =====================================================
   LOG BOX
===================================================== */

.log-box{
background:#020318;
border:1px solid #1b1f4a;
border-radius:10px;
padding:10px;
font-size:12px;
line-height:1.6;
margin-top:10px;
}

/* =====================================================
   HIDDEN
===================================================== */

.hide{display:none}

/* =====================================================
   LOADER
===================================================== */

.loader{
width:130px;
height:130px;
border-radius:50%;
border:6px solid rgba(0,255,255,0.15);
border-top:6px solid #00f2ff;
margin:20px auto;
animation:spin 1s linear infinite;
}

@keyframes spin{
from{transform:rotate(0)}
to{transform:rotate(360deg)}
}

.count{
font-size:42px;
text-align:center;
color:#00f2ff;
}

</style>
</head>

<body>

<div class="container">

<!-- ================= INIT PAGE ================= -->

<div class="card" id="initPage">
<img src="logo.png" class="logo">
<h1>Initializing RAKIB PAPA</h1>
<p>Loading secure modules...</p>

<div class="loader"></div>

<button class="btn" onclick="openMain()">CONTINUE</button>
</div>

<!-- ================= MAIN PAGE ================= -->

<div id="mainPage" class="hide">

<div class="card">
<img src="logo.png" class="logo">
<h1>RAKIB PAPA KEY SYSTEM</h1>
</div>

<div class="card">

<?php if($show_key): ?>

<h2 style="text-align:center">Access Key Generated</h2>
<input type="text" class="select" value="<?php echo $final_key; ?>" readonly>

<?php else: ?>

<h2 style="text-align:center">Start Verification</h2>

<select id="serverSelect" class="select">
<option value="1">Global Server</option>
<option value="2">Asia Server</option>
<option value="3">Europe Server</option>
</select>

<button class="btn" onclick="startProcess()">START VERIFICATION</button>

<?php endif; ?>

</div>

<div class="card">
<h3>Security Log</h3>
<div class="log-box">
<div>[<?php echo date("H:i:s"); ?>] IP : <?php echo $user_ip; ?></div>
<div>[<?php echo date("H:i:s"); ?>] Status : <?php echo $status_text; ?></div>
</div>
</div>

</div>

<!-- ================= LOADING ================= -->

<div class="card hide" id="loadingPage">
<h2 style="text-align:center">Please Wait...</h2>
<div class="loader"></div>
<div class="count" id="countNum">3</div>
</div>

</div>

<script>

/* =====================================================
   VOICE FUNCTION
===================================================== */

function speak(text, lang="hi-IN"){
    let msg = new SpeechSynthesisUtterance(text);
    msg.lang = lang;
    msg.rate = 0.9;
    speechSynthesis.cancel();
    speechSynthesis.speak(msg);
}

/* =====================================================
   INIT CONTINUE
===================================================== */

function openMain(){
    speak("WELCOME TO RAKIB PAPA KEY GENERATE SYSTEM");
    document.getElementById("initPage").classList.add("hide");
    document.getElementById("mainPage").classList.remove("hide");
}

/* =====================================================
   START PROCESS
===================================================== */

function startProcess(){
    document.getElementById("mainPage").classList.add("hide");
    document.getElementById("loadingPage").classList.remove("hide");

    let c = 3;
    document.getElementById("countNum").innerText = c;
    speak("Three","en-US");

    let timer = setInterval(()=>{
        c--;

        if(c===2){
            document.getElementById("countNum").innerText="2";
            speak("Two","en-US");
        }

        if(c===1){
            document.getElementById("countNum").innerText="1";
            speak("One","en-US");
        }

        if(c===0){
            clearInterval(timer);
            speak("Aap short link par jaa rahe hain.");
            setTimeout(()=>{
                let id=document.getElementById("serverSelect").value;
                window.location.href="go.php?id="+id;
            },1200);
        }

    },1000);
}

</script>

</body>
</html>