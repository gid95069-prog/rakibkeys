<?php
session_start();

// ✅ CONFIG: APNE SHORTLINKS YAHAN DALEIN
$link_1 = "https://vplink.in/MJgN"; 
$link_2 = "https://just2earn.com/7UdY5"; 
$link_3 = "https://earnlinks.in/KMSz"; 

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    // 🔒 SECURITY 1: FINGERPRINT (IP + Browser Note Karo)
    $_SESSION['secure_ip'] = $_SERVER['REMOTE_ADDR'];
    $_SESSION['secure_agent'] = $_SERVER['HTTP_USER_AGENT'];
    
    // 🔒 SECURITY 2: TIME START (Abhi ka waqt note karo)
    $_SESSION['start_time'] = time();
    
    // 🔒 SECURITY 3: FLOW CHECK (Proof ki banda yahan se gaya hai)
    $_SESSION['flow_started'] = true;

    // Redirect to Shortlink
    if ($id == 1) { header("Location: $link_1"); }
    elseif ($id == 2) { header("Location: $link_2"); }
    elseif ($id == 3) { header("Location: $link_3"); }
    else { echo "Invalid Server ID"; }
    
    exit;
}
?>
