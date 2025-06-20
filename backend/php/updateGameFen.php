<?php
require_once 'config.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"));

if(!isset($data->gameId) || !isset($data->fen)) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required data']);
    exit;
}

try {
    $sql = "UPDATE games SET initial_fen = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $data->fen, $data->gameId);
    
    if($stmt->execute()) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update game']);
    }
} catch(Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}

$conn->close();