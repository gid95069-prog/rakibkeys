<?php
$host = "mysql-xirusmodz.alwaysdata.net";
$user = "xirusmodz";
$pass = "XIRUS@123";
$dbname = "xirusmodz_key";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
