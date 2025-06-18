<?php
require_once 'config.php';

$data = json_decode(file_get_contents("php://input"));

if(isset($data->userId)) {
    $sql = "SELECT f.id, u.username 
            FROM friendships f
            INNER JOIN users u ON f.user_id = u.id
            WHERE f.friend_id = ? AND f.status = 'pending'";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $data->userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $requests = [];
    while($row = $result->fetch_assoc()) {
        $requests[] = $row;
    }
    
    echo json_encode(['status' => 'success', 'requests' => $requests]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid input']);
}

$conn->close();