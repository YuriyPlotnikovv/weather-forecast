<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/core/classes/Tools.php';
header('Content-Type: application/json');

if (isset($_GET['currentIp'])) {
    $clientIp = $_GET['currentIp'];
} else {
    $clientIp = $_SERVER['REMOTE_ADDR'];

    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $clientIp = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $clientIp = $_SERVER['HTTP_CLIENT_IP'];
    }
}

$data = Tools::getIpLocation($clientIp);

if ($data !== null) {
    echo json_encode(['success' => true, 'data' => $data]);
} else {
    echo json_encode(['success' => false, 'message' => 'Не удалось получить данные.']);
}
