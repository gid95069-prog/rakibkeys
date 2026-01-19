<?php
// ===============================
// XIRUS MODZ API.PHP (FINAL)
// VIP + FREE KEY VALIDATOR
// ===============================

header('Content-Type: application/json');
error_reporting(0); // 500 error hide (production)

include 'db.php'; // mysqli connection required

// -------------------------------
// INPUT (C++ / APP / OLD API)
// -------------------------------
$key_input = $_REQUEST['key'] ?? $_REQUEST['user_key'] ?? '';
$device_id = $_REQUEST['hwid'] ?? $_REQUEST['serial'] ?? '';

$user_key = trim($key_input);
$serial   = trim($device_id);

// -------------------------------
// BASIC VALIDATION
// -------------------------------
if ($user_key == '') {
    echo json_encode(["status"=>false,"reason"=>"Key Missing"]);
    exit;
}

if ($serial == '') {
    $serial = "UNKNOWN_" . md5($user_key);
}

// -------------------------------
$isValid = false;
$expiryTimestamp = 0;
// -------------------------------


// ==================================================
// 1️⃣ VIP KEY CHECK (premium_keys)
// ==================================================
$stmt = $conn->prepare("SELECT * FROM premium_keys WHERE key_code = ?");
$stmt->bind_param("s", $user_key);
$stmt->execute();
$res = $stmt->get_result();

if ($res && $res->num_rows > 0) {

    $row = $res->fetch_assoc();
    $expiryTimestamp = strtotime($row['expiry_date']);

    if ($expiryTimestamp <= time()) {
        echo json_encode(["status"=>false,"reason"=>"Key Expired"]);
        exit;
    }

    $used_devices = $row['used_ips'] ? explode(',', $row['used_ips']) : [];

    // Already registered device
    if (in_array($serial, $used_devices)) {
        $isValid = true;
    }
    // New device allowed
    elseif ($row['devices_used'] < $row['max_devices']) {

        $new_devices = $row['used_ips']
            ? $row['used_ips'] . ',' . $serial
            : $serial;

        $new_count = $row['devices_used'] + 1;

        $up = $conn->prepare(
            "UPDATE premium_keys SET used_ips=?, devices_used=? WHERE id=?"
        );
        $up->bind_param("sii", $new_devices, $new_count, $row['id']);
        $up->execute();

        $isValid = true;
    }
    else {
        echo json_encode(["status"=>false,"reason"=>"Device Limit Reached"]);
        exit;
    }
}

// ==================================================
// 2️⃣ FREE KEY CHECK (ip_logs)
// ==================================================
else {

    $stmt2 = $conn->prepare("SELECT * FROM ip_logs WHERE generated_key = ?");
    $stmt2->bind_param("s", $user_key);
    $stmt2->execute();
    $res2 = $stmt2->get_result();

    if (!$res2 || $res2->num_rows == 0) {
        echo json_encode(["status"=>false,"reason"=>"Invalid Key"]);
        exit;
    }

    $row = $res2->fetch_assoc();

    $created = strtotime($row['created_at']);
    $expiryTimestamp = $created + 86400; // 24 HOURS

    if (time() > $expiryTimestamp) {
        echo json_encode(["status"=>false,"reason"=>"Key Expired"]);
        exit;
    }

    // Device lock
    if ($row['device_id'] == '' || $row['device_id'] === null) {

        $up = $conn->prepare(
            "UPDATE ip_logs SET device_id=? WHERE id=?"
        );
        $up->bind_param("si", $serial, $row['id']);
        $up->execute();

        $isValid = true;
    }
    elseif ($row['device_id'] === $serial) {
        $isValid = true;
    }
    else {
        echo json_encode(["status"=>false,"reason"=>"Key Used on Another Device"]);
        exit;
    }
}


// ==================================================
// ✅ SUCCESS RESPONSE (C++ FRIENDLY)
// ==================================================
if ($isValid) {
    echo json_encode([
        "status" => true,
        "data" => [
            "token" => md5("RAKIB_SALT_" . $user_key),
            "rng"   => time(),
            "EXP"   => $expiryTimestamp
        ]
    ]);
    exit;
}

// FALLBACK
echo json_encode(["status"=>false,"reason"=>"Unknown Error"]);
exit;

?>
