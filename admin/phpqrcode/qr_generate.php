<?php
include "qrlib.php";

$data = isset($_GET['data']) ? trim($_GET['data']) : "No Data";

// Using Level L makes the QR code less dense and dots larger, which is much easier to scan on screens
QRcode::png($data, false, QR_ECLEVEL_L, 10, 2);
?>