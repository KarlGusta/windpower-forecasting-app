<?php
require_once '../config/database.php';

// Initialize response array
$response = array(
    'status' => 'success',
    'data' => null,
    'message' => ''
);

try {
    // Get database connection using singleton pattern
    $conn = Config\Database::getInstance()->getConnection();

    // Validate database connection
    if (!$conn) {
        throw new Exception("Database connection failed");
    }

    $query = "SELECT wind_speed, power_output FROM wind_data ORDER BY wind_speed";
    $result = $conn->query($query);

    if (!$result) {
        throw new Exception("Query failed: " . $conn->error);
    }

    $data = array();
    while($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    // Check if data was found
    if (empty($data)) {
        $response['status'] = 'warning';
        $response['message'] = 'No data found';
    } else {
        $response['data'] = $data;
        $response['message'] = 'Data retrieved successfully';
    }

} catch (Exception $e) {
    $response['status'] = 'error';
    $response['message'] = $e->getMessage();
    error_log("Power Curve Data Error: " . $e->getMessage());
} finally {
    if ($conn) {
        $conn->close();
    }
}

header('Content-Type: application/json');
echo json_encode($response);
?>