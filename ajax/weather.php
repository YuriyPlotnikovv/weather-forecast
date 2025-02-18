<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/core/classes/Weather.php';
header('Content-Type: application/json');

if (isset($_GET['latitude']) && isset($_GET['longitude'])) {
    $latitude = $_GET['latitude'];
    $longitude = $_GET['longitude'];

    $weather = new Weather($latitude, $longitude);
    $data = $weather->getData();

    if ($data !== null) {
        echo json_encode(['success' => true, 'data' => $data]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Не удалось получить данные.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Не указаны широта и долгота.']);
}