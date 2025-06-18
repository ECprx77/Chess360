<?php
require_once 'config.php';

$data = json_decode(file_get_contents("php://input"));

if(isset($data->userId) && isset($data->targetId)) {
    $sql = "SELECT status FROM friendships 
            WHERE (user_id = ? AND friend_id = ?) 
            OR (user_id = ? AND friend_id = ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiii", 
        $data->userId, $data->targetId,
        $data->targetId, $data->userId
    );
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows > 0) {
        $friendship = $result->fetch_assoc();
        echo json_encode([
            'status' => $friendship['status']
        ]);
    } else {
        echo json_encode([
            'status' => 'none'
        ]);
    }
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid input'
    ]);
}

$conn->close();