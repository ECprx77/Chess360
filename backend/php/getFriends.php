<?php
require_once 'config.php';

$data = json_decode(file_get_contents("php://input"));

if(isset($data->userId)) {
    $sql = "SELECT u.id, u.username FROM users u 
            INNER JOIN friendships f ON (u.id = f.friend_id) 
            WHERE f.user_id = ? AND f.status = 'accepted'";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $data->userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $friends = [];
    while($row = $result->fetch_assoc()) {
        $friends[] = $row;
    }
    
    echo json_encode(['status' => 'success', 'friends' => $friends]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid input']);
}

$conn->close();